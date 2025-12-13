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
        Schema::table('notification_preferences', function (Blueprint $table) {
            // WhatsApp preferences (similar to SMS)
            $table->boolean('whatsapp_enabled')->default(false)->after('sms_invoice_overdue');
            $table->integer('whatsapp_credits_remaining')->default(0)->after('whatsapp_enabled');
            $table->boolean('whatsapp_payment_received')->default(false)->after('whatsapp_credits_remaining');
            $table->boolean('whatsapp_invoice_sent')->default(false)->after('whatsapp_payment_received');
            $table->boolean('whatsapp_payment_reminder')->default(false)->after('whatsapp_invoice_sent');
            $table->boolean('whatsapp_invoice_overdue')->default(false)->after('whatsapp_payment_reminder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_enabled',
                'whatsapp_credits_remaining',
                'whatsapp_payment_received',
                'whatsapp_invoice_sent',
                'whatsapp_payment_reminder',
                'whatsapp_invoice_overdue',
            ]);
        });
    }
};
