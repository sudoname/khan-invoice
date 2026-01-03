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
        Schema::create('merchant_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Bank account details
            $table->string('bank_name');
            $table->string('bank_code', 10); // Nigerian bank codes
            $table->string('account_number', 20);
            $table->string('account_name');
            $table->enum('account_type', ['savings', 'current', 'corporate', 'domiciliary'])->default('savings');

            // Verification status
            $table->enum('verification_status', [
                'PENDING', 'VERIFIED', 'FAILED', 'SUSPENDED'
            ])->default('PENDING');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            // Settlement preferences
            $table->boolean('is_primary')->default(true);
            $table->enum('settlement_schedule', [
                'INSTANT', 'DAILY', 'WEEKLY', 'MANUAL'
            ])->default('MANUAL');
            $table->decimal('minimum_payout', 15, 2)->default(1000.00); // Min ₦1000

            // Provider integration
            $table->string('provider_recipient_code')->nullable(); // Paystack/Flutterwave code
            $table->json('provider_metadata')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_primary']);
            $table->index(['verification_status', 'is_active']);
            $table->unique(['user_id', 'account_number'], 'unique_user_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_accounts');
    }
};
