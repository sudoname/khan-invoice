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
        Schema::create('brand_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Default Brand Kit');
            $table->string('logo_url', 500)->nullable();
            $table->string('primary_color', 7)->nullable(); // Hex color #RRGGBB
            $table->string('secondary_color', 7)->nullable();
            $table->string('accent_color', 7)->nullable();
            $table->string('font_heading', 100)->default('Inter');
            $table->string('font_body', 100)->default('Inter');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_kits');
    }
};
