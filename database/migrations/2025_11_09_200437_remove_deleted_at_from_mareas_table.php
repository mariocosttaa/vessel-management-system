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
        // Check if deleted_at column exists before trying to clean up
        if (Schema::hasColumn('mareas', 'deleted_at')) {
            // First, permanently delete any soft-deleted mareas and their related data
            // Use DB facade to avoid model issues during migration
            $softDeletedMareaIds = \Illuminate\Support\Facades\DB::table('mareas')
                ->whereNotNull('deleted_at')
                ->pluck('id');

            foreach ($softDeletedMareaIds as $mareaId) {
                // Permanently delete related transactions (use force delete for soft-deleted transactions)
                \Illuminate\Support\Facades\DB::table('transactions')
                    ->where('marea_id', $mareaId)
                    ->delete();
                // Permanently delete related quantity returns
                \Illuminate\Support\Facades\DB::table('marea_quantity_returns')
                    ->where('marea_id', $mareaId)
                    ->delete();
                // Permanently delete related crew members
                \Illuminate\Support\Facades\DB::table('marea_crew')
                    ->where('marea_id', $mareaId)
                    ->delete();
                // Permanently delete related distribution items
                \Illuminate\Support\Facades\DB::table('marea_distribution_items')
                    ->where('marea_id', $mareaId)
                    ->delete();
            }

            // Permanently delete soft-deleted mareas
            \Illuminate\Support\Facades\DB::table('mareas')
                ->whereNotNull('deleted_at')
                ->delete();
        }

        Schema::table('mareas', function (Blueprint $table) {
            if (Schema::hasColumn('mareas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mareas', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
