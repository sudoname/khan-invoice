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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_profile_id')->nullable()->constrained('business_profiles')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('income_number')->unique(); // e.g., INC-2025-00000001
            $table->date('income_date');
            $table->string('category'); // cash_sales, service_revenue, commission, interest, other
            $table->text('description');
            $table->string('payment_method')->nullable(); // cash, bank_transfer, card, mobile_money, cheque
            $table->string('reference_number')->nullable(); // Transaction reference
            $table->string('currency', 3)->default('NGN');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0); // VAT collected if applicable
            $table->decimal('total_amount', 15, 2); // amount + tax
            $table->text('notes')->nullable();
            $table->string('receipt_url')->nullable(); // Receipt/proof if any
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
