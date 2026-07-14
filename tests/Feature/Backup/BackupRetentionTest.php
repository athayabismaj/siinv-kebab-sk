<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\BackupRetentionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = storage_path('framework/testing/backup-retention-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);
        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.retention' => ['daily' => 1, 'weekly' => 0, 'monthly' => 0, 'minimum_valid' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);
        parent::tearDown();
    }

    public function test_prune_is_a_dry_run_until_delete_is_explicit_and_never_touches_legacy_files(): void
    {
        $older = $this->createArtifact('older', now()->subDays(2));
        $newer = $this->createArtifact('newer', now());
        $legacy = $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'legacy.backup';
        File::put($legacy, 'legacy-content');
        $service = new BackupRetentionService(new BackupFilesystem(), new BackupManifestService());

        $dryRun = $service->prune();
        $this->assertSame(2, $dryRun['valid']);
        $this->assertSame([$older], $dryRun['candidates']);
        $this->assertDirectoryExists($older);
        $this->assertDirectoryExists($newer);
        $this->assertFileExists($legacy);

        $deleted = $service->prune(true);
        $this->assertSame([$older], $deleted['deleted']);
        $this->assertDirectoryDoesNotExist($older);
        $this->assertDirectoryExists($newer);
        $this->assertFileExists($legacy);
    }

    private function createArtifact(string $id, \Carbon\CarbonInterface $createdAt): string
    {
        $directory = $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifacts'.DIRECTORY_SEPARATOR.$id;
        File::ensureDirectoryExists($directory);
        $dump = $directory.DIRECTORY_SEPARATOR.'database.dump';
        File::put($dump, 'dump-'.$id);
        (new BackupManifestService())->write($dump, [
            'backup_id' => $id,
            'created_at' => $createdAt->toIso8601String(),
            'backup_format' => 'custom',
            'checksum_algorithm' => 'sha256',
            'checksum' => hash_file('sha256', $dump),
            'size_bytes' => filesize($dump),
            'status' => 'success',
        ]);

        return $directory;
    }
}
