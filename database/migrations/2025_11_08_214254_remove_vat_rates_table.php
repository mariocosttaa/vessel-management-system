<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the vat_rates table as we're now using vat_profiles exclusively.
     */
    public function up(): void
    {
        // Check if table exists before dropping
        if (Schema::hasTable('vat_rates')) {
            Schema::dropIfExists('vat_rates');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2); // 23.00, 13.00, 6.00, 0.00
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }
};
