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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_profile_id')->nullable()->constrained('business_profiles')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('expense_number')->unique(); // e.g., EXP-2025-00000001
            $table->date('expense_date');
            $table->date('due_date')->nullable();
            $table->string('category'); // e.g., utilities, rent, supplies, services, etc.
            $table->text('description');
            $table->string('reference_number')->nullable(); // Vendor invoice number or reference
            $table->string('payment_method')->nullable(); // cash, bank transfer, check, credit card
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
            $table->string('currency', 3)->default('NGN');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount + tax
            $table->text('notes')->nullable();
            $table->string('receipt_url')->nullable(); // Receipt/proof of payment
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
