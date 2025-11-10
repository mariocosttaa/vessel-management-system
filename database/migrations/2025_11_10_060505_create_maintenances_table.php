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
        if (Schema::hasTable('maintenances')) {
            return;
        }

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_number', 50)->unique(); // MANT20250001
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');

            // Informações básicas
            $table->string('name', 255)->nullable(); // Nome opcional da manutenção
            $table->text('description')->nullable();

            // Status e Ciclo de Vida
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');

            // Datas
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Currency settings
            $table->string('currency', 3)->nullable();
            $table->tinyInteger('house_of_zeros')->default(2);

            // Metadados
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('vessel_id');
            $table->index('status');
            $table->index('maintenance_number');
            $table->index(['start_date', 'end_date'], 'idx_maintenance_dates');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
