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
        Schema::create('monthly_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('cascade');
            // Note: bank_account_id was removed as bank_accounts table was dropped
            $table->tinyInteger('month'); // 1-12
            $table->year('year');

            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('total_income')->default(0);
            $table->bigInteger('total_expense')->default(0);
            $table->bigInteger('closing_balance')->default(0);

            $table->string('currency', 3)->default('EUR');
            $table->tinyInteger('house_of_zeros')->default(2);

            $table->integer('transaction_count')->default(0);
            $table->timestamp('last_calculated_at')->nullable();

            $table->timestamps();

            // Updated unique constraint: removed bank_account_id
            $table->unique(['vessel_id', 'year', 'month'], 'unique_balance');
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_balances');
    }
};
