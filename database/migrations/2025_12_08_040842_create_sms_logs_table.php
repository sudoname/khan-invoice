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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_phone');
            $table->string('message_type'); // payment_received, invoice_sent, etc.
            $table->text('message_content');
            $table->string('status'); // sent, failed, pending
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('cost', 8, 4)->default(0); // Cost per SMS
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
