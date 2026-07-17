<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'UMK',
                'name' => 'UMK',
                'address' => 'Jl. Lkr. Utara, Kayuapu Kulon, Gondangmanis, Kec. Bae, Kabupaten Kudus',
            ],
            [
                'code' => 'PKG',
                'name' => 'Pekeng',
                'address' => 'Jl. Raya Gulang Cilik, Pekeng, Kec. Mejobo, Kabupaten Kudus',
            ],
        ];

        foreach ($branches as $branch) {
            DB::table('branches')->updateOrInsert(
                ['code' => $branch['code']],
                [
                    'name' => $branch['name'],
                    'address' => $branch['address'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}