<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Check if columns don't already exist before adding
            if (!Schema::hasColumn('invoices', 'wa_conversation_id')) {
                $table->foreignId('wa_conversation_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'wa_contact_id')) {
                $table->foreignId('wa_contact_id')->nullable()->after('wa_conversation_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'whatsapp_last_followup_at')) {
                $table->timestamp('whatsapp_last_followup_at')->nullable();
            }

            if (!Schema::hasColumn('invoices', 'whatsapp_followup_attempts')) {
                $table->unsignedSmallInteger('whatsapp_followup_attempts')->default(0);
            }

            // Add indexes if they don't exist
            // Using raw SQL to avoid Doctrine DBAL dependency in Laravel 12
            $indexExists = function($indexName) {
                $result = \DB::select("SHOW INDEX FROM invoices WHERE Key_name = ?", [$indexName]);
                return !empty($result);
            };

            if (!$indexExists('invoices_wa_conversation_id_index')) {
                $table->index('wa_conversation_id');
            }

            if (!$indexExists('invoices_wa_contact_id_index')) {
                $table->index('wa_contact_id');
            }

            if (!$indexExists('invoices_wa_followup_idx')) {
                $table->index(['whatsapp_last_followup_at', 'whatsapp_followup_attempts'], 'invoices_wa_followup_idx');
            }
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
