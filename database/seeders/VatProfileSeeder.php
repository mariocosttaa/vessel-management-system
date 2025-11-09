<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\VatProfile;
use Illuminate\Database\Seeder;

class VatProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing VAT profiles
        VatProfile::truncate();

        // Get all countries
        $countries = Country::all()->keyBy('code');

        // Common VAT rates by country code (standard rates)
        // Source: Common VAT rates as of 2024
        $vatRates = [
            // European Union
            'PT' => ['name' => 'IVA Normal', 'percentage' => 23.00, 'code' => 'IVA'],
            'ES' => ['name' => 'IVA General', 'percentage' => 21.00, 'code' => 'IVA'],
            'FR' => ['name' => 'TVA Standard', 'percentage' => 20.00, 'code' => 'TVA'],
            'DE' => ['name' => 'MwSt Standard', 'percentage' => 19.00, 'code' => 'MwSt'],
            'IT' => ['name' => 'IVA Standard', 'percentage' => 22.00, 'code' => 'IVA'],
            'NL' => ['name' => 'BTW Hoog', 'percentage' => 21.00, 'code' => 'BTW'],
            'BE' => ['name' => 'TVA Standard', 'percentage' => 21.00, 'code' => 'TVA'],
            'AT' => ['name' => 'USt Standard', 'percentage' => 20.00, 'code' => 'USt'],
            'GR' => ['name' => 'ΦΠΑ Standard', 'percentage' => 24.00, 'code' => 'ΦΠΑ'],
            'IE' => ['name' => 'VAT Standard', 'percentage' => 23.00, 'code' => 'VAT'],
            'DK' => ['name' => 'Moms Standard', 'percentage' => 25.00, 'code' => 'Moms'],
            'SE' => ['name' => 'Moms Standard', 'percentage' => 25.00, 'code' => 'Moms'],
            'FI' => ['name' => 'ALV Yleinen', 'percentage' => 24.00, 'code' => 'ALV'],
            'PL' => ['name' => 'VAT Standard', 'percentage' => 23.00, 'code' => 'VAT'],
            'CZ' => ['name' => 'DPH Standard', 'percentage' => 21.00, 'code' => 'DPH'],
            'HU' => ['name' => 'ÁFA Általános', 'percentage' => 27.00, 'code' => 'ÁFA'],
            'RO' => ['name' => 'TVA Standard', 'percentage' => 19.00, 'code' => 'TVA'],
            'BG' => ['name' => 'ДДС Standard', 'percentage' => 20.00, 'code' => 'ДДС'],
            'HR' => ['name' => 'PDV Standard', 'percentage' => 25.00, 'code' => 'PDV'],
            'SI' => ['name' => 'DDV Standard', 'percentage' => 22.00, 'code' => 'DDV'],
            'SK' => ['name' => 'DPH Standard', 'percentage' => 20.00, 'code' => 'DPH'],
            'EE' => ['name' => 'KM Standard', 'percentage' => 20.00, 'code' => 'KM'],
            'LV' => ['name' => 'PVN Standard', 'percentage' => 21.00, 'code' => 'PVN'],
            'LT' => ['name' => 'PVM Standard', 'percentage' => 21.00, 'code' => 'PVM'],
            'LU' => ['name' => 'TVA Standard', 'percentage' => 17.00, 'code' => 'TVA'],
            'MT' => ['name' => 'VAT Standard', 'percentage' => 18.00, 'code' => 'VAT'],
            'CY' => ['name' => 'ΦΠΑ Standard', 'percentage' => 19.00, 'code' => 'ΦΠΑ'],

            // Other European countries
            'GB' => ['name' => 'VAT Standard', 'percentage' => 20.00, 'code' => 'VAT'],
            'CH' => ['name' => 'MwSt Standard', 'percentage' => 7.70, 'code' => 'MwSt'],
            'NO' => ['name' => 'MVA Standard', 'percentage' => 25.00, 'code' => 'MVA'],
            'IS' => ['name' => 'VSK Standard', 'percentage' => 24.00, 'code' => 'VSK'],
            'TR' => ['name' => 'KDV Standard', 'percentage' => 20.00, 'code' => 'KDV'],
            'RU' => ['name' => 'НДС Standard', 'percentage' => 20.00, 'code' => 'НДС'],
            'UA' => ['name' => 'ПДВ Standard', 'percentage' => 20.00, 'code' => 'ПДВ'],
            'AL' => ['name' => 'TVSH Standard', 'percentage' => 20.00, 'code' => 'TVSH'],
            'MK' => ['name' => 'ДДВ Standard', 'percentage' => 18.00, 'code' => 'ДДВ'],
            'RS' => ['name' => 'ПДВ Standard', 'percentage' => 20.00, 'code' => 'ПДВ'],
            'BA' => ['name' => 'PDV Standard', 'percentage' => 17.00, 'code' => 'PDV'],
            'ME' => ['name' => 'PDV Standard', 'percentage' => 21.00, 'code' => 'PDV'],

            // Americas
            'US' => ['name' => 'Sales Tax', 'percentage' => 0.00, 'code' => 'TAX'], // Varies by state
            'CA' => ['name' => 'GST/HST', 'percentage' => 0.00, 'code' => 'GST'], // Varies by province
            'MX' => ['name' => 'IVA Standard', 'percentage' => 16.00, 'code' => 'IVA'],
            'BR' => ['name' => 'ICMS Standard', 'percentage' => 0.00, 'code' => 'ICMS'], // Varies by state
            'AR' => ['name' => 'IVA Standard', 'percentage' => 21.00, 'code' => 'IVA'],
            'CL' => ['name' => 'IVA Standard', 'percentage' => 19.00, 'code' => 'IVA'],
            'CO' => ['name' => 'IVA Standard', 'percentage' => 19.00, 'code' => 'IVA'],
            'PE' => ['name' => 'IGV Standard', 'percentage' => 18.00, 'code' => 'IGV'],
            'VE' => ['name' => 'IVA Standard', 'percentage' => 16.00, 'code' => 'IVA'],
            'UY' => ['name' => 'IVA Standard', 'percentage' => 22.00, 'code' => 'IVA'],
            'PY' => ['name' => 'IVA Standard', 'percentage' => 10.00, 'code' => 'IVA'],
            'BO' => ['name' => 'IVA Standard', 'percentage' => 13.00, 'code' => 'IVA'],
            'EC' => ['name' => 'IVA Standard', 'percentage' => 12.00, 'code' => 'IVA'],

            // Asia
            'CN' => ['name' => 'VAT Standard', 'percentage' => 13.00, 'code' => 'VAT'],
            'JP' => ['name' => '消費税 Standard', 'percentage' => 10.00, 'code' => '消費税'],
            'KR' => ['name' => 'VAT Standard', 'percentage' => 10.00, 'code' => 'VAT'],
            'IN' => ['name' => 'GST Standard', 'percentage' => 18.00, 'code' => 'GST'],
            'TH' => ['name' => 'VAT Standard', 'percentage' => 7.00, 'code' => 'VAT'],
            'SG' => ['name' => 'GST Standard', 'percentage' => 9.00, 'code' => 'GST'],
            'MY' => ['name' => 'SST Standard', 'percentage' => 10.00, 'code' => 'SST'],
            'ID' => ['name' => 'PPN Standard', 'percentage' => 11.00, 'code' => 'PPN'],
            'PH' => ['name' => 'VAT Standard', 'percentage' => 12.00, 'code' => 'VAT'],
            'VN' => ['name' => 'VAT Standard', 'percentage' => 10.00, 'code' => 'VAT'],
            'BD' => ['name' => 'VAT Standard', 'percentage' => 15.00, 'code' => 'VAT'],
            'PK' => ['name' => 'GST Standard', 'percentage' => 17.00, 'code' => 'GST'],
            'LK' => ['name' => 'VAT Standard', 'percentage' => 18.00, 'code' => 'VAT'],
            'IL' => ['name' => 'מעמ Standard', 'percentage' => 17.00, 'code' => 'מעמ'],
            'AE' => ['name' => 'VAT Standard', 'percentage' => 5.00, 'code' => 'VAT'],
            'SA' => ['name' => 'VAT Standard', 'percentage' => 15.00, 'code' => 'VAT'],
            'QA' => ['name' => 'VAT Standard', 'percentage' => 0.00, 'code' => 'VAT'],
            'KW' => ['name' => 'VAT Standard', 'percentage' => 0.00, 'code' => 'VAT'],
            'BH' => ['name' => 'VAT Standard', 'percentage' => 0.00, 'code' => 'VAT'],
            'OM' => ['name' => 'VAT Standard', 'percentage' => 5.00, 'code' => 'VAT'],

            // Africa
            'ZA' => ['name' => 'VAT Standard', 'percentage' => 15.00, 'code' => 'VAT'],
            'NG' => ['name' => 'VAT Standard', 'percentage' => 7.50, 'code' => 'VAT'],
            'EG' => ['name' => 'VAT Standard', 'percentage' => 14.00, 'code' => 'VAT'],
            'KE' => ['name' => 'VAT Standard', 'percentage' => 16.00, 'code' => 'VAT'],
            'GH' => ['name' => 'VAT Standard', 'percentage' => 12.50, 'code' => 'VAT'],
            'MA' => ['name' => 'TVA Standard', 'percentage' => 20.00, 'code' => 'TVA'],
            'TN' => ['name' => 'TVA Standard', 'percentage' => 19.00, 'code' => 'TVA'],
            'DZ' => ['name' => 'TVA Standard', 'percentage' => 19.00, 'code' => 'TVA'],
            'AO' => ['name' => 'IVA Standard', 'percentage' => 14.00, 'code' => 'IVA'],

            // Oceania
            'AU' => ['name' => 'GST Standard', 'percentage' => 10.00, 'code' => 'GST'],
            'NZ' => ['name' => 'GST Standard', 'percentage' => 15.00, 'code' => 'GST'],
        ];

        $defaultSet = false;

        // Create VAT profiles for countries
        foreach ($vatRates as $countryCode => $vatData) {
            $country = $countries->get($countryCode);

            if (!$country) {
                continue;
            }

            $isDefault = !$defaultSet && in_array($countryCode, ['PT', 'ES', 'FR', 'DE', 'IT']); // Default to Portugal if exists
            if ($isDefault) {
                $defaultSet = true;
            }

            VatProfile::create([
                'country_id' => $country->id,
                'name' => $vatData['name'],
                'percentage' => $vatData['percentage'],
                'code' => $vatData['code'],
                'description' => "Standard VAT rate for {$country->name}",
                'is_default' => $isDefault,
                'is_active' => true,
            ]);
        }

        // Create a generic "No VAT" profile for countries without VAT
        VatProfile::create([
            'country_id' => null,
            'name' => 'No VAT',
            'percentage' => 0.00,
            'code' => 'N/A',
            'description' => 'No VAT applicable',
            'is_default' => !$defaultSet, // Set as default if no country default was set
            'is_active' => true,
        ]);

        $this->command->info('VAT profiles seeded successfully.');
    }
}
