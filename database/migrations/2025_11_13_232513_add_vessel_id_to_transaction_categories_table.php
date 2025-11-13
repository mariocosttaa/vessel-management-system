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
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->foreignId('vessel_id')->nullable()->after('is_system')->constrained('vessels')->onDelete('cascade');
            $table->index('vessel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });
    }
};
