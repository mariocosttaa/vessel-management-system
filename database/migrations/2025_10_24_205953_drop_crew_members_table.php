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
        // Drop the crew_members table since we've migrated all data to users table
        Schema::dropIfExists('crew_members');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the crew_members table structure (for rollback purposes)
        Schema::create('crew_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('position_id')->constrained('crew_positions')->onDelete('restrict');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date');
            $table->integer('salary_amount')->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->integer('house_of_zeros')->default(2);
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly']);
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vessel_id', 'status']);
            $table->index(['position_id', 'status']);
            $table->index(['user_id', 'vessel_id']);
        });
    }
};
