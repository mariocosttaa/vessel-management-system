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
        // Drop table if it exists (in case of previous failed migration)
        Schema::dropIfExists('marea_distribution_items');

        Schema::create('marea_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marea_id')->constrained('mareas')->onDelete('cascade');

            // Reference to profile item (if based on profile)
            // Note: Foreign key to marea_distribution_profile_items added after that table exists
            $table->unsignedBigInteger('profile_item_id')->nullable();

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
            // Note: Self-referencing foreign keys added after table exists
            $table->unsignedBigInteger('reference_item_id')->nullable();

            // Operação matemática
            $table->enum('operation', ['set', 'add', 'subtract', 'multiply', 'divide'])->default('set');

            // Item de referência para operação (opcional)
            $table->unsignedBigInteger('reference_operation_item_id')->nullable();

            // Metadados
            $table->timestamps();

            $table->index('marea_id');
            $table->index(['marea_id', 'order_index'], 'idx_marea_order');
            $table->index('profile_item_id', 'idx_profile_item_id');
            $table->index('reference_item_id', 'idx_marea_dist_item_ref_id');
            $table->index('reference_operation_item_id', 'idx_marea_dist_item_ref_op_id');
        });

        // Add foreign keys after referenced tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            try {
                if (Schema::hasTable('marea_distribution_profile_items')) {
                    Schema::table('marea_distribution_items', function (Blueprint $table) {
                        $table->foreign('profile_item_id')
                            ->references('id')
                            ->on('marea_distribution_profile_items')
                            ->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            // Add self-referencing foreign keys after table exists
            try {
                Schema::table('marea_distribution_items', function (Blueprint $table) {
                    $table->foreign('reference_item_id')
                        ->references('id')
                        ->on('marea_distribution_items')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }

            try {
                Schema::table('marea_distribution_items', function (Blueprint $table) {
                    $table->foreign('reference_operation_item_id')
                        ->references('id')
                        ->on('marea_distribution_items')
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
        // Only drop foreign keys if they exist (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('marea_distribution_items', function (Blueprint $table) {
                $table->dropForeign(['profile_item_id']);
                $table->dropForeign(['reference_item_id']);
                $table->dropForeign(['reference_operation_item_id']);
            });
        }

        Schema::dropIfExists('marea_distribution_items');
    }
};
