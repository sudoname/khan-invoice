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
        // Update existing users to have payment_reminder and invoice_overdue OFF by default
        DB::table('notification_preferences')
            ->update([
                'email_payment_reminder' => false,
                'email_invoice_overdue' => false,
                'sms_payment_reminder' => false,
                'sms_invoice_overdue' => false,
            ]);

        // Change column defaults for future users
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('email_payment_reminder')->default(false)->change();
            $table->boolean('email_invoice_overdue')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original defaults
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('email_payment_reminder')->default(true)->change();
            $table->boolean('email_invoice_overdue')->default(true)->change();
        });
    }
};
