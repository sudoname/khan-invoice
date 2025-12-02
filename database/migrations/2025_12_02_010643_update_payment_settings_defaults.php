<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Paystack fee cap from ₦3,000 to ₦2,000 (Paystack's actual local cap)
        DB::table('payment_settings')
            ->where('key', 'paystack_fee_cap')
            ->update([
                'value' => '2000',
                'description' => 'Maximum Paystack fee cap in Naira (Paystack local cap: ₦2,000)',
            ]);

        // Update description for Paystack fee minimum to clarify it's a fixed amount
        DB::table('payment_settings')
            ->where('key', 'paystack_fee_minimum')
            ->update([
                'description' => 'Fixed amount added to percentage (Formula: percentage + fixed amount)',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old values
        DB::table('payment_settings')
            ->where('key', 'paystack_fee_cap')
            ->update([
                'value' => '3000',
                'description' => 'Maximum Paystack fee cap in Naira',
            ]);

        DB::table('payment_settings')
            ->where('key', 'paystack_fee_minimum')
            ->update([
                'description' => 'Minimum Paystack fee in Naira',
            ]);
    }
};
