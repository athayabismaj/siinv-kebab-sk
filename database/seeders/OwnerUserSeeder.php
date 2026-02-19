<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = DB::table('roles')
            ->where('name', 'owner')
            ->first();

        if ($ownerRole) {
            DB::table('users')->insert([
                'name' => 'Owner Kebab SK',
                'username' => 'owner',
                'password' => Hash::make('password123'),
                'role_id' => $ownerRole->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
