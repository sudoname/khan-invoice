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
        // Invoices table indexes
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('business_profile_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('due_date');
            // public_id unique constraint already exists from earlier migration
            $table->index('invoice_number');
            $table->index('created_at');
        });

        // Customers table indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('email');
        });

        // Invoice items table indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id');
        });

        // Business profiles table indexes
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove invoices table indexes
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['business_profile_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            // public_id unique constraint managed by earlier migration
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['created_at']);
        });

        // Remove customers table indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['email']);
        });

        // Remove invoice items table indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
        });

        // Remove business profiles table indexes
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        // Remove payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['payment_date']);
        });
    }
};
