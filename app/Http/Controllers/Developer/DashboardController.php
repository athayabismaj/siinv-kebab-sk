<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\BackupHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $laravelVersion = app()->version();
        $phpVersion = phpversion();
        $databaseSize = $this->getDatabaseSize();
        $appEnv = app()->environment();
        $debugMode = config('app.debug') ? 'Aktif' : 'Nonaktif';
        $roleCounts = Role::query()
            ->withCount(['users' => fn ($query) => $query->whereNull('deleted_at')])
            ->pluck('users_count', 'name');
        $totalUsers = User::query()->count();
        $totalBackups = BackupHistory::query()->count();
        $successfulBackups = BackupHistory::query()->where('status', 'success')->count();
        $failedBackups = BackupHistory::query()->where('status', 'failed')->count();
        $lastBackup = BackupHistory::with('user')->latest()->first();
        $latestBackups = BackupHistory::with('user')->latest()->take(5)->get();

        return view('developer.dashboard', compact(
            'laravelVersion',
            'phpVersion',
            'databaseSize',
            'appEnv',
            'debugMode',
            'roleCounts',
            'totalUsers',
            'totalBackups',
            'successfulBackups',
            'failedBackups',
            'lastBackup',
            'latestBackups'
        ));
    }

    public function clearCache()
    {
        try {
            Artisan::call('optimize:clear');
            return redirect()->back()->with('success', 'Cache sistem, views, dan route berhasil dibersihkan.');
        } catch (\Exception $e) {
            Log::error('Developer clear cache gagal.', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal membersihkan cache. Detail teknis sudah dicatat di log server.');
        }
    }

    // Backup method removed, moved to BackupController

    private function getDatabaseSize()
    {
        try {
            $dbName = env('DB_DATABASE');
            $result = \DB::select("SELECT pg_size_pretty(pg_database_size(?)) as size", [$dbName]);
            return $result[0]->size ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}
