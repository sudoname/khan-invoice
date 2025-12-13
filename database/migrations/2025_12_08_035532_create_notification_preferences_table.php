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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // SMS preferences
            $table->boolean('sms_payment_received')->default(false);
            $table->boolean('sms_invoice_sent')->default(false);
            $table->boolean('sms_payment_reminder')->default(false);
            $table->boolean('sms_invoice_overdue')->default(false);

            // Email preferences
            $table->boolean('email_payment_received')->default(true);
            $table->boolean('email_invoice_sent')->default(true);
            $table->boolean('email_payment_reminder')->default(true);
            $table->boolean('email_invoice_overdue')->default(true);

            // SMS credit tracking (cost optimization)
            $table->integer('sms_credits_remaining')->default(0);
            $table->boolean('sms_enabled')->default(false); // Master switch

            $table->timestamps();

            // Ensure one preference record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
