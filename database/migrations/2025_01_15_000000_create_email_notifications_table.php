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
        Schema::create('email_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vessel_id')->constrained()->onDelete('cascade');
            $table->string('type'); // transaction_created, transaction_deleted, marea_started, marea_completed
            $table->string('subject_type'); // Transaction, Marea
            $table->unsignedBigInteger('subject_id'); // Transaction ID or Marea ID
            $table->json('subject_data')->nullable()->comment('Snapshot of subject data at time of notification');
            $table->string('action_by_user_id')->nullable()->comment('User who performed the action');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('grouped_at')->nullable()->comment('When this notification was grouped with others');
            $table->boolean('is_grouped')->default(false)->comment('Whether this notification is part of a group');
            $table->string('group_id')->nullable()->comment('Group ID for grouped notifications');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'vessel_id', 'sent_at']);
            $table->index(['type', 'subject_type', 'subject_id']);
            $table->index(['is_grouped', 'grouped_at']);
            $table->index('group_id');
            $table->index(['user_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
    }
};

