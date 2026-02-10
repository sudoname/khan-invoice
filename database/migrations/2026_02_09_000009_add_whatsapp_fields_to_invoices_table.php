<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('wa_conversation_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('wa_contact_id')->nullable()->after('wa_conversation_id')->constrained()->nullOnDelete();
            $table->timestamp('whatsapp_last_followup_at')->nullable();
            $table->unsignedSmallInteger('whatsapp_followup_attempts')->default(0);

            $table->index('wa_conversation_id');
            $table->index('wa_contact_id');
            $table->index(['whatsapp_last_followup_at', 'whatsapp_followup_attempts'], 'invoices_wa_followup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_wa_followup_idx');
            $table->dropForeign(['wa_conversation_id']);
            $table->dropForeign(['wa_contact_id']);
            $table->dropColumn([
                'wa_conversation_id',
                'wa_contact_id',
                'whatsapp_last_followup_at',
                'whatsapp_followup_attempts',
            ]);
        });
    }
};
