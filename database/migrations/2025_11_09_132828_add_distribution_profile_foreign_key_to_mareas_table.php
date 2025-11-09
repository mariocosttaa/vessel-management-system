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
            $table->foreign('distribution_profile_id')->references('id')->on('marea_distribution_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mareas', function (Blueprint $table) {
            $table->dropForeign(['distribution_profile_id']);
        });
    }
};
