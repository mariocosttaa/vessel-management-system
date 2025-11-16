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
        // Add foreign keys to transactions table after mareas and vat_profiles tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('transactions')) {
            try {
                if (Schema::hasTable('mareas')) {
                    Schema::table('transactions', function (Blueprint $table) {
                        $table->foreign('marea_id')
                            ->references('id')
                            ->on('mareas')
                            ->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            try {
                if (Schema::hasTable('vat_profiles')) {
                    Schema::table('transactions', function (Blueprint $table) {
                        $table->foreign('vat_profile_id')
                            ->references('id')
                            ->on('vat_profiles')
                            ->onDelete('set null');
                    });
                }
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
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['marea_id']);
                $table->dropForeign(['vat_profile_id']);
            });
        }
    }
};
