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
        // Add subscription change tracking fields
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('last_plan_change_at')->nullable()->after('usage_reset_at');
            $table->foreignId('previous_plan_id')->nullable()->after('plan_id')->constrained('plans')->onDelete('set null');
        });

        // Create account credits table for tracking credits
        Schema::create('account_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');

            $table->string('type'); // prorated_refund, plan_change, manual_adjustment
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('available'); // available, used, expired

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_in_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_credits');

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['previous_plan_id']);
            $table->dropColumn(['last_plan_change_at', 'previous_plan_id']);
        });
    }
};
