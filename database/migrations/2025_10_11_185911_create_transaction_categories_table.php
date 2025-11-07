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
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('transaction_categories')->onDelete('cascade'); // para subcategorias
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable(); // hex color para UI
            $table->boolean('is_system')->default(false); // categorias do sistema não podem ser deletadas
            $table->timestamps();

            $table->index('type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
