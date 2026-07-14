<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;

class BackupManifestService
{
    /** @param array<string, mixed> $manifest */
    public function write(string $artifactPath, array $manifest): string
    {
        $manifest['checksum_algorithm'] = $manifest['checksum_algorithm'] ?? 'sha256';
        $manifest['checksum'] = $manifest['checksum'] ?? hash_file('sha256', $artifactPath);
        $manifest['size_bytes'] = $manifest['size_bytes'] ?? filesize($artifactPath);

        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $path = $this->pathFor($artifactPath);
        File::put($path, $encoded);

        return $path;
    }

    /** @return array<string, mixed> */
    public function read(string $artifactPath): array
    {
        $path = $this->pathFor($artifactPath);

        if (! is_file($path)) {
            throw new RuntimeException('Backup manifest is not available.');
        }

        try {
            $manifest = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('Backup manifest is not valid.');
        }

        if (! is_array($manifest)) {
            throw new RuntimeException('Backup manifest is not valid.');
        }

        return $manifest;
    }

    /** @return array<string, mixed> */
    public function verify(string $artifactPath): array
    {
        $manifest = $this->read($artifactPath);
        $required = ['backup_id', 'backup_format', 'checksum_algorithm', 'checksum', 'size_bytes', 'status'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $manifest)) {
                throw new RuntimeException('Backup manifest is incomplete.');
            }
        }

        if (! is_file($artifactPath) || is_link($artifactPath) || $manifest['checksum_algorithm'] !== 'sha256' || $manifest['status'] !== 'success') {
            throw new RuntimeException('Backup artifact is not valid.');
        }

        if ((int) $manifest['size_bytes'] <= 0 || filesize($artifactPath) !== (int) $manifest['size_bytes'] || ! hash_equals((string) $manifest['checksum'], (string) hash_file('sha256', $artifactPath))) {
            throw new RuntimeException('Backup checksum verification failed.');
        }

        return $manifest;
    }

    public function pathFor(string $artifactPath): string
    {
        return $artifactPath.'.manifest.json';
    }
}
