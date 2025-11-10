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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');
            // Note: bank_account_id was removed as bank_accounts table was dropped
            $table->foreignId('category_id')->constrained('transaction_categories')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');

            $table->string('name');
            $table->enum('type', ['income', 'expense']);

            // Valores
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('EUR');
            $table->tinyInteger('house_of_zeros')->default(2);

            // VAT (vat_rate_id was removed, using vat_profile_id instead)
            $table->foreignId('vat_profile_id')->nullable()->constrained('vat_profiles')->onDelete('set null');

            // Recorrência
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'semi_annual', 'annual']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_occurrence_date');
            $table->date('last_generated_date')->nullable();

            $table->text('description')->nullable();
            $table->boolean('auto_generate')->default(true); // gera automaticamente

            $table->enum('status', ['active', 'paused', 'completed'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('next_occurrence_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
