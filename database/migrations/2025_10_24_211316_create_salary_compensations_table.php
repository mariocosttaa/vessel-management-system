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
        Schema::create('salary_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('compensation_type', ['fixed', 'percentage'])->default('fixed');
            $table->bigInteger('fixed_amount')->nullable()->comment('Fixed salary amount in cents');
            $table->decimal('percentage', 5, 2)->nullable()->comment('Percentage of total revenue (0.00-100.00)');
            $table->string('currency', 3)->default('EUR');
            $table->enum('payment_frequency', ['weekly', 'bi_weekly', 'monthly', 'quarterly', 'annually'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'is_active']);
            $table->index(['compensation_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_compensations');
    }
};