<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\VesselSetting;
use App\Models\Transaction;
use App\Models\Vessel;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update transactions to use vessel's default currency from vessel_settings
        // For each vessel, get its vessel_settings currency and update all transactions
        $vessels = Vessel::all();

        foreach ($vessels as $vessel) {
            $vesselSetting = VesselSetting::getForVessel($vessel->id);
            $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code;

            if ($defaultCurrency) {
                // Update all transactions for this vessel that have EUR (or null) to use vessel default currency
                Transaction::where('vessel_id', $vessel->id)
                    ->where(function($query) {
                        $query->where('currency', 'EUR')
                              ->orWhereNull('currency');
                    })
                    ->update(['currency' => $defaultCurrency]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert transactions back to EUR (we can't know the original currency)
        // So we'll just set them back to EUR
        Transaction::where('currency', '!=', 'EUR')
            ->update(['currency' => 'EUR']);
    }
};
