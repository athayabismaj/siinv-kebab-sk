<?php

namespace Tests\Integration;

use App\Models\BackupHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\Backup\PostgreSqlProcessRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PostgreSqlBackupWebActionTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['FASE4D_PG_HOST', 'FASE4D_PG_DATABASE', 'FASE4D_PG_USERNAME', 'FASE4D_PG_PASSWORD'] as $key) {
            $this->assertNotSame('', (string) env($key), "{$key} must be configured for the explicit PostgreSQL web backup test.");
        }

        $this->storageRoot = storage_path('framework/testing/backup-web-action-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.enabled' => true,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'fase4d',
            'backup.encryption.enabled' => false,
            'database.connections.fase4d' => [
                'driver' => 'pgsql',
                'host' => (string) env('FASE4D_PG_HOST'),
                'port' => (string) env('FASE4D_PG_PORT', '5432'),
                'database' => (string) env('FASE4D_PG_DATABASE'),
                'username' => (string) env('FASE4D_PG_USERNAME'),
                'password' => (string) env('FASE4D_PG_PASSWORD'),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_backup_web_action_creates_a_readable_postgresql_dump(): void
    {
        $role = Role::query()->create(['name' => 'developer']);
        $developer = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($developer)
            ->post(route('developer.backups.create'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Backup database berhasil dibuat dan diverifikasi.');

        $backup = BackupHistory::query()->sole();
        $this->assertSame('success', $backup->status);
        $this->assertFileExists((string) $backup->file_path);
        $this->assertGreaterThan(0, (int) $backup->file_size);

        $inspection = (new PostgreSqlProcessRunner())->run([
            (string) config('backup.pg_restore_path'),
            '--list',
            (string) $backup->file_path,
        ]);

        $this->assertTrue($inspection->successful());
        $this->assertStringContainsString('Format: CUSTOM', $inspection->output());
    }
}
