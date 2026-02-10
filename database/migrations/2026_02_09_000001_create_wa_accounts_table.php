<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('meta'); // meta, termii, twilio
            $table->string('phone_number')->nullable(); // Display number
            $table->string('phone_number_id')->nullable()->index(); // Meta phone_number_id
            $table->string('waba_id')->nullable(); // WhatsApp Business Account ID
            $table->text('access_token')->nullable(); // Encrypted
            $table->string('verify_token')->nullable(); // Encrypted, per-user webhook verify
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable(); // Templates, language, etc.
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_accounts');
    }
};
