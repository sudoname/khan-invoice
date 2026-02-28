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
        Schema::create('marketing_designs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Nullable for demo mode
            $table->foreignId('template_id')->constrained('marketing_templates')->cascadeOnDelete();
            $table->foreignId('brand_kit_id')->nullable()->constrained('brand_kits')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');

            $table->string('title')->nullable();
            $table->text('prompt')->nullable(); // User's original prompt
            $table->json('design_json')->nullable(); // Claude-generated design structure
            $table->string('rendered_url', 500)->nullable(); // S3 URL of final PNG

            $table->string('status', 50)->default('draft'); // 'draft', 'rendering', 'completed', 'failed'
            $table->integer('render_attempts')->default(0);
            $table->text('render_error')->nullable();

            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('file_size')->nullable(); // bytes

            $table->timestamp('shared_at')->nullable();
            $table->integer('download_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('status');
            $table->index('invoice_id');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_designs');
    }
};
