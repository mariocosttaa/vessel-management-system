<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mareas', function (Blueprint $table) {
            $table->id();
            $table->string('marea_number', 50)->unique(); // MARE20250001
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');

            // Informações básicas
            $table->string('name', 255)->nullable(); // Nome opcional da marea
            $table->text('description')->nullable();

            // Status e Ciclo de Vida
            $table->enum('status', ['preparing', 'at_sea', 'returned', 'closed', 'cancelled'])->default('preparing');

            // Datas Estimadas
            $table->date('estimated_departure_date')->nullable();
            $table->date('estimated_return_date')->nullable();

            // Datas Reais
            $table->date('actual_departure_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Perfil de Distribuição Financeira
            // Note: Foreign key to marea_distribution_profiles added after marea_distribution_profiles table exists (see migration order)
            $table->unsignedBigInteger('distribution_profile_id')->nullable();

            // Calculation fields
            $table->boolean('use_calculation')->default(true);
            $table->string('currency', 3)->nullable();
            $table->tinyInteger('house_of_zeros')->default(2);

            // Metadados
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('vessel_id');
            $table->index('status');
            $table->index('marea_number');
            $table->index(['estimated_departure_date', 'actual_departure_date'], 'idx_dates');
            $table->index('distribution_profile_id');
            $table->index('use_calculation');
            $table->index('created_at');
        });

        // Add foreign key to marea_distribution_profiles after marea_distribution_profiles table exists
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('marea_distribution_profiles')) {
            try {
                Schema::table('mareas', function (Blueprint $table) {
                    $table->foreign('distribution_profile_id')
                        ->references('id')
                        ->on('marea_distribution_profiles')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop foreign key if it exists (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('mareas', function (Blueprint $table) {
                $table->dropForeign(['distribution_profile_id']);
            });
        }

        Schema::dropIfExists('mareas');
    }
};
