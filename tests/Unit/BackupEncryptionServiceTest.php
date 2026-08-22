<?php

namespace Tests\Unit;

use App\Services\Backup\BackupEncryptionService;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class BackupEncryptionServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/backup-encryption-'.uniqid());
        File::ensureDirectoryExists($this->directory);
        config(['backup.encryption.key' => base64_encode(random_bytes(32))]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_encrypted_backup_round_trips_and_rejects_tampering(): void
    {
        $plainPath = $this->directory.'/database.dump';
        $encryptedPath = $this->directory.'/database.dump.enc';
        $restoredPath = $this->directory.'/restored.dump';
        $plain = random_bytes(1024 * 1024 + 137);
        File::put($plainPath, $plain);

        $service = new BackupEncryptionService;
        $service->encryptFile($plainPath, $encryptedPath);

        $this->assertFileExists($encryptedPath);
        $this->assertStringNotContainsString(substr($plain, 0, 64), (string) File::get($encryptedPath));

        $service->decryptFile($encryptedPath, $restoredPath);
        $this->assertSame(hash_file('sha256', $plainPath), hash_file('sha256', $restoredPath));

        $tampered = (string) File::get($encryptedPath);
        $tampered[strlen($tampered) - 1] = chr(ord($tampered[strlen($tampered) - 1]) ^ 1);
        File::put($encryptedPath, $tampered);

        $tamperedOutput = $this->directory.'/tampered.dump';
        try {
            $service->decryptFile($encryptedPath, $tamperedOutput);
            $this->fail('Tampered backup must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('authentication failed', $exception->getMessage());
            $this->assertFileDoesNotExist($tamperedOutput);
        }
    }

    public function test_truncated_header_does_not_leave_a_plaintext_file(): void
    {
        $truncated = $this->directory.'/truncated.dump.enc';
        $output = $this->directory.'/partial.dump';
        File::put($truncated, 'SIINV');

        try {
            (new BackupEncryptionService)->decryptFile($truncated, $output);
            $this->fail('Truncated backup must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('truncated', strtolower($exception->getMessage()));
            $this->assertFileDoesNotExist($output);
        }
    }
}
