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
            // Note: Foreign key to vat_profiles added after vat_profiles table exists (see migration order)
            $table->unsignedBigInteger('vat_profile_id')->nullable();

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
            $table->index('vat_profile_id');
        });

        // Add foreign key to vat_profiles after vat_profiles table exists
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('vat_profiles')) {
            try {
                Schema::table('recurring_transactions', function (Blueprint $table) {
                    $table->foreign('vat_profile_id')
                        ->references('id')
                        ->on('vat_profiles')
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
            Schema::table('recurring_transactions', function (Blueprint $table) {
                $table->dropForeign(['vat_profile_id']);
            });
        }

        Schema::dropIfExists('recurring_transactions');
    }
};
