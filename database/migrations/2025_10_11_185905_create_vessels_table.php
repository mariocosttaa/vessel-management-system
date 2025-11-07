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
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number', 100)->unique(); // matrícula
            $table->string('vessel_type', 100); // cargo, passenger, fishing, yacht
            $table->integer('capacity')->nullable();
            $table->year('year_built')->nullable();
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('registration_number');
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
