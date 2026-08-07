<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $kasirRole = Role::firstOrCreate(['name' => 'kasir']);

        // Create branches
        $umkBranch = Branch::firstOrCreate(
            ['code' => 'UMK'],
            ['name' => 'UMK', 'address' => 'Cabang UMK']
        );

        $pkgBranch = Branch::firstOrCreate(
            ['code' => 'PKG'],
            ['name' => 'Pekeng', 'address' => 'Cabang Pekeng']
        );

        $branches = Branch::all();

        foreach ($branches as $branch) {
            if ($branch->code === 'UMK') {
                $name = 'santoso';
                $username = 'santoso';
                $email = 'simasukasi69@gmail.com';
            } else if ($branch->code === 'PKG') {
                $name = 'cahyo pratomo';
                $username = 'cahyo_p';
                $email = 'larongaming300@gmail.com';
            } else {
                continue;
            }

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt('kebabsk123'),
                    'role_id' => $kasirRole->id,
                    'branch_id' => $branch->id,
                ]
            );

            if (DB::getSchemaBuilder()->hasTable('branch_user')) {
                DB::table('branch_user')->updateOrInsert(['user_id' => $user->id, 'branch_id' => $branch->id]);
            }
        }
    }
}
