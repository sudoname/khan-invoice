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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');

            // Subscription details
            $table->string('status')->default('active'); // active, canceled, expired, past_due
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Payment gateway reference
            $table->string('paystack_subscription_code')->nullable();
            $table->string('paystack_email_token')->nullable();

            // Usage resets
            $table->integer('invoices_used')->default(0);
            $table->integer('customers_used')->default(0);
            $table->integer('sms_credits_used')->default(0);
            $table->integer('whatsapp_credits_used')->default(0);
            $table->integer('api_requests_used')->default(0);
            $table->timestamp('usage_reset_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
