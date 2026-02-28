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
        Schema::create('generation_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('marketing_designs')->onDelete('cascade');
            $table->integer('priority')->default(5); // 1 = highest, 10 = lowest
            $table->string('status', 50)->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for queue processing
            $table->index('status');
            $table->index(['status', 'priority', 'created_at']); // Composite index for queue ordering
            $table->index('design_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_queue');
    }
};
