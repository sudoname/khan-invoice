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
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('paystack_subaccount_id')->nullable()->after('bank_account_type');
            $table->string('paystack_subaccount_code')->nullable()->after('paystack_subaccount_id');
            $table->string('paystack_settlement_bank')->nullable()->after('paystack_subaccount_code');
            $table->decimal('paystack_split_percentage', 5, 2)->default(0)->after('paystack_settlement_bank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_subaccount_id',
                'paystack_subaccount_code',
                'paystack_settlement_bank',
                'paystack_split_percentage'
            ]);
        });
    }
};
