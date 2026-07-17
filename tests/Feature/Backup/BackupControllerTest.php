<?php

namespace Tests\Feature\Backup;

use App\Models\Role;
use App\Models\User;
use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlProcessRunner;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/backup-controller-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.enabled' => true,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'backup-test',
            'backup.pg_dump_path' => 'pg_dump',
            'backup.encryption.enabled' => false,
            'database.connections.backup-test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'safe_fixture',
                'username' => 'safe_user',
                'password' => 'process-only-secret',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_developer_backup_button_publishes_a_verified_backup_history_record(): void
    {
        $role = Role::query()->create(['name' => 'developer']);
        $developer = User::factory()->create(['role_id' => $role->id]);

        $this->app->instance(PostgreSqlBackupService::class, new PostgreSqlBackupService(
            new BackupFilesystem(),
            new BackupManifestService(),
            new PostgreSqlProcessRunner(function (array $command) {
                $outputIndex = array_search('--file', $command, true) + 1;
                File::put($command[$outputIndex], 'web-backup-fixture');

                return Process::result();
            }),
        ));

        $this->actingAs($developer)
            ->post(route('developer.backups.create'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Backup database berhasil dibuat dan diverifikasi.');

        $this->assertDatabaseHas('backup_histories', [
            'status' => 'success',
            'user_id' => $developer->id,
        ]);
    }

    public function test_manual_restore_upload_restores_the_application_and_cleans_the_uploaded_file(): void
    {
        $role = Role::query()->create(['name' => 'developer']);
        $developer = User::factory()->create(['role_id' => $role->id]);
        $uploadedPath = null;

        $this->mock(PostgreSqlRestoreService::class, function ($mock) use (&$uploadedPath) {
            $mock->shouldReceive('restoreUploadedToApplication')
                ->once()
                ->withArgs(function (string $path) use (&$uploadedPath): bool {
                    $uploadedPath = $path;

                    return is_file($path) && filesize($path) > 0;
                });
        });

        $this->mock(PostgreSqlBackupService::class, function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->with('pre_restore')
                ->andReturn([
                    'file_path' => $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'pre-restore.dump',
                    'manifest' => ['size_bytes' => 128],
                ]);
        });

        $this->actingAs($developer)
            ->post(route('developer.backups.restore-upload'), [
                'backup_file' => UploadedFile::fake()->createWithContent('database.dump', 'valid-postgresql-archive-fixture'),
                'restore_confirmation' => 'restore',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Database berhasil dipulihkan dari file backup yang diunggah.');

        $this->assertNotNull($uploadedPath);
        $this->assertFileDoesNotExist($uploadedPath);
        $this->assertDatabaseHas('backup_histories', [
            'file_name' => 'pre-restore.dump',
            'status' => 'success',
            'user_id' => $developer->id,
        ]);
    }
}
