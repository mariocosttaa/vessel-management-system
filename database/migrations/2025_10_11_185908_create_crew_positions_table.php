<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crew_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('cascade');
            // Note: Foreign key to vessel_role_accesses added after that table exists (see migration order)
            $table->unsignedBigInteger('vessel_role_access_id')->nullable();
            $table->string('name', 100); // captain, sailor, mechanic, cook
            $table->text('description')->nullable();
            $table->boolean('is_administrative')->default(false);
            $table->timestamps();

            // Unique constraint: vessel_id + name (positions can be duplicated across vessels)
            $table->unique(['vessel_id', 'name'], 'crew_positions_vessel_name_unique');

            // Indexes
            $table->index('vessel_id');
            $table->index('vessel_role_access_id');
        });

        // Add foreign key to vessel_role_accesses after that table exists
        // This is done here to keep everything in one migration file per the migration patterns
        // Note: This migration must run after vessel_role_accesses migration
        Schema::table('crew_positions', function (Blueprint $table) {
            $table->foreign('vessel_role_access_id')
                ->references('id')
                ->on('vessel_role_accesses')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, disable foreign key checks temporarily
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        Schema::dropIfExists('crew_positions');

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
};
