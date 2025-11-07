<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate data from user_vessels to vessel_users
        DB::statement("
            INSERT INTO vessel_users (vessel_id, user_id, is_active, role, created_at, updated_at)
            SELECT vessel_id, user_id, true as is_active, role, created_at, updated_at
            FROM user_vessels
        ");

        // Drop the old user_vessels table
        Schema::dropIfExists('user_vessels');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the old user_vessels table
        Schema::create('user_vessels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'manager', 'viewer'])->default('viewer');
            $table->timestamps();

            $table->unique(['user_id', 'vessel_id']);
            $table->index(['user_id', 'role']);
            $table->index(['vessel_id', 'role']);
        });

        // Migrate data back from vessel_users to user_vessels
        DB::statement("
            INSERT INTO user_vessels (vessel_id, user_id, role, created_at, updated_at)
            SELECT vessel_id, user_id, role, created_at, updated_at
            FROM vessel_users
            WHERE is_active = true
        ");
    }
};
