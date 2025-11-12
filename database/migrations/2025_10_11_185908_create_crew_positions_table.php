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
        Schema::create('crew_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vessel_role_access_id')->nullable()->constrained('vessel_role_accesses')->onDelete('set null');
            $table->string('name', 100); // captain, sailor, mechanic, cook
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique constraint: vessel_id + name (positions can be duplicated across vessels)
            $table->unique(['vessel_id', 'name'], 'crew_positions_vessel_name_unique');

            // Indexes
            $table->index('vessel_id');
            $table->index('vessel_role_access_id');
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
