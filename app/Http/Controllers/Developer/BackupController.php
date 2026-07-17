<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\BackupHistory;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    private const MAX_RESTORE_UPLOAD_KB = 102400;
    private const RESTORE_CONFIRMATION = 'restore';
    private const UPLOAD_RESTORE_EXTENSIONS = ['backup', 'dump'];
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
        $totalBackups = BackupHistory::query()->count();
        $successCount = BackupHistory::query()->where('status', 'success')->count();
        $failedCount = BackupHistory::query()->where('status', 'failed')->count();
        $totalSize = BackupHistory::query()->where('status', 'success')->pluck('file_size')->sum(fn ($size) => is_numeric($size) ? (int) $size : 0);
        $lastBackup = BackupHistory::with('user')->latest()->first();
        $backups = BackupHistory::with('user')->latest()->paginate(10)->withQueryString();

        return view('developer.backups.index', compact('backups', 'totalBackups', 'successCount', 'failedCount', 'totalSize', 'lastBackup'));
    }

    public function create(PostgreSqlBackupService $backupService)
    {
        try {
            $backup = $backupService->create('manual');

            BackupHistory::query()->create([
                'file_name' => basename($backup['file_path']),
                'file_path' => $backup['file_path'],
                'file_size' => $backup['manifest']['size_bytes'],
                'status' => 'success',
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', 'Backup database berhasil dibuat dan diverifikasi.');
        } catch (\Throwable $exception) {
            $this->recordFailedBackup();
            Log::warning('Developer database backup failed.', [
                'operation' => 'backup',
                'exception' => $exception::class,
            ]);

            return redirect()->back()->with('error', 'Backup gagal diproses. Detail teknis sudah dicatat di log server.');
        }
    }

    public function download($id): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $backup = BackupHistory::query()->findOrFail($id);
        $filePath = (string) $backup->file_path;

        if ($backup->status !== 'success' || ! $this->isAllowedBackupPath($filePath)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau tidak valid untuk diunduh.');
        }

        return response()->download($filePath, basename((string) $backup->file_name));
    }

    public function restore(Request $request, $id, PostgreSqlRestoreService $restoreService, PostgreSqlBackupService $backupService)
    {
        if (! $this->hasRestoreConfirmation($request)) {
            return redirect()->back()->with('error', 'Restore dibatalkan. Ketik RESTORE untuk mengonfirmasi proses restore database.');
        }

        $backup = BackupHistory::query()->findOrFail($id);
        $filePath = (string) $backup->file_path;

        if ($backup->status !== 'success' || ! $this->isAllowedBackupPath($filePath)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau tidak valid untuk di-restore.');
        }

        try {
            $this->restoreApplication($backupService, fn () => $restoreService->restoreToApplication($filePath));

            return redirect()->back()->with('success', 'Database berhasil dipulihkan dari file backup.');
        } catch (\Throwable $exception) {
            Log::warning('Developer backup restore failed.', [
                'operation' => 'restore',
                'backup_id' => $backup->id,
                'exception' => $exception::class,
            ]);

            return redirect()->back()->with('error', 'File backup tidak valid atau tidak dapat dibaca.');
        }
    }

    public function restoreUpload(Request $request, PostgreSqlRestoreService $restoreService, PostgreSqlBackupService $backupService)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:'.self::MAX_RESTORE_UPLOAD_KB],
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

        $uploadDirectory = trim((string) config('backup.temporary_directory'), '/').'/restore-upload-'.Str::uuid();
        $uploadPath = Storage::disk((string) config('backup.disk'))->path($file->storeAs(
            $uploadDirectory,
            'database.'.$extension,
            (string) config('backup.disk'),
        ));

        try {
            $this->restoreApplication($backupService, fn () => $restoreService->restoreUploadedToApplication($uploadPath));

            return redirect()->back()->with('success', 'Database berhasil dipulihkan dari file backup yang diunggah.');
        } catch (\Throwable $exception) {
            Log::warning('Developer uploaded backup restore failed.', [
                'operation' => 'restore_upload',
                'exception' => $exception::class,
            ]);

            return redirect()->back()->with('error', 'File backup tidak valid atau tidak dapat dibaca.');
        } finally {
            Storage::disk((string) config('backup.disk'))->deleteDirectory($uploadDirectory);
        }
    }

    private function hasRestoreConfirmation(Request $request): bool
    {
        return strtolower(trim((string) $request->input('restore_confirmation'))) === self::RESTORE_CONFIRMATION;
    }

    /** @param callable():void $restore */
    private function restoreApplication(PostgreSqlBackupService $backupService, callable $restore): void
    {
        $wasInMaintenanceMode = app()->isDownForMaintenance();

        $snapshot = $backupService->create('pre_restore');

        if (! $wasInMaintenanceMode && Artisan::call('down') !== 0) {
            throw new \RuntimeException('Unable to enable maintenance mode for restore.');
        }

        try {
            $restore();

            if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new \RuntimeException('Unable to migrate the restored database.');
            }

            BackupHistory::query()->create([
                'file_name' => basename($snapshot['file_path']),
                'file_path' => $snapshot['file_path'],
                'file_size' => $snapshot['manifest']['size_bytes'],
                'status' => 'success',
                'user_id' => Auth::id(),
            ]);
        } catch (\Throwable $exception) {
            Log::critical('Database restore failed after a pre-restore snapshot was created.', [
                'operation' => 'restore',
                'pre_restore_backup' => basename($snapshot['file_path']),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } finally {
            if (! $wasInMaintenanceMode) {
                Artisan::call('up');
            }
        }
    }

    private function isAllowedBackupPath(string $filePath): bool
    {
        $resolvedPath = realpath($filePath);
        $backupRoot = realpath(Storage::disk('local')->path('backups'));

        return $resolvedPath !== false
            && $backupRoot !== false
            && ! is_link($resolvedPath)
            && str_starts_with($resolvedPath, rtrim($backupRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            && File::isFile($resolvedPath);
    }

    private function recordFailedBackup(): void
    {
        try {
            BackupHistory::query()->create([
                'file_name' => 'failed-'.now()->format('YmdHis'),
                'status' => 'failed',
                'error_message' => 'Backup gagal. Detail teknis sudah dicatat di log server.',
                'user_id' => Auth::id(),
            ]);
        } catch (\Throwable) {
            // Logging remains available when the history database cannot be written.
        }
    }
}
