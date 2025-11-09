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
            $table->bigInteger('price_per_unit')->nullable()->after('amount')->comment('Price per unit in cents');
            $table->decimal('quantity', 10, 2)->nullable()->after('price_per_unit')->comment('Quantity of items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['price_per_unit', 'quantity']);
        });
    }
};
