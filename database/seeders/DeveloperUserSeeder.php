<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Role;

class DeveloperUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan role 'developer' sudah ada
        $developerRole = Role::firstOrCreate(
            ['name' => 'developer'],
            [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // 2. Buat user superadmin (developer) jika belum ada
        $developerUser = DB::table('users')->where('username', 'superadmin')->first();

        if (!$developerUser) {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'developer@skkebab.my.id',
                'password' => Hash::make('developer123'),
                'role_id' => $developerRole->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
