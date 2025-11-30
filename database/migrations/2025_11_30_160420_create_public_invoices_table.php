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
        Schema::create('public_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('invoice_number');

            // From (Business) Information
            $table->string('from_name');
            $table->string('from_email')->nullable();
            $table->string('from_phone')->nullable();
            $table->text('from_address')->nullable();

            // To (Customer) Information
            $table->string('to_name');
            $table->string('to_email')->nullable();
            $table->string('to_phone')->nullable();
            $table->text('to_address')->nullable();

            // Invoice Details
            $table->date('issue_date');
            $table->date('due_date');

            // Items (stored as JSON)
            $table->json('items');

            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('wht_percentage', 5, 2)->default(0);
            $table->decimal('wht_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Notes
            $table->text('notes')->nullable();

            // Payment status
            $table->enum('payment_status', ['pending', 'paid', 'partially_paid'])->default('pending');
            $table->decimal('amount_paid', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_invoices');
    }
};
