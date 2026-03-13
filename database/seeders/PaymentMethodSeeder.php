<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cash', 'QRIS'] as $name) {
            PaymentMethod::query()->updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
