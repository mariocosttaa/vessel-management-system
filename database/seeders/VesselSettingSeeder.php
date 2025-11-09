<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\VatProfile;
use App\Models\Vessel;
use App\Models\VesselSetting;
use Illuminate\Database\Seeder;

class VesselSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Angola country
        $angola = Country::where('code', 'AO')->first();

        // Get AOA currency
        $aoaCurrency = Currency::where('code', 'AOA')->first();

        // Get Angolan VAT profile (14%)
        $angolanVatProfile = VatProfile::where('country_id', $angola?->id)
            ->where('percentage', 14.00)
            ->first();

        // If Angolan VAT profile doesn't exist, create it
        if (!$angolanVatProfile && $angola) {
            $angolanVatProfile = VatProfile::create([
                'country_id' => $angola->id,
                'name' => 'IVA Angolano',
                'percentage' => 14.00,
                'code' => 'IVA',
                'description' => 'Standard VAT rate for Angola',
                'is_default' => false,
                'is_active' => true,
            ]);
        }

        // Update or create vessel settings for all vessels
        $vessels = Vessel::all();

        foreach ($vessels as $vessel) {
            VesselSetting::updateOrCreate(
                ['vessel_id' => $vessel->id],
                [
                    'country_code' => $angola?->code ?? null,
                    'currency_code' => $aoaCurrency?->code ?? null,
                    'vat_profile_id' => $angolanVatProfile?->id ?? null,
                ]
            );
        }

        $this->command->info('Vessel settings seeded successfully with Angola, AOA, and 14% VAT.');
    }
}

