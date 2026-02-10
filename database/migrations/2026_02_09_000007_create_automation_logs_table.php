<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wa_conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_type')->nullable(); // invoice, lead, conversation
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('action'); // send_message, create_invoice, mark_paid, etc.
            $table->enum('status', ['success', 'skipped', 'failed'])->default('success');
            $table->json('meta')->nullable(); // Action details, error messages, etc.
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index('automation_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
