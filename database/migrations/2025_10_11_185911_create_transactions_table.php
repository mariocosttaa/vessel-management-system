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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique(); // gerado automaticamente
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('bank_account_id')->constrained()->onDelete('restrict');
            $table->foreignId('category_id')->constrained('transaction_categories')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('crew_member_id')->nullable()->constrained()->onDelete('set null'); // se for pagamento de salário

            $table->enum('type', ['income', 'expense', 'transfer']);

            // Valores monetários
            $table->bigInteger('amount'); // valor em inteiro (centavos)
            $table->string('currency', 3)->default('EUR');
            $table->tinyInteger('house_of_zeros')->default(2);

            // IVA
            $table->foreignId('vat_rate_id')->nullable()->constrained()->onDelete('set null');
            $table->bigInteger('vat_amount')->default(0); // IVA em centavos
            $table->bigInteger('total_amount'); // amount + vat_amount

            // Organização temporal
            $table->date('transaction_date');
            $table->tinyInteger('transaction_month'); // 1-12
            $table->year('transaction_year');

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference', 100)->nullable(); // referência externa (fatura, etc)

            // Despesas recorrentes
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('recurring_transaction_id')->nullable()->constrained('recurring_transactions')->onDelete('set null');

            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('vessel_id');
            $table->index('bank_account_id');
            $table->index('category_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index(['transaction_year', 'transaction_month'], 'idx_month_year');
            $table->index('status');
            $table->index('transaction_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
