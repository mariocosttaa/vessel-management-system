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
     * 1. Adds vat_profile_id to recurring_transactions
     * 2. Migrates data from vat_rate_id to vat_profile_id (matching by rate percentage)
     * 3. Removes the vat_rate_id column and foreign key
     */
    public function up(): void
    {
        // First, add vat_profile_id column
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->foreignId('vat_profile_id')->nullable()->after('vat_rate_id')->constrained('vat_profiles')->onDelete('set null');
        });

        // Migrate existing vat_rate_id data to vat_profile_id
        // Match VatRate by percentage to VatProfile
        // Use a loop approach for SQLite compatibility
        $recurringTransactions = DB::table('recurring_transactions')
            ->whereNotNull('vat_rate_id')
            ->whereNull('vat_profile_id')
            ->get();

        foreach ($recurringTransactions as $recurringTransaction) {
            $vatRate = DB::table('vat_rates')->where('id', $recurringTransaction->vat_rate_id)->first();
            if ($vatRate) {
                // Find matching VAT profile by percentage
                $vatProfile = DB::table('vat_profiles')
                    ->whereRaw('CAST(percentage AS DECIMAL(5,2)) = CAST(? AS DECIMAL(5,2))', [$vatRate->rate])
                    ->first();

                if ($vatProfile) {
                    DB::table('recurring_transactions')
                        ->where('id', $recurringTransaction->id)
                        ->update(['vat_profile_id' => $vatProfile->id]);
                }
            }
        }

        // For recurring transactions with vat_rate_id that couldn't be matched,
        // use the default VAT profile from vessel settings
        $unmatchedRecurring = DB::table('recurring_transactions')
            ->whereNotNull('vat_rate_id')
            ->whereNull('vat_profile_id')
            ->where('type', 'income')
            ->get();

        foreach ($unmatchedRecurring as $recurringTransaction) {
            $vesselSetting = DB::table('vessel_settings')
                ->where('vessel_id', $recurringTransaction->vessel_id)
                ->whereNotNull('vat_profile_id')
                ->first();

            if ($vesselSetting) {
                DB::table('recurring_transactions')
                    ->where('id', $recurringTransaction->id)
                    ->update(['vat_profile_id' => $vesselSetting->vat_profile_id]);
            }
        }

        // Now remove the foreign key constraint and column
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropForeign(['vat_rate_id']);
            $table->dropColumn('vat_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->foreignId('vat_rate_id')->nullable()->after('vat_profile_id')->constrained('vat_rates')->onDelete('set null');
        });

        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropForeign(['vat_profile_id']);
            $table->dropColumn('vat_profile_id');
        });
    }
};
