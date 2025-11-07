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
        Schema::table('crew_members', function (Blueprint $table) {
            // Remove the unique constraint first
            $table->dropUnique('crew_members_document_number_unique');
            // Remove the index
            $table->dropIndex('crew_members_document_number_index');
            // Remove the column
            $table->dropColumn('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crew_members', function (Blueprint $table) {
            $table->string('document_number')->unique()->after('name');
        });
    }
};
