<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BackupFilesystem
{
    public function root(): string
    {
        $root = rtrim(Storage::disk((string) config('backup.disk'))->path(''), DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists($root);

        return $root;
    }

    public function path(string $relativePath): string
    {
        $segments = preg_split('#[\\\\/]#', $relativePath) ?: [];

        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_contains($relativePath, ':') || in_array('..', $segments, true)) {
            throw new RuntimeException('Backup path is not valid.');
        }

        return $this->root().DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, array_filter($segments, fn (string $segment) => $segment !== ''));
    }

    public function temporaryDirectory(string $backupId): string
    {
        return $this->path(trim((string) config('backup.temporary_directory'), '/').'/'.$this->validBackupId($backupId));
    }

    public function artifactDirectory(string $backupId): string
    {
        return $this->path(trim((string) config('backup.directory'), '/').'/artifacts/'.$this->validBackupId($backupId));
    }

    public function publish(string $temporaryDirectory, string $artifactDirectory): void
    {
        if (! is_dir($temporaryDirectory) || is_link($temporaryDirectory) || file_exists($artifactDirectory)) {
            throw new RuntimeException('Backup artifact cannot be published.');
        }

        File::ensureDirectoryExists(dirname($artifactDirectory));

        if (! @rename($temporaryDirectory, $artifactDirectory)) {
            throw new RuntimeException('Backup artifact cannot be published.');
        }
    }

    public function deleteDirectory(string $directory): void
    {
        if (is_dir($directory) && ! is_link($directory)) {
            File::deleteDirectory($directory);
        }

        $root = $this->root();
        $parent = dirname($directory);

        while ($parent !== $root && str_starts_with($parent, $root) && is_dir($parent) && ! is_link($parent) && File::isEmptyDirectory($parent)) {
            @rmdir($parent);
            $parent = dirname($parent);
        }
    }

    private function validBackupId(string $backupId): string
    {
        if (! preg_match('/^[a-zA-Z0-9-]+$/', $backupId)) {
            throw new RuntimeException('Backup identifier is not valid.');
        }

        return $backupId;
    }
}
