<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('whatsapp'); // whatsapp, email, sms
            $table->enum('type', ['unpaid_invoice_followup', 'abandoned_cart_followup', 'lead_nurture'])->default('unpaid_invoice_followup');
            $table->boolean('enabled')->default(true);
            $table->string('name');
            $table->json('trigger'); // e.g., {"invoice_status":"sent","older_than_minutes":60}
            $table->json('schedule'); // e.g., {"attempts":[60,1440,4320]} minutes after trigger
            $table->text('message_template'); // Supports variables: {{customer_name}}, etc.
            $table->json('constraints')->nullable(); // business_hours, max_per_day, opt_out_check
            $table->timestamps();

            $table->index(['user_id', 'enabled', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
