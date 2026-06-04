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
        Schema::create('platform_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('reference')->unique();
            $table->string('paystack_reference')->nullable();
            $table->string('type'); // 'payment', 'commission', 'settlement'
            $table->string('status'); // 'pending', 'success', 'failed'

            // Amount details
            $table->decimal('total_amount', 15, 2); // Total paid by customer
            $table->decimal('platform_commission', 15, 2); // Platform fee (e.g., 2%)
            $table->decimal('merchant_amount', 15, 2); // Amount for merchant (e.g., 98%)

            // Merchant details
            $table->string('merchant_name')->nullable();
            $table->string('merchant_email')->nullable();
            $table->string('merchant_account')->nullable();
            $table->string('merchant_bank')->nullable();
            $table->string('paystack_subaccount')->nullable();

            // Settlement tracking
            $table->boolean('settled_to_merchant')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_reference')->nullable();

            // Customer details
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('reference');
            $table->index('paystack_reference');
            $table->index('status');
            $table->index('type');
            $table->index('settled_to_merchant');
            $table->index('created_at');
            $table->index(['merchant_email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
    }
};
