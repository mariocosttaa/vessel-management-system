<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop account_transfers table first (if it exists) as it depends on bank_accounts
        if (Schema::hasTable('account_transfers')) {
            Schema::dropIfExists('account_transfers');
        }

        // Drop bank_accounts table
        // Note: monthly_balances.bank_account_id will remain but won't be used
        // This is acceptable as SQLite has limitations with dropping columns that are part of indexes
        if (Schema::hasTable('bank_accounts')) {
            Schema::dropIfExists('bank_accounts');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration doesn't recreate the tables as they're no longer needed
        // If you need to reverse this, you'll need to recreate the bank_accounts table structure
    }
};
