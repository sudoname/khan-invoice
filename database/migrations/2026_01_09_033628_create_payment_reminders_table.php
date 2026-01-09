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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->enum('channel', ['email', 'whatsapp', 'sms'])->default('email');
            $table->timestamp('scheduled_at')->index();
            $table->enum('status', ['pending', 'sent', 'failed', 'canceled'])->default('pending')->index();
            $table->text('message')->nullable();
            $table->string('recipient')->nullable(); // Email/phone number
            $table->string('reference')->nullable()->unique(); // For tracking delivery
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamps();

            // Indexes for common queries
            $table->index(['invoice_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
