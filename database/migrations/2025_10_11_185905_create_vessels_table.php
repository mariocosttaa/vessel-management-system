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
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number', 100)->unique(); // matrícula
            $table->string('vessel_type', 100);                   // cargo, passenger, fishing, yacht
            $table->integer('capacity')->nullable();
            $table->year('year_built')->nullable();
            $table->enum('status', ['active', 'suspended', 'maintenance', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->string('logo')->nullable()->comment('Vessel logo file path');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');

            // Country and currency
            // Note: Foreign keys added after countries and currencies tables exist (see migration order)
            $table->string('country_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('registration_number');
            $table->index('owner_id');
            $table->index('country_code');
            $table->index('currency_code');
        });

        // Add foreign keys for country and currency after those tables exist
        // Note: SQLite has limited foreign key support, so we skip for SQLite
        if (DB::getDriverName() !== 'sqlite' && Schema::hasTable('countries') && Schema::hasTable('currencies')) {
            Schema::table('vessels', function (Blueprint $table) {
                $table->foreign('country_code')
                    ->references('code')
                    ->on('countries')
                    ->onDelete('set null');

                $table->foreign('currency_code')
                    ->references('code')
                    ->on('currencies')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop foreign keys if they exist (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('vessels', function (Blueprint $table) {
                $table->dropForeign(['country_code']);
                $table->dropForeign(['currency_code']);
            });
        }

        Schema::dropIfExists('vessels');
    }
};
