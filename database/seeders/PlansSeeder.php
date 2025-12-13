<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Free Plan - Acquisition tool
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started with basic invoicing features',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'NGN',
                'max_invoices' => 5,
                'max_customers' => 3,
                'max_team_members' => 0,
                'sms_credits_monthly' => 0,
                'whatsapp_credits_monthly' => 0,
                'api_requests_monthly' => 0,
                'storage_gb' => 0.5,
                'multi_currency' => false,
                'recurring_invoices' => false,
                'api_access' => false,
                'remove_branding' => false,
                'white_label' => false,
                'custom_domain' => false,
                'priority_support' => false,
                'advanced_reports' => false,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],

            // Starter Plan - ₦5,000/month
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Ideal for freelancers and small businesses getting started',
                'price_monthly' => 5000,
                'price_yearly' => 50000, // 17% discount
                'currency' => 'NGN',
                'max_invoices' => 50,
                'max_customers' => 25,
                'max_team_members' => 1,
                'sms_credits_monthly' => 100,
                'whatsapp_credits_monthly' => 50,
                'api_requests_monthly' => 10000,
                'storage_gb' => 5,
                'multi_currency' => true,
                'recurring_invoices' => false,
                'api_access' => true,
                'remove_branding' => false,
                'white_label' => false,
                'custom_domain' => false,
                'priority_support' => false,
                'advanced_reports' => false,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
            ],

            // Professional Plan - ₦15,000/month (Most Popular)
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Complete solution for growing SMEs with unlimited invoices',
                'price_monthly' => 15000,
                'price_yearly' => 150000, // 17% discount
                'currency' => 'NGN',
                'max_invoices' => -1, // unlimited
                'max_customers' => -1, // unlimited
                'max_team_members' => 3,
                'sms_credits_monthly' => 500,
                'whatsapp_credits_monthly' => 200,
                'api_requests_monthly' => 100000,
                'storage_gb' => 20,
                'multi_currency' => true,
                'recurring_invoices' => true,
                'api_access' => true,
                'remove_branding' => true,
                'white_label' => false,
                'custom_domain' => false,
                'priority_support' => true,
                'advanced_reports' => true,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
            ],

            // Business Plan - ₦35,000/month
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Enterprise-grade features for agencies and large businesses',
                'price_monthly' => 35000,
                'price_yearly' => 350000, // 17% discount
                'currency' => 'NGN',
                'max_invoices' => -1, // unlimited
                'max_customers' => -1, // unlimited
                'max_team_members' => 10,
                'sms_credits_monthly' => 2000,
                'whatsapp_credits_monthly' => 1000,
                'api_requests_monthly' => 500000,
                'storage_gb' => 100,
                'multi_currency' => true,
                'recurring_invoices' => true,
                'api_access' => true,
                'remove_branding' => true,
                'white_label' => true,
                'custom_domain' => true,
                'priority_support' => true,
                'advanced_reports' => true,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('✅ Successfully seeded ' . count($plans) . ' subscription plans');
    }
}
