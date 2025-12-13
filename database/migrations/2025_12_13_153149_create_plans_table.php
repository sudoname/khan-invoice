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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Professional, Business
            $table->string('slug')->unique(); // free, starter, professional, business
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0); // Monthly price in Naira
            $table->decimal('price_yearly', 10, 2)->default(0); // Yearly price (discounted)
            $table->string('currency', 3)->default('NGN');

            // Limits
            $table->integer('max_invoices')->default(-1); // -1 = unlimited
            $table->integer('max_customers')->default(-1);
            $table->integer('max_team_members')->default(1);
            $table->integer('sms_credits_monthly')->default(0);
            $table->integer('whatsapp_credits_monthly')->default(0);
            $table->integer('api_requests_monthly')->default(0);
            $table->decimal('storage_gb', 8, 2)->default(1);

            // Features
            $table->boolean('multi_currency')->default(false);
            $table->boolean('recurring_invoices')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('remove_branding')->default(false);
            $table->boolean('white_label')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('advanced_reports')->default(false);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
