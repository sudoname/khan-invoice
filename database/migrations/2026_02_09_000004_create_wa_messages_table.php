<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wa_conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('message_type')->default('text'); // text, image, interactive, template
            $table->text('body')->nullable();
            $table->json('payload')->nullable(); // Raw parsed payload
            $table->string('provider_message_id')->nullable()->index(); // Meta message id
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'wa_conversation_id', 'created_at']);
            $table->index('direction');
        });

        // Add unique constraint for idempotency on inbound messages
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->unique('provider_message_id', 'wa_messages_provider_message_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};
