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
        Schema::table('vessels', function (Blueprint $table) {
            // Add country and currency fields
            $table->string('country_code', 2)->nullable()->after('owner_id');
            $table->string('currency_code', 3)->nullable()->after('country_code');

            // Update status enum to include 'suspended'
            $table->enum('status', ['active', 'suspended', 'maintenance'])->default('active')->change();

            // Add foreign key constraints
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
            $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('set null');

            // Add indexes
            $table->index('country_code');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['country_code']);
            $table->dropForeign(['currency_code']);

            // Drop indexes
            $table->dropIndex(['country_code']);
            $table->dropIndex(['currency_code']);

            // Drop columns
            $table->dropColumn(['country_code', 'currency_code']);

            // Revert status enum to original values
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active')->change();
        });
    }
};
