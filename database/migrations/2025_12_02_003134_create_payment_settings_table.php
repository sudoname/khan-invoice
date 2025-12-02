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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('payment_settings')->insert([
            [
                'key' => 'paystack_fee_percentage',
                'value' => '1.5',
                'description' => 'Paystack fee percentage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'paystack_fee_minimum',
                'value' => '100',
                'description' => 'Minimum Paystack fee in Naira',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'paystack_fee_cap',
                'value' => '3000',
                'description' => 'Maximum Paystack fee cap in Naira',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'service_charge_percentage',
                'value' => '2',
                'description' => 'Service charge percentage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'service_charge_minimum',
                'value' => '150',
                'description' => 'Minimum service charge in Naira',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'service_charge_cap',
                'value' => '3000',
                'description' => 'Maximum service charge cap in Naira',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
