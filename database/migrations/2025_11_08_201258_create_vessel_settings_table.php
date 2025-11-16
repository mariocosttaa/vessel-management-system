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
        Schema::create('vessel_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->unique()->constrained('vessels')->onDelete('cascade');
            // Note: MySQL doesn't support 'after' clause in CREATE TABLE, only in ALTER TABLE
            // Column position doesn't matter for functionality
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            // Note: Foreign key to vat_profiles added after vat_profiles table exists (see migration order)
            $table->unsignedBigInteger('vat_profile_id')->nullable();
            $table->integer('starting_marea_number')->nullable()->default(1);
            $table->timestamps();

            // Indexes
            $table->index('country_code');
            $table->index('currency_code');
            $table->index('vat_profile_id');
        });

        // Add foreign key constraints after referenced tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            try {
                if (Schema::hasTable('countries')) {
                    Schema::table('vessel_settings', function (Blueprint $table) {
                        $table->foreign('country_code')
                            ->references('code')
                            ->on('countries')
                            ->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            try {
                if (Schema::hasTable('currencies')) {
                    Schema::table('vessel_settings', function (Blueprint $table) {
                        $table->foreign('currency_code')
                            ->references('code')
                            ->on('currencies')
                            ->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            try {
                if (Schema::hasTable('vat_profiles')) {
                    Schema::table('vessel_settings', function (Blueprint $table) {
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
            Schema::table('vessel_settings', function (Blueprint $table) {
                $table->dropForeign(['country_code']);
                $table->dropForeign(['currency_code']);
                $table->dropForeign(['vat_profile_id']);
            });
        }

        Schema::dropIfExists('vessel_settings');
    }
};
