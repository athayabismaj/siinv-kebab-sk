<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $laravelVersion = app()->version();
        $phpVersion = phpversion();
        $databaseSize = $this->getDatabaseSize();

        return view('developer.dashboard', compact('laravelVersion', 'phpVersion', 'databaseSize'));
    }

    public function clearCache()
    {
        try {
            Artisan::call('optimize:clear');
            return redirect()->back()->with('success', 'Cache sistem, views, dan route berhasil dibersihkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
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
