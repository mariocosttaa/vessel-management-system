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
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('to_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('from_transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('to_transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('EUR');
            $table->tinyInteger('house_of_zeros')->default(2);
            $table->date('transfer_date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['from_account_id', 'to_account_id']);
            $table->index('transfer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
