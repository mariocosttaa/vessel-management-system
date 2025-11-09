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
        Schema::table('mareas', function (Blueprint $table) {
            $table->boolean('use_calculation')->default(true)->after('distribution_profile_id');
            $table->string('currency', 3)->nullable()->after('use_calculation');
            $table->tinyInteger('house_of_zeros')->default(2)->after('currency');

            $table->index('use_calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mareas', function (Blueprint $table) {
            $table->dropIndex(['use_calculation']);
            $table->dropColumn(['use_calculation', 'currency', 'house_of_zeros']);
        });
    }
};
