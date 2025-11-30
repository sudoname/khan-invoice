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
        Schema::table('public_invoices', function (Blueprint $table) {
            // Add discount field (after wht_amount)
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('wht_amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percentage');

            // Add bank account details (after from_address)
            $table->string('from_bank_name')->nullable()->after('from_address');
            $table->string('from_account_number')->nullable()->after('from_bank_name');
            $table->string('from_account_name')->nullable()->after('from_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'discount_percentage',
                'discount_amount',
                'from_bank_name',
                'from_account_number',
                'from_account_name',
            ]);
        });
    }
};
