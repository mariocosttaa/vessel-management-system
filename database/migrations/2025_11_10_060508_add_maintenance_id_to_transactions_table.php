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
        Schema::table('transactions', function (Blueprint $table) {
            // SQLite doesn't support 'after' clause, so we add the column without it
            // Column position doesn't matter for functionality
            if (DB::getDriverName() === 'sqlite') {
                $table->unsignedBigInteger('maintenance_id')->nullable();
            } else {
                $table->foreignId('maintenance_id')->nullable()->after('marea_id');
            }
            $table->index('maintenance_id');
        });

        // Add foreign key constraint (skip for SQLite due to limited support)
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('maintenances')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('maintenance_id')
                    ->references('id')
                    ->on('maintenances')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop foreign key if it exists (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['maintenance_id']);
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['maintenance_id']);
            $table->dropColumn('maintenance_id');
        });
    }
};
