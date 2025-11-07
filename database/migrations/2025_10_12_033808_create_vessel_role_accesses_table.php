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
        Schema::create('vessel_role_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // normal, moderator, supervisor, administrator
            $table->string('display_name'); // Normal User, Moderator, Supervisor, Administrator
            $table->text('description')->nullable();
            $table->json('permissions'); // JSON array of permissions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_role_accesses');
    }
};
