<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Checks if roles table is still being used.
     * The Role model is used for basic user roles (admin, manager, viewer),
     * but vessel-specific permissions use VesselRoleAccess/VesselUserRole.
     *
     * We'll keep the roles table for now as it's used in User model for basic role checking.
     * If you want to remove it, you'll need to refactor User model to not use Role.
     */
    public function up(): void
    {
        // Check if roles table has any data
        $roleCount = DB::table('roles')->count();
        $userRoleCount = DB::table('user_roles')->count();

        // Log for information
        if ($roleCount > 0 || $userRoleCount > 0) {
            \Log::info("Roles table migration: Found {$roleCount} roles and {$userRoleCount} user_role assignments. Keeping tables for now.");
        }

        // For now, we keep the roles table as it's used in User model
        // If you want to remove it completely, you need to:
        // 1. Remove Role usage from User model
        // 2. Update all hasRole() checks to use VesselRoleAccess instead
        // 3. Then drop the tables

        // Uncomment below to remove if you're sure:
        /*
        if (Schema::hasTable('user_roles')) {
            Schema::dropIfExists('user_roles');
        }

        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse if we're keeping the tables
    }
};
