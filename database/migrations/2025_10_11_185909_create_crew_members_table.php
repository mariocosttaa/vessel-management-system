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
        Schema::create('crew_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('position_id')->constrained('crew_positions')->onDelete('restrict');
            $table->string('name');
            $table->string('document_number', 50)->unique(); // NIF, passport, etc
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date');
            $table->bigInteger('salary_amount')->default(0); // em centavos
            $table->string('salary_currency', 3)->default('EUR');
            $table->tinyInteger('house_of_zeros')->default(2); // casas decimais (2 = centavos)
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vessel_id');
            $table->index('status');
            $table->index('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_members');
    }
};
