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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->json('environments')->nullable(); // ['staging', 'production']
            $table->json('rules')->nullable(); // Future: user_ids, business_ids
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();

            $table->index(['key', 'enabled']);
        });

        // Seed initial flags (ALL DISABLED by default)
        DB::table('feature_flags')->insert([
            [
                'key' => 'payment_orchestration',
                'name' => 'Payment Orchestration Layer',
                'description' => 'Use new payment service for invoices',
                'enabled' => false,
                'environments' => json_encode(['staging']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'partial_payments',
                'name' => 'Partial Payments',
                'description' => 'Allow customers to pay less than invoice amount',
                'enabled' => false,
                'environments' => json_encode(['staging']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_reminders',
                'name' => 'Automatic Invoice Reminders',
                'description' => 'Send automated payment reminders',
                'enabled' => false,
                'environments' => json_encode(['staging']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'instant_payout',
                'name' => 'Instant Payout (Premium)',
                'description' => 'Faster payout with additional fee',
                'enabled' => false,
                'environments' => json_encode(['staging']),
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
        Schema::dropIfExists('feature_flags');
    }
};
