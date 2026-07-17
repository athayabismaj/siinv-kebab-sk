<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\BackupHistory;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function restore(Request $request, $id, PostgreSqlRestoreService $restoreService)
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
            $restoreService->drill($filePath);

            return redirect()->back()->with('success', 'Verifikasi restore pada database sementara berhasil. Database aplikasi tidak diubah.');
        } catch (\Throwable $exception) {
            Log::warning('Developer backup restore drill failed.', [
                'operation' => 'restore_drill',
                'backup_id' => $backup->id,
                'exception' => $exception::class,
            ]);

            return redirect()->back()->with('error', 'File backup tidak valid atau tidak dapat dibaca.');
        }
    }

    public function restoreUpload(Request $request)
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

        return redirect()->back()->with('error', 'File backup tidak valid atau tidak dapat dibaca.');
    }

    private function hasRestoreConfirmation(Request $request): bool
    {
        return strtolower(trim((string) $request->input('restore_confirmation'))) === self::RESTORE_CONFIRMATION;
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
