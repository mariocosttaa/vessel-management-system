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
        Schema::create('vessel_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');
            $table->foreignId('vessel_role_access_id')->constrained('vessel_role_accesses')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'vessel_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['vessel_id', 'is_active']);
            $table->index(['vessel_role_access_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_user_roles');
    }
};
