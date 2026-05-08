<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-data {action : export atau import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export/Import data ke bentuk JSON untuk melewati masalah PostgreSQL beda versi';

    // Urutan tabel dari master ke transaksi
    protected $tables = [
        'roles',
        'users',
        'ingredient_categories',
        'ingredients',
        'menu_categories',
        'menus',
        'menu_variants',
        'menu_variant_ingredients',
        'payment_methods',
        'transactions',
        'transaction_details',
        'stock_logs',
        'daily_stock_sessions',
        'daily_stock_items',
        'cashflow_entries',
        'period_closings',
        'daily_targets'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $path = storage_path('app/backups/universal_data.json');

        if ($action === 'export') {
            $this->info("Mengekstrak data dari PostgreSQL lokal Anda...");
            $data = [];
            foreach ($this->tables as $table) {
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    $data[$table] = DB::table($table)->get()->toArray();
                    $this->line("Exported " . count($data[$table]) . " baris dari tabel {$table}.");
                }
            }

            if (!File::exists(storage_path('app/backups/'))) {
                File::makeDirectory(storage_path('app/backups/'), 0755, true);
            }

            File::put($path, json_encode($data, JSON_PRETTY_PRINT));
            $this->info("✅ Berhasil! Data telah dibungkus menjadi teks murni di:");
            $this->info($path);
            $this->line("Silakan download/copy file universal_data.json ini dan masukkan ke folder storage/app/backups/ di cPanel Anda.");

        } elseif ($action === 'import') {
            if (!File::exists($path)) {
                $this->error("❌ File tidak ditemukan di " . $path);
                $this->line("Pastikan Anda sudah meletakkan file universal_data.json di folder tersebut.");
                return;
            }

            $this->info("Membersihkan data lama di database...");
            try {
                // Hapus semua data sekaligus agar tidak ada error Foreign Key
                DB::statement('TRUNCATE TABLE ' . implode(', ', $this->tables) . ' RESTART IDENTITY CASCADE');
            } catch (\Exception $e) {
                $this->error("Gagal membersihkan tabel: " . $e->getMessage());
            }

            $this->info("Membaca dan memasukkan data JSON ke PostgreSQL 10...");
            $data = json_decode(File::get($path), true);
            
            foreach ($this->tables as $table) {
                if (isset($data[$table]) && count($data[$table]) > 0) {
                    $chunks = array_chunk($data[$table], 500);
                    foreach ($chunks as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                    $this->line("Imported " . count($data[$table]) . " baris ke tabel {$table}.");
                }
            }

            // Reset sequence ID PostgreSQL
            $this->info("Menata ulang ID Sequence...");
            foreach ($this->tables as $table) {
                try {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id)+1 FROM {$table}), 1), false)");
                } catch (\Exception $e) {}
            }

            $this->info("✅ Semua data berhasil di-restore tanpa error beda versi!");
        } else {
            $this->error("Tuliskan action 'export' atau 'import'. Contoh: php artisan app:migrate-data export");
        }
    }
}
