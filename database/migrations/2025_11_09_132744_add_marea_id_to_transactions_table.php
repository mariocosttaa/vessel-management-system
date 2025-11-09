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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('marea_id')->nullable()->after('vessel_id')->constrained('mareas')->onDelete('set null');
            $table->index('marea_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['marea_id']);
            $table->dropIndex(['marea_id']);
            $table->dropColumn('marea_id');
        });
    }
};
