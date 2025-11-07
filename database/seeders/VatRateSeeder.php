<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VatRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vatRates = [
            ['name' => 'IVA Normal', 'rate' => 23.00, 'is_default' => true, 'is_active' => true],
            ['name' => 'IVA Intermédio', 'rate' => 13.00, 'is_default' => false, 'is_active' => true],
            ['name' => 'IVA Reduzido', 'rate' => 6.00, 'is_default' => false, 'is_active' => true],
            ['name' => 'Isento', 'rate' => 0.00, 'is_default' => false, 'is_active' => true],
        ];

        foreach ($vatRates as $rate) {
            \App\Models\VatRate::updateOrCreate(
                ['name' => $rate['name']],
                $rate
            );
        }
    }
}
