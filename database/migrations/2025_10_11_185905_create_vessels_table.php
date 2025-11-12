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
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number', 100)->unique(); // matrícula
            $table->string('vessel_type', 100); // cargo, passenger, fishing, yacht
            $table->integer('capacity')->nullable();
            $table->year('year_built')->nullable();
            $table->enum('status', ['active', 'suspended', 'maintenance', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->string('logo')->nullable()->comment('Vessel logo file path');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');

            // Country and currency
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys for country and currency (skip for SQLite due to nullable foreign key issues)
            if (config('database.default') !== 'sqlite') {
                $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
                $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('set null');
            }

            // Indexes
            $table->index('status');
            $table->index('registration_number');
            $table->index('owner_id');
            $table->index('country_code');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
