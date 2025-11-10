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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('model_type', 255)->comment('The model class name (e.g., App\\Models\\Transaction)');
            $table->unsignedBigInteger('model_id')->nullable()->comment('The ID of the model instance');
            $table->string('action', 50)->comment('Action performed: create, update, delete');
            $table->text('message')->comment('Predefined audit message');
            $table->unsignedBigInteger('vessel_id')->nullable()->comment('Vessel ID for multi-tenancy');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
            $table->index(['vessel_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
