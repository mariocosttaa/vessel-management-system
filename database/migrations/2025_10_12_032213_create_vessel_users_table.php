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
        Schema::create('vessel_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('Whether user has access to this vessel');
            $table->enum('role', ['owner', 'manager', 'viewer'])->default('viewer');
            $table->timestamps();

            // Unique constraint to prevent duplicate vessel-user relationships
            $table->unique(['vessel_id', 'user_id']);

            // Indexes for better performance
            $table->index(['vessel_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_users');
    }
};
