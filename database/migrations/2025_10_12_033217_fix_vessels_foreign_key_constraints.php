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
            // Drop existing foreign key constraints for nullable fields
            $table->dropForeign(['country_code']);
            $table->dropForeign(['currency_code']);

            // Re-add foreign key constraints with proper handling for nullable fields
            // SQLite doesn't handle nullable foreign keys well, so we'll use a different approach
            if (config('database.default') !== 'sqlite') {
                $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
                $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            // Drop the foreign key constraints
            $table->dropForeign(['country_code']);
            $table->dropForeign(['currency_code']);

            // Re-add the original foreign key constraints
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
            $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('set null');
        });
    }
};
