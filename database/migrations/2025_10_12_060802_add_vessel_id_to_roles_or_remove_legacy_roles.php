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
        // Since we have vessel_role_accesses table for vessel-specific roles,
        // we should either:
        // 1. Make roles table vessel-specific, or
        // 2. Remove it entirely if it's not being used

        // Let's check if roles table is being used by checking foreign key references
        // If it's not being used, we'll remove it
        // If it is being used, we'll make it vessel-specific

        // For now, let's make it vessel-specific to maintain compatibility
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('vessel_id')->nullable()->after('id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('cascade');
            $table->index('vessel_id');
        });

        // Remove the global unique constraint on name
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_unique');
        });

        // Add unique constraint for vessel_id + name combination
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['vessel_id', 'name'], 'roles_vessel_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the vessel-specific unique constraint
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_vessel_name_unique');
        });

        // Restore the original unique constraint on name
        Schema::table('roles', function (Blueprint $table) {
            $table->unique('name', 'roles_name_unique');
        });

        // Remove vessel_id from roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });
    }
};
