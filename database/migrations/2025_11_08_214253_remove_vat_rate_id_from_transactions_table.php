<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration:
     * 1. Migrates data from vat_rate_id to vat_profile_id (matching by rate percentage)
     * 2. Removes the vat_rate_id column and foreign key
     */
    public function up(): void
    {
        // First, migrate existing vat_rate_id data to vat_profile_id
        // Match VatRate by percentage to VatProfile
        // Use a loop approach for SQLite compatibility
        $transactions = DB::table('transactions')
            ->whereNotNull('vat_rate_id')
            ->whereNull('vat_profile_id')
            ->get();

        foreach ($transactions as $transaction) {
            $vatRate = DB::table('vat_rates')->where('id', $transaction->vat_rate_id)->first();
            if ($vatRate) {
                // Find matching VAT profile by percentage
                $vatProfile = DB::table('vat_profiles')
                    ->whereRaw('CAST(percentage AS DECIMAL(5,2)) = CAST(? AS DECIMAL(5,2))', [$vatRate->rate])
                    ->first();

                if ($vatProfile) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['vat_profile_id' => $vatProfile->id]);
                }
            }
        }

        // For transactions with vat_rate_id that couldn't be matched,
        // use the default VAT profile from vessel settings
        $unmatchedTransactions = DB::table('transactions')
            ->whereNotNull('vat_rate_id')
            ->whereNull('vat_profile_id')
            ->where('type', 'income')
            ->get();

        foreach ($unmatchedTransactions as $transaction) {
            $vesselSetting = DB::table('vessel_settings')
                ->where('vessel_id', $transaction->vessel_id)
                ->whereNotNull('vat_profile_id')
                ->first();

            if ($vesselSetting) {
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['vat_profile_id' => $vesselSetting->vat_profile_id]);
            }
        }

        // Now remove the foreign key constraint and column
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['vat_rate_id']);
            $table->dropColumn('vat_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('vat_rate_id')->nullable()->after('vat_profile_id')->constrained('vat_rates')->onDelete('set null');
        });
    }
};
