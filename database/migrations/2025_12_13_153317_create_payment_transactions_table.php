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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');

            // Transaction details
            $table->string('type'); // subscription_payment, credit_purchase, upgrade, downgrade
            $table->string('status'); // pending, successful, failed, refunded
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');

            // Payment gateway data
            $table->string('payment_gateway')->default('paystack');
            $table->string('transaction_reference')->unique();
            $table->string('paystack_reference')->nullable();
            $table->text('gateway_response')->nullable();

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('transaction_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
