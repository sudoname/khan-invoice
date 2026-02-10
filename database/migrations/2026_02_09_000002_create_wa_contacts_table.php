<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone_e164')->index(); // E.164 format: +2348012345678
            $table->string('name')->nullable();
            $table->json('metadata')->nullable(); // Profile pic, locale, etc.
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone_e164']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_contacts');
    }
};
