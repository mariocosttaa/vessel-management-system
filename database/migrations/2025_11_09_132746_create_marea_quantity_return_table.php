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
        Schema::create('marea_quantity_return', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marea_id')->constrained('mareas')->onDelete('cascade');
            $table->string('name', 255); // Nome do produto/peixe (ex: "MERMA - PEQUENA", "MERMA - GRANDE", "REBENTADOS")
            $table->decimal('quantity', 10, 2); // Quantidade retornada
            $table->bigInteger('unit_price')->nullable(); // Preço unitário em centavos (opcional, pode vir de transações)
            $table->bigInteger('total_value')->nullable(); // Valor total em centavos (opcional, calculado)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('marea_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marea_quantity_return');
    }
};
