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
        Schema::create('marea_distribution_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255); // Nome do perfil (ex: "Perfil Padrão", "Perfil Pesca Costeira")
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false); // Perfis do sistema não podem ser deletados

            // Metadados
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_default');
            $table->index('is_system');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marea_distribution_profiles');
    }
};
