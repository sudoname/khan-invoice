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
        Schema::create('marketing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 100)->nullable(); // 'invoice-share', 'product', 'general', 'whatsapp-status'
            $table->string('aspect_ratio', 20)->nullable(); // '1:1', '9:16', '16:9', '4:5'
            $table->integer('width')->nullable(); // Canvas width in pixels
            $table->integer('height')->nullable(); // Canvas height in pixels
            $table->json('layout_schema')->nullable(); // Grid structure, safe margins, text zones
            $table->json('default_styles')->nullable(); // Font families, colors, spacing rules
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->string('preview_url', 500)->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            // Indexes for filtering
            $table->index('category');
            $table->index('aspect_ratio');
            $table->index(['is_active', 'is_premium']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_templates');
    }
};
