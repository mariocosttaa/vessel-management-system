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
        // Add crew member fields to users table
        Schema::table('users', function (Blueprint $table) {
            // Crew member specific fields
            $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null')->after('user_type');
            $table->foreignId('position_id')->nullable()->constrained('crew_positions')->onDelete('restrict')->after('vessel_id');
            $table->string('phone', 50)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->date('hire_date')->nullable()->after('date_of_birth');
            $table->bigInteger('salary_amount')->nullable()->default(0)->after('hire_date'); // in cents
            $table->string('salary_currency', 3)->nullable()->default('EUR')->after('salary_amount');
            $table->tinyInteger('house_of_zeros')->nullable()->default(2)->after('salary_currency'); // decimal places
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly'])->nullable()->after('house_of_zeros');
            $table->enum('status', ['active', 'inactive', 'on_leave'])->nullable()->default('active')->after('payment_frequency');
            $table->text('notes')->nullable()->after('status');

            // System access fields
            $table->boolean('login_permitted')->default(true)->after('notes');
            $table->string('temporary_password')->nullable()->after('login_permitted');

            // Indexes for performance
            $table->index(['vessel_id', 'status']);
            $table->index(['position_id', 'status']);
            $table->index(['login_permitted', 'status']);
            $table->index(['user_type', 'login_permitted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['vessel_id', 'status']);
            $table->dropIndex(['position_id', 'status']);
            $table->dropIndex(['login_permitted', 'status']);
            $table->dropIndex(['user_type', 'login_permitted']);

            // Drop foreign keys
            $table->dropForeign(['vessel_id']);
            $table->dropForeign(['position_id']);

            // Drop columns
            $table->dropColumn([
                'vessel_id',
                'position_id',
                'phone',
                'date_of_birth',
                'hire_date',
                'salary_amount',
                'salary_currency',
                'house_of_zeros',
                'payment_frequency',
                'status',
                'notes',
                'login_permitted',
                'temporary_password'
            ]);
        });
    }
};
