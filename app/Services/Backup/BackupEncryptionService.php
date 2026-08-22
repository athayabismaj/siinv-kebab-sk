<?php

namespace App\Services\Backup;

use RuntimeException;

class BackupEncryptionService
{
    private const MAGIC = 'SIINVBACKUPENC1';

    private const SALT_BYTES = 16;

    private const NONCE_BYTES = 12;

    private const TAG_BYTES = 16;

    private const SIZE_BYTES = 20;

    private const CHUNK_BYTES = 1048576;

    private const CIPHER = 'aes-256-gcm';

    public function encryptFile(string $sourcePath, string $destinationPath): void
    {
        $sourceSize = $this->assertReadableSource($sourcePath);
        $source = fopen($sourcePath, 'rb');
        $destination = fopen($destinationPath, 'wb');
        if ($source === false || $destination === false) {
            $this->close($source);
            $this->close($destination);
            @unlink($destinationPath);
            throw new RuntimeException('Unable to open backup encryption stream.');
        }

        $key = '';
        $completed = false;

        try {
            $salt = random_bytes(self::SALT_BYTES);
            $key = $this->deriveKey($salt);
            $this->write($destination, self::MAGIC.$salt.str_pad((string) $sourceSize, self::SIZE_BYTES, '0', STR_PAD_LEFT));
            $chunkIndex = 0;

            while (! feof($source)) {
                $plain = fread($source, self::CHUNK_BYTES);
                if ($plain === false) {
                    throw new RuntimeException('Unable to read backup encryption stream.');
                }
                if ($plain === '') {
                    break;
                }

                $nonce = random_bytes(self::NONCE_BYTES);
                $tag = '';
                $cipherText = openssl_encrypt(
                    $plain,
                    self::CIPHER,
                    $key,
                    OPENSSL_RAW_DATA,
                    $nonce,
                    $tag,
                    $this->aad($chunkIndex),
                    self::TAG_BYTES,
                );
                if ($cipherText === false || strlen($tag) !== self::TAG_BYTES) {
                    throw new RuntimeException('Backup encryption failed.');
                }

                $this->write($destination, pack('N', strlen($cipherText)).$nonce.$tag.$cipherText);
                $chunkIndex++;
            }
            $completed = true;
        } finally {
            if ($key !== '') {
                $this->forgetKey($key);
            }
            fclose($source);
            fclose($destination);
            if (! $completed) {
                @unlink($destinationPath);
            }
        }
    }

    public function decryptFile(string $sourcePath, string $destinationPath): void
    {
        $this->assertReadableSource($sourcePath);
        $source = fopen($sourcePath, 'rb');
        $destination = fopen($destinationPath, 'wb');
        if ($source === false || $destination === false) {
            $this->close($source);
            $this->close($destination);
            @unlink($destinationPath);
            throw new RuntimeException('Unable to open backup decryption stream.');
        }

        $headerLength = strlen(self::MAGIC) + self::SALT_BYTES + self::SIZE_BYTES;
        try {
            $header = $this->readExact($source, $headerLength);
        } catch (\Throwable $exception) {
            fclose($source);
            fclose($destination);
            @unlink($destinationPath);
            throw $exception;
        }
        if (! str_starts_with($header, self::MAGIC)) {
            fclose($source);
            fclose($destination);
            @unlink($destinationPath);
            throw new RuntimeException('Encrypted backup header is invalid.');
        }

        $offset = strlen(self::MAGIC);
        $salt = substr($header, $offset, self::SALT_BYTES);
        $expectedSizeText = substr($header, $offset + self::SALT_BYTES, self::SIZE_BYTES);
        if (! ctype_digit($expectedSizeText)) {
            fclose($source);
            fclose($destination);
            @unlink($destinationPath);
            throw new RuntimeException('Encrypted backup size metadata is invalid.');
        }

        $expectedSize = (int) ltrim($expectedSizeText, '0');
        $key = '';
        $written = 0;
        $chunkIndex = 0;

        try {
            $key = $this->deriveKey($salt);
            while (! feof($source)) {
                $lengthBytes = fread($source, 4);
                if ($lengthBytes === false) {
                    throw new RuntimeException('Unable to read encrypted backup.');
                }
                if ($lengthBytes === '') {
                    break;
                }
                if (strlen($lengthBytes) !== 4) {
                    throw new RuntimeException('Encrypted backup is truncated.');
                }

                $cipherLength = (int) unpack('Nlength', $lengthBytes)['length'];
                if ($cipherLength <= 0 || $cipherLength > self::CHUNK_BYTES + self::TAG_BYTES) {
                    throw new RuntimeException('Encrypted backup chunk length is invalid.');
                }

                $nonce = $this->readExact($source, self::NONCE_BYTES);
                $tag = $this->readExact($source, self::TAG_BYTES);
                $cipherText = $this->readExact($source, $cipherLength);
                $plain = openssl_decrypt(
                    $cipherText,
                    self::CIPHER,
                    $key,
                    OPENSSL_RAW_DATA,
                    $nonce,
                    $tag,
                    $this->aad($chunkIndex),
                );
                if ($plain === false) {
                    throw new RuntimeException('Encrypted backup authentication failed.');
                }

                $this->write($destination, $plain);
                $written += strlen($plain);
                $chunkIndex++;
            }

            if ($written !== $expectedSize) {
                throw new RuntimeException('Encrypted backup is incomplete.');
            }
        } catch (\Throwable $exception) {
            fclose($source);
            fclose($destination);
            @unlink($destinationPath);
            if ($key !== '') {
                $this->forgetKey($key);
            }
            throw $exception;
        }

        if ($key !== '') {
            $this->forgetKey($key);
        }
        fclose($source);
        fclose($destination);
    }

    private function deriveKey(string $salt): string
    {
        $configured = trim((string) config('backup.encryption.key'));
        $encoded = str_starts_with($configured, 'base64:') ? substr($configured, 7) : $configured;
        $masterKey = base64_decode($encoded, true);
        if ($masterKey === false || strlen($masterKey) !== 32) {
            throw new RuntimeException('Backup encryption key must be a base64-encoded 32-byte key.');
        }

        $derived = hash_hkdf('sha256', $masterKey, 32, 'siinv-backup-v1', $salt);
        $this->forgetKey($masterKey);

        return $derived;
    }

    private function assertReadableSource(string $path): int
    {
        if (! is_file($path) || is_link($path) || ! is_readable($path)) {
            throw new RuntimeException('Backup encryption source is invalid.');
        }

        $size = filesize($path);
        if ($size === false || $size <= 0) {
            throw new RuntimeException('Backup encryption source is empty.');
        }

        return $size;
    }

    /** @param resource $stream */
    private function readExact($stream, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($stream)) {
            $chunk = fread($stream, $length - strlen($buffer));
            if ($chunk === false) {
                throw new RuntimeException('Unable to read encrypted backup stream.');
            }
            $buffer .= $chunk;
        }
        if (strlen($buffer) !== $length) {
            throw new RuntimeException('Encrypted backup is truncated.');
        }

        return $buffer;
    }

    /** @param resource $stream */
    private function write($stream, string $data): void
    {
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($stream, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write encrypted backup stream.');
            }
            $offset += $written;
        }
    }

    private function aad(int $chunkIndex): string
    {
        return self::MAGIC.':'.$chunkIndex;
    }

    private function close(mixed $stream): void
    {
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    private function forgetKey(string &$key): void
    {
        if (function_exists('sodium_memzero')) {
            sodium_memzero($key);

            return;
        }

        $key = str_repeat("\0", strlen($key));
    }
}
