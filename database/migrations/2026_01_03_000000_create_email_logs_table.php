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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->string('message_type')->default('general'); // invoice_sent, payment_received, payment_reminder, invoice_overdue, verification, general
            $table->text('body_excerpt')->nullable(); // First 500 chars of email body
            $table->string('status')->default('sent'); // sent, failed
            $table->text('error_message')->nullable();
            $table->string('provider')->default('smtp'); // smtp, ses, mailgun, etc.
            $table->string('message_id')->nullable(); // Email provider's message ID
            $table->json('metadata')->nullable(); // Additional data (invoice_id, customer_id, etc.)
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('recipient_email');
            $table->index('message_type');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
