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
        Schema::create('marea_distribution_profile_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_profile_id')->constrained('marea_distribution_profiles')->onDelete('cascade');

            // Ordem de execução
            $table->integer('order_index'); // Ordem em que o item será calculado (1, 2, 3, ...)

            // Informações do item
            $table->string('name', 255); // Nome do item (ex: "Total Receita", "15% do Barco", "Gelo para Descarga")
            $table->text('description')->nullable();

            // Tipo de valor de entrada (source)
            $table->enum('value_type', [
                'base_total_income',
                'base_total_expense',
                'fixed_amount',
                'percentage_of_income',
                'percentage_of_expense',
                'reference_item'
            ]);

            // Valor (depende do value_type)
            // - Se fixed_amount: valor fixo em centavos
            // - Se percentage_of_income/expense: percentual (ex: 15.00 para 15%)
            // - Se reference_item: ID do item referenciado
            // - Se base_total_income/expense: NULL (usa valor base)
            $table->decimal('value_amount', 15, 2)->nullable(); // Valor fixo ou percentual
            $table->foreignId('reference_item_id')->nullable()->constrained('marea_distribution_profile_items')->onDelete('set null'); // ID do item referenciado (se value_type = 'reference_item')

            // Operação matemática
            $table->enum('operation', ['set', 'add', 'subtract', 'multiply', 'divide'])->default('set');

            // Item de referência para operação (opcional)
            // Se operation = 'set': usa value_amount ou base
            // Se operation = 'add/subtract/multiply/divide': operação será feita com o resultado do item anterior ou reference_operation_item_id
            $table->foreignId('reference_operation_item_id')->nullable()->constrained('marea_distribution_profile_items')->onDelete('set null'); // ID do item para operação (ex: subtrair item X)

            // Metadados
            $table->timestamps();

            $table->index('distribution_profile_id');
            $table->index(['distribution_profile_id', 'order_index'], 'idx_order');
            $table->index('reference_item_id');
            $table->index('reference_operation_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marea_distribution_profile_items');
    }
};
