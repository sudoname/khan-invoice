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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

            // Reminder configuration
            $table->enum('reminder_type', [
                'BEFORE_DUE', 'ON_DUE', 'AFTER_DUE', 'OVERDUE'
            ]);
            $table->integer('days_offset')->comment('Days before/after due date (negative = before)');

            // Delivery channels
            $table->boolean('send_email')->default(true);
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_whatsapp')->default(true);

            // Scheduling
            $table->timestamp('scheduled_at');
            $table->enum('status', [
                'SCHEDULED', 'SENT', 'FAILED', 'CANCELLED', 'SKIPPED'
            ])->default('SCHEDULED');

            // Delivery tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('delivery_metadata')->nullable(); // Provider responses

            // Message customization
            $table->text('custom_message')->nullable();
            $table->boolean('include_payment_link')->default(true);

            // Skip conditions
            $table->string('skip_reason')->nullable(); // 'already_paid', 'payment_disabled', etc.

            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['reminder_type', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
