<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wa_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('whatsapp'); // whatsapp, web, manual
            $table->enum('stage', ['new', 'qualified', 'invoiced', 'paid', 'lost'])->default('new');
            $table->unsignedTinyInteger('score')->default(0); // 0-100
            $table->string('product_interest')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'stage', 'last_activity_at']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
