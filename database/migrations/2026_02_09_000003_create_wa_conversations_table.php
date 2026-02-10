<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wa_contact_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['open', 'paused', 'handoff', 'closed'])->default('open');
            $table->string('state')->default('idle'); // State machine: idle, collecting_invoice, awaiting_payment, etc.
            $table->string('last_intent')->nullable(); // Last detected intent
            $table->json('context')->nullable(); // Collected fields, cart, metadata
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->boolean('human_handoff')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status', 'last_message_at']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversations');
    }
};
