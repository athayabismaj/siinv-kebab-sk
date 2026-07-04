<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\BackupHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    private const MAX_RESTORE_UPLOAD_KB = 102400;
    private const RESTORE_CONFIRMATION = 'RESTORE';
    private const UPLOAD_RESTORE_EXTENSIONS = ['backup', 'dump'];
    private const STORED_RESTORE_EXTENSIONS = ['backup', 'dump', 'sql'];
    private const RESTORE_UPLOAD_MIMES = [
        'application/octet-stream',
        'application/x-tar',
        'application/gzip',
        'application/x-gzip',
        'application/zip',
        'application/vnd.postgresql',
        'application/x-postgresql-backup',
    ];

    public function index()
    {
        $backups = BackupHistory::with('user')
            ->latest()
            ->get();

        $totalBackups = $backups->count();
        $successCount = $backups->where('status', 'success')->count();
        $failedCount = $backups->where('status', 'failed')->count();
        $totalSize = $backups->where('status', 'success')->sum('file_size');
        $lastBackup = $backups->first();

        return view('developer.backups.index', compact(
            'backups',
            'totalBackups',
            'successCount',
            'failedCount',
            'totalSize',
            'lastBackup'
        ));
    }

    public function create()
    {
        $filename = 'backup-' . $this->safeDatabaseNameForFilename() . '-' . Carbon::now()->format('Y-m-d-H-i-s') . '.backup';
        $filePath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $filename;

        try {
            $this->ensureDirectory($this->backupDirectory());

            [$output, $returnVar] = $this->runPostgresCommand(
                $this->buildPgDumpCommand($filePath),
                (string) env('DB_PASSWORD', '')
            );

            if ($returnVar !== 0 || ! File::exists($filePath)) {
                $this->recordFailedBackup($filename, 'Backup gagal. Detail teknis sudah dicatat di log server.');
                Log::error('Backup database developer gagal.', [
                    'file_name' => $filename,
                    'exit_code' => $returnVar,
                    'output' => $output,
                ]);

                return redirect()->back()->with('error', 'Backup gagal diproses. Detail teknis sudah dicatat di log server.');
            }

            BackupHistory::create([
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => File::size($filePath),
                'status' => 'success',
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', 'Backup database berhasil dibuat.');
        } catch (\Throwable $e) {
            $this->recordFailedBackup($filename, 'Backup gagal. Detail teknis sudah dicatat di log server.');
            Log::error('Exception saat membuat backup database developer.', [
                'file_name' => $filename,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Backup gagal diproses. Detail teknis sudah dicatat di log server.');
        }
    }

    public function download($id): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $backup = BackupHistory::findOrFail($id);
        $filePath = (string) $backup->file_path;

        if (
            $backup->status !== 'success'
            || $filePath === ''
            || ! File::exists($filePath)
            || ! $this->isAllowedBackupPath($filePath)
        ) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau tidak valid untuk diunduh.');
        }

        return response()->download($filePath, basename($backup->file_name));
    }

    /**
     * Restore database dari file backup yang sudah ada di riwayat.
     */
    public function restore(Request $request, $id)
    {
        if (! $this->hasRestoreConfirmation($request)) {
            return redirect()->back()->with('error', 'Restore dibatalkan. Ketik RESTORE untuk mengonfirmasi proses restore database.');
        }

        $backup = BackupHistory::findOrFail($id);
        $filePath = (string) $backup->file_path;

        if (
            $backup->status !== 'success'
            || $filePath === ''
            || ! File::exists($filePath)
            || ! $this->isAllowedBackupPath($filePath)
            || ! $this->hasAllowedExtension($filePath, self::STORED_RESTORE_EXTENSIONS)
        ) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau tidak valid untuk di-restore.');
        }

        return $this->executeRestore($filePath, basename($backup->file_name));
    }

    /**
     * Restore database dari file yang di-upload manual oleh developer.
     */
    public function restoreUpload(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:' . self::MAX_RESTORE_UPLOAD_KB],
            'restore_confirmation' => ['required', 'string'],
        ]);

        if (! $this->hasRestoreConfirmation($request)) {
            return redirect()->back()->with('error', 'Restore dibatalkan. Ketik RESTORE untuk mengonfirmasi proses restore database.');
        }

        $file = $request->file('backup_file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mimeType = (string) $file->getMimeType();

        if (! in_array($extension, self::UPLOAD_RESTORE_EXTENSIONS, true)) {
            return redirect()->back()->with('error', 'File restore ditolak. Gunakan file backup PostgreSQL dengan ekstensi .backup atau .dump.');
        }

        if ($mimeType !== '' && ! in_array($mimeType, self::RESTORE_UPLOAD_MIMES, true)) {
            return redirect()->back()->with('error', 'File restore ditolak karena tipe file tidak sesuai.');
        }

        $storedName = 'restore-' . Str::uuid()->toString() . '.' . $extension;
        $storedPath = $file->storeAs('backups/restores', $storedName, 'local');

        if (! $storedPath) {
            Log::error('Upload restore database gagal disimpan.', [
                'original_extension' => $extension,
                'mime_type' => $mimeType,
            ]);

            return redirect()->back()->with('error', 'File restore gagal disimpan. Silakan coba lagi.');
        }

        $filePath = Storage::disk('local')->path($storedPath);

        return $this->executeRestore($filePath, $storedName);
    }

    /**
     * Eksekusi pg_restore dari file path yang diberikan.
     */
    private function executeRestore(string $filePath, string $fileName)
    {
        if (
            ! File::exists($filePath)
            || ! $this->isAllowedBackupPath($filePath)
            || ! $this->hasAllowedExtension($filePath, self::STORED_RESTORE_EXTENSIONS)
        ) {
            return redirect()->back()->with('error', 'File backup tidak valid untuk di-restore.');
        }

        if (! $this->isReadablePostgresArchive($filePath)) {
            return redirect()->back()->with('error', 'File backup tidak valid atau tidak dapat dibaca.');
        }

        try {
            [$output, $returnVar] = $this->runPostgresCommand(
                $this->buildPgRestoreCommand($filePath),
                (string) env('DB_PASSWORD', '')
            );

            if ($returnVar !== 0) {
                Log::error('Restore database developer gagal.', [
                    'file_name' => $fileName,
                    'exit_code' => $returnVar,
                    'output' => $output,
                ]);

                if ($this->hasOnlyNonFatalRestoreWarnings($output)) {
                    return redirect()->back()->with('success', 'Database berhasil di-restore dengan peringatan. Detail teknis sudah dicatat di log server.');
                }

                return redirect()->back()->with('error', 'Restore gagal diproses. Detail teknis sudah dicatat di log server.');
            }

            return redirect()->back()->with('success', 'Database berhasil di-restore dari "' . $this->safeDisplayName($fileName) . '".');
        } catch (\Throwable $e) {
            Log::error('Exception saat restore database developer.', [
                'file_name' => $fileName,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Restore gagal diproses. Detail teknis sudah dicatat di log server.');
        }
    }

    private function buildPgDumpCommand(string $filePath): string
    {
        return implode(' ', [
            escapeshellarg((string) env('PG_DUMP_PATH', 'pg_dump')),
            '-h',
            escapeshellarg((string) env('DB_HOST', '127.0.0.1')),
            '-p',
            escapeshellarg((string) env('DB_PORT', '5432')),
            '-U',
            escapeshellarg((string) env('DB_USERNAME')),
            '-F',
            'c',
            '-b',
            '-v',
            '-f',
            escapeshellarg($filePath),
            escapeshellarg((string) env('DB_DATABASE')),
        ]);
    }

    private function buildPgRestoreCommand(string $filePath): string
    {
        return implode(' ', [
            escapeshellarg((string) env('PG_RESTORE_PATH', 'pg_restore')),
            '-h',
            escapeshellarg((string) env('DB_HOST', '127.0.0.1')),
            '-p',
            escapeshellarg((string) env('DB_PORT', '5432')),
            '-U',
            escapeshellarg((string) env('DB_USERNAME')),
            '-d',
            escapeshellarg((string) env('DB_DATABASE')),
            '--clean',
            '--if-exists',
            '-v',
            escapeshellarg($filePath),
        ]);
    }

    private function buildPgRestoreListCommand(string $filePath): string
    {
        return implode(' ', [
            escapeshellarg((string) env('PG_RESTORE_PATH', 'pg_restore')),
            '--list',
            escapeshellarg($filePath),
        ]);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function runPostgresCommand(string $command, string $dbPassword): array
    {
        putenv('PGPASSWORD=' . $dbPassword);

        try {
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            return [implode("\n", $output), $returnVar];
        } finally {
            putenv('PGPASSWORD');
        }
    }

    private function isReadablePostgresArchive(string $filePath): bool
    {
        [$output, $returnVar] = $this->runPostgresCommand(
            $this->buildPgRestoreListCommand($filePath),
            (string) env('DB_PASSWORD', '')
        );

        if ($returnVar === 0) {
            return true;
        }

        Log::warning('File restore database gagal validasi pg_restore --list.', [
            'file_name' => basename($filePath),
            'exit_code' => $returnVar,
            'output' => $output,
        ]);

        return false;
    }

    private function hasOnlyNonFatalRestoreWarnings(string $output): bool
    {
        $normalized = strtolower($output);

        return $normalized !== ''
            && ! str_contains($normalized, 'fatal')
            && ! str_contains($normalized, 'could not connect')
            && ! str_contains($normalized, 'password authentication failed')
            && ! str_contains($normalized, 'permission denied');
    }

    private function hasRestoreConfirmation(Request $request): bool
    {
        return strtoupper(trim((string) $request->input('restore_confirmation'))) === self::RESTORE_CONFIRMATION;
    }

    private function hasAllowedExtension(string $path, array $allowedExtensions): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowedExtensions, true);
    }

    private function isAllowedBackupPath(string $filePath): bool
    {
        $resolvedPath = realpath($filePath);
        if (! $resolvedPath) {
            return false;
        }

        foreach ([$this->backupDirectory(), $this->legacyBackupDirectory(), Storage::disk('local')->path('backups')] as $directory) {
            $basePath = realpath($directory);
            if ($basePath && str_starts_with($resolvedPath, rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function ensureDirectory(string $path): void
    {
        if (! File::exists($path)) {
            File::makeDirectory($path, 0750, true);
        }
    }

    private function backupDirectory(): string
    {
        return storage_path('app/private/backups');
    }

    private function legacyBackupDirectory(): string
    {
        return storage_path('app/backups');
    }

    private function safeDatabaseNameForFilename(): string
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) env('DB_DATABASE', 'database')) ?: 'database';
    }

    private function safeDisplayName(string $fileName): string
    {
        return Str::limit(basename($fileName), 120, '');
    }

    private function recordFailedBackup(string $fileName, string $message): void
    {
        BackupHistory::create([
            'file_name' => $fileName,
            'status' => 'failed',
            'error_message' => $message,
            'user_id' => Auth::id(),
        ]);
    }
}
