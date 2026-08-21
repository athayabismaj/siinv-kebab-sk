<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cash', 'QRIS'] as $name) {
            PaymentMethod::withTrashed()->updateOrCreate(
                ['name' => $name],
                ['deleted_at' => null],
            );
        }
    }
}
