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
        Schema::table('marea_quantity_return', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marea_quantity_return', function (Blueprint $table) {
            $table->bigInteger('unit_price')->nullable()->after('quantity');
            $table->bigInteger('total_value')->nullable()->after('unit_price');
        });
    }
};
