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
        Schema::create('marea_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marea_id')->constrained('mareas')->onDelete('cascade');

            // Reference to profile item (if based on profile)
            $table->foreignId('profile_item_id')->nullable()->constrained('marea_distribution_profile_items')->onDelete('set null');

            // Ordem de execução
            $table->integer('order_index'); // Ordem em que o item será calculado (1, 2, 3, ...)

            // Informações do item
            $table->string('name', 255); // Nome do item
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
            $table->decimal('value_amount', 15, 2)->nullable(); // Valor fixo ou percentual
            $table->foreignId('reference_item_id')->nullable()->constrained('marea_distribution_items')->onDelete('set null');

            // Operação matemática
            $table->enum('operation', ['set', 'add', 'subtract', 'multiply', 'divide'])->default('set');

            // Item de referência para operação (opcional)
            $table->foreignId('reference_operation_item_id')->nullable()->constrained('marea_distribution_items')->onDelete('set null');

            // Metadados
            $table->timestamps();

            $table->index('marea_id');
            $table->index(['marea_id', 'order_index'], 'idx_marea_order');
            $table->index('profile_item_id');
            $table->index('reference_item_id');
            $table->index('reference_operation_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marea_distribution_items');
    }
};
