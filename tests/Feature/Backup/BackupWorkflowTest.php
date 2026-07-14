<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlProcessRunner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class BackupWorkflowTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/backup-workflow-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.enabled' => true,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'backup-test',
            'backup.pg_dump_path' => 'pg_dump',
            'backup.timeout' => 30,
            'backup.encryption.enabled' => false,
            'database.connections.backup-test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'safe_fixture',
                'username' => 'safe_user',
                'password' => 'never-in-command',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_backup_publishes_manifest_and_checksum_only_after_a_non_empty_dump_exists(): void
    {
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            $outputIndex = array_search('--file', $command, true) + 1;
            File::put($command[$outputIndex], 'fixture-dump-content');

            return Process::result();
        });

        $backup = $this->backupService($runner)->create('manual');

        $this->assertFileExists($backup['file_path']);
        $this->assertFileExists($backup['manifest_path']);
        $this->assertSame(hash_file('sha256', $backup['file_path']), $backup['manifest']['checksum']);
        $this->assertSame(filesize($backup['file_path']), $backup['manifest']['size_bytes']);
        $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'.tmp'.DIRECTORY_SEPARATOR.$backup['backup_id']);
        $this->assertStringNotContainsString('never-in-command', implode(' ', $runner->commands()[0]));
    }

    public function test_failed_dump_cleans_its_temporary_artifact(): void
    {
        $runner = new PostgreSqlProcessRunner(fn () => Process::result('', 'dump failed', 1));

        try {
            $this->backupService($runner)->create('manual');
            $this->fail('Backup failure must be surfaced.');
        } catch (RuntimeException) {
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'.tmp');
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifacts');
        }
    }

    public function test_empty_dump_is_rejected_and_not_published(): void
    {
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            $outputIndex = array_search('--file', $command, true) + 1;
            File::put($command[$outputIndex], '');

            return Process::result();
        });

        $this->expectException(RuntimeException::class);

        try {
            $this->backupService($runner)->create('manual');
        } finally {
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifacts');
        }
    }

    public function test_encryption_requires_an_explicit_key_when_enabled(): void
    {
        config(['backup.encryption.enabled' => true, 'backup.encryption.key' => null]);

        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->expectException(RuntimeException::class);
        $this->backupService($runner)->create('manual');
    }

    public function test_encryption_cannot_be_silently_claimed_when_no_approved_implementation_exists(): void
    {
        config(['backup.encryption.enabled' => true, 'backup.encryption.key' => 'test-secret']);

        $this->expectException(RuntimeException::class);
        $this->backupService(new PostgreSqlProcessRunner(fn () => Process::result()))->create('manual');
    }

    private function backupService(PostgreSqlProcessRunner $runner): PostgreSqlBackupService
    {
        return new PostgreSqlBackupService(
            new BackupFilesystem(),
            new BackupManifestService(),
            $runner,
        );
    }
}
