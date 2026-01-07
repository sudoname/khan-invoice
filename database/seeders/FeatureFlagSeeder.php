<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureFlag;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flags = [
            [
                'key' => 'instant_payouts',
                'name' => 'Instant Payouts (Premium)',
                'description' => 'Allows merchants to request instant payouts with a 2% fee. Payouts are processed within 1 hour instead of T+1.',
                'enabled' => false, // Disabled by default
                'environments' => ['production', 'staging'],
                'rules' => null,
            ],
            [
                'key' => 'automated_reminders',
                'name' => 'Automated Invoice Reminders',
                'description' => 'Enables automated email, SMS, and WhatsApp reminders for unpaid invoices.',
                'enabled' => true,
                'environments' => null, // All environments
                'rules' => null,
            ],
            [
                'key' => 'partial_payments',
                'name' => 'Partial Payments',
                'description' => 'Allows customers to make partial payments on invoices.',
                'enabled' => true,
                'environments' => null,
                'rules' => null,
            ],
            [
                'key' => 'whatsapp_integration',
                'name' => 'WhatsApp Integration',
                'description' => 'Enables WhatsApp invoice sharing and payment links.',
                'enabled' => true,
                'environments' => null,
                'rules' => null,
            ],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::updateOrCreate(
                ['key' => $flag['key']],
                $flag
            );
        }

        $this->command->info('Feature flags seeded successfully!');
    }
}
