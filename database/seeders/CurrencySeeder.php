<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Euro
        Currency::create([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => '€',
            'symbol_2' => 'EUR',
            'decimal_separator' => 2,
        ]);

        // Dollar
        Currency::create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'symbol_2' => 'USD',
            'decimal_separator' => 2,
        ]);

        // Real
        Currency::create([
            'name' => 'Brazilian Real',
            'code' => 'BRL',
            'symbol' => 'R$',
            'symbol_2' => 'BRL',
            'decimal_separator' => 2,
        ]);

        // Kwanza Angolano
        Currency::create([
            'name' => 'Angolan Kwanza',
            'code' => 'AOA',
            'symbol' => 'Kz',
            'symbol_2' => 'AOA',
            'decimal_separator' => 2,
        ]);

        // British Pound
        Currency::create([
            'name' => 'British Pound',
            'code' => 'GBP',
            'symbol' => '£',
            'symbol_2' => 'GBP',
            'decimal_separator' => 2,
        ]);

    }
}
