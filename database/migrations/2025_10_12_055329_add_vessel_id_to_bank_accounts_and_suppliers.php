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
        // Add vessel_id to bank_accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('vessel_id')->nullable()->after('id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('set null');
            $table->index('vessel_id');
        });

        // Add vessel_id to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('vessel_id')->nullable()->after('id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('set null');
            $table->index('vessel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove vessel_id from suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });

        // Remove vessel_id from bank_accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });
    }
};
