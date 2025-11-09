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
        Schema::table('transactions', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['bank_account_id']);
            // Drop index
            $table->dropIndex(['bank_account_id']);
            // Drop column
            $table->dropColumn('bank_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('vessel_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->index('bank_account_id');
        });
    }
};
