<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/CurrencySeeder.json');

        if (!File::exists($jsonPath)) {
            $this->command->error('CurrencySeeder.json not found!');
            return;
        }

        $currencies = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing CurrencySeeder.json: ' . json_last_error_msg());
            return;
        }

        $this->command->info('Seeding currencies from CurrencySeeder.json...');

        foreach ($currencies as $currencyData) {
            // Check if currency already exists
            $existing = Currency::where('code', $currencyData['code'])->first();

            if ($existing) {
                // Update existing currency
                $existing->update([
                    'name' => $currencyData['name'],
                    'symbol' => $currencyData['symbol'],
                    'symbol_2' => $currencyData['symbol_2'],
                    'decimal_separator' => $currencyData['decimal_separator'],
                ]);
                $this->command->line("Updated: {$currencyData['code']} - {$currencyData['name']}");
            } else {
                // Create new currency
                Currency::create([
                    'name' => $currencyData['name'],
                    'code' => $currencyData['code'],
                    'symbol' => $currencyData['symbol'],
                    'symbol_2' => $currencyData['symbol_2'],
                    'decimal_separator' => $currencyData['decimal_separator'],
                ]);
                $this->command->line("Created: {$currencyData['code']} - {$currencyData['name']}");
            }
        }

        $this->command->info('Currency seeding completed!');
    }
}
