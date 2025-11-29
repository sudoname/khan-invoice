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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217 code (NGN, USD, GBP, EUR)
            $table->string('name'); // Nigerian Naira, US Dollar, etc.
            $table->string('symbol'); // ₦, $, £, €
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Rate to base currency (NGN)
            $table->boolean('is_base')->default(false); // Is this the base currency?
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default currencies
        DB::table('currencies')->insert([
            [
                'code' => 'NGN',
                'name' => 'Nigerian Naira',
                'symbol' => '₦',
                'exchange_rate' => 1.000000,
                'is_base' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 0.0013, // Example: 1 NGN = 0.0013 USD (approx 760 NGN/USD)
                'is_base' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'exchange_rate' => 0.0010, // Example: 1 NGN = 0.001 GBP (approx 1000 NGN/GBP)
                'is_base' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.0012, // Example: 1 NGN = 0.0012 EUR (approx 833 NGN/EUR)
                'is_base' => false,
                'is_active' => true,
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
        Schema::dropIfExists('currencies');
    }
};
