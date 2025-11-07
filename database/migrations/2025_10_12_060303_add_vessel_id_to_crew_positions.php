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
        // Add vessel_id to crew_positions table
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('vessel_id')->nullable()->after('id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('cascade');
            $table->index('vessel_id');
        });

        // Remove the unique constraint on name since positions can be duplicated across vessels
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->dropUnique('crew_positions_name_unique');
        });

        // Add unique constraint for vessel_id + name combination
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->unique(['vessel_id', 'name'], 'crew_positions_vessel_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the vessel-specific unique constraint
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->dropUnique('crew_positions_vessel_name_unique');
        });

        // Restore the original unique constraint on name
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->unique('name', 'crew_positions_name_unique');
        });

        // Remove vessel_id from crew_positions table
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });
    }
};
