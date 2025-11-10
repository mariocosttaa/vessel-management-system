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
        Schema::create('vessel_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->unique()->constrained('vessels')->onDelete('cascade');
            $table->string('country_code', 2)->nullable()->after('vessel_id');
            $table->string('currency_code', 3)->nullable()->after('country_code');
            $table->foreignId('vat_profile_id')->nullable()->after('currency_code')->constrained('vat_profiles')->onDelete('set null');
            $table->integer('starting_marea_number')->nullable()->after('vat_profile_id')->default(1);
            $table->timestamps();

            // Foreign key constraints (skip for SQLite due to nullable foreign key issues)
            if (config('database.default') !== 'sqlite') {
                $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
                $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('set null');
            }

            // Indexes
            $table->index('country_code');
            $table->index('currency_code');
            $table->index('vat_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_settings');
    }
};
