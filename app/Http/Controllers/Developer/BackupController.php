<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BackupController extends Controller
{
    public function index()
    {
        $backups = BackupHistory::with('user')
            ->latest()
            ->get();

        $totalBackups = $backups->count();
        $successCount = $backups->where('status', 'success')->count();
        $failedCount  = $backups->where('status', 'failed')->count();
        $totalSize    = $backups->where('status', 'success')->sum('file_size');
        $lastBackup   = $backups->first();

        return view('developer.backups.index', compact(
            'backups', 'totalBackups', 'successCount', 'failedCount', 'totalSize', 'lastBackup'
        ));
    }

    public function create()
    {
        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '5432');
            
            $filename = 'backup-' . $dbName . '-' . Carbon::now()->format('Y-m-d-H-i-s') . '.sql';
            $backupPath = storage_path('app/backups/');
            
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }
            
            $filePath = $backupPath . $filename;
            
            putenv("PGPASSWORD=" . $dbPassword);
            
            $pgDumpPath = env('PG_DUMP_PATH', 'pg_dump');
            
            // Perintah pg_dump
            $command = "\"{$pgDumpPath}\" -h {$dbHost} -p {$dbPort} -U {$dbUser} -F c -b -v -f \"{$filePath}\" {$dbName}";
            
            exec($command . ' 2>&1', $output, $returnVar);
            $outputStr = implode("\n", $output);
            
            putenv("PGPASSWORD");

            if ($returnVar !== 0) {
                // Catat di database jika gagal
                BackupHistory::create([
                    'file_name' => $filename,
                    'status' => 'failed',
                    'error_message' => $outputStr,
                    'user_id' => Auth::id(),
                ]);

                return redirect()->back()->with('error', "Backup gagal. Pesan Error: \n" . $outputStr);
            }

            // Catat di database jika sukses
            BackupHistory::create([
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => File::size($filePath),
                'status' => 'success',
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', 'Backup database berhasil dibuat.');
            
        } catch (\Exception $e) {
            BackupHistory::create([
                'file_name' => 'Failed-' . Carbon::now()->format('Y-m-d-H-i-s'),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        $backup = BackupHistory::findOrFail($id);

        if ($backup->status !== 'success' || !File::exists($backup->file_path)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau status backup gagal.');
        }

        return response()->download($backup->file_path);
    }

    /**
     * Restore database dari file backup yang sudah ada di riwayat.
     */
    public function restore($id)
    {
        $backup = BackupHistory::findOrFail($id);

        if ($backup->status !== 'success' || !$backup->file_path || !File::exists($backup->file_path)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan atau tidak valid untuk di-restore.');
        }

        return $this->executeRestore($backup->file_path, $backup->file_name);
    }

    /**
     * Restore database dari file yang di-upload manual oleh developer.
     */
    public function restoreUpload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:102400', // Max 100MB
        ]);

        $file = $request->file('backup_file');
        $filename = $file->getClientOriginalName();
        
        $backupPath = storage_path('app/backups/');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filePath = $backupPath . 'upload-' . Carbon::now()->format('Y-m-d-H-i-s') . '-' . $filename;
        $file->move($backupPath, basename($filePath));

        return $this->executeRestore($filePath, $filename);
    }

    /**
     * Eksekusi pg_restore dari file path yang diberikan.
     */
    private function executeRestore(string $filePath, string $fileName)
    {
        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '5432');

            $pgRestorePath = env('PG_RESTORE_PATH', 'pg_restore');

            putenv("PGPASSWORD=" . $dbPassword);

            // pg_restore: --clean untuk drop objects dulu, --if-exists agar tidak error jika belum ada
            $command = "\"{$pgRestorePath}\" -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbName} --clean --if-exists -v \"{$filePath}\"";

            exec($command . ' 2>&1', $output, $returnVar);
            $outputStr = implode("\n", $output);

            putenv("PGPASSWORD");

            if ($returnVar !== 0) {
                // pg_restore sering return code non-zero untuk warning, cek apakah ada fatal error
                $hasFatalError = str_contains(strtolower($outputStr), 'fatal') || str_contains(strtolower($outputStr), 'could not connect');

                if ($hasFatalError) {
                    return redirect()->back()->with('error', "Restore gagal. Pesan Error:\n" . $outputStr);
                }
                
                // Jika hanya warning, anggap berhasil dengan peringatan
                return redirect()->back()->with('success', "Database berhasil di-restore dari \"{$fileName}\" (dengan beberapa peringatan non-fatal).");
            }

            return redirect()->back()->with('success', "Database berhasil di-restore dari \"{$fileName}\".");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal restore database: ' . $e->getMessage());
        }
    }
}
