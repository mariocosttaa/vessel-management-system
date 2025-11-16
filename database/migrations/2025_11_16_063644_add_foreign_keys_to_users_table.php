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
        // Add foreign keys to users table after vessels and crew_positions tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('vessels') && Schema::hasTable('crew_positions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('vessel_id')
                    ->references('id')
                    ->on('vessels')
                    ->onDelete('set null');

                $table->foreign('position_id')
                    ->references('id')
                    ->on('crew_positions')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop foreign keys if they exist (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['vessel_id']);
                $table->dropForeign(['position_id']);
            });
        }
    }
};
