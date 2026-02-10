<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone_e164')->index();
            $table->string('reason')->nullable(); // User said STOP, complained, etc.
            $table->timestamp('created_at');

            $table->unique(['user_id', 'phone_e164']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_opt_outs');
    }
};
