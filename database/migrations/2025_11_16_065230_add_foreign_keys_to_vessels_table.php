<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign keys to vessels table after countries and currencies tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('vessels') && Schema::hasTable('countries') && Schema::hasTable('currencies')) {
            try {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->foreign('country_code')
                        ->references('code')
                        ->on('countries')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            try {
                Schema::table('vessels', function (Blueprint $table) {
                    $table->foreign('currency_code')
                        ->references('code')
                        ->on('currencies')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop foreign keys if they exist (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('vessels', function (Blueprint $table) {
                $table->dropForeign(['country_code']);
                $table->dropForeign(['currency_code']);
            });
        }
    }
};
