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
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('admin_notes');
            $table->unsignedBigInteger('replied_by')->nullable()->after('admin_reply');
            $table->timestamp('replied_at')->nullable()->after('replied_by');
            $table->timestamp('resolved_at')->nullable()->after('replied_at');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');

            $table->foreign('replied_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['replied_by']);
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['admin_reply', 'replied_by', 'replied_at', 'resolved_at', 'resolved_by']);
        });
    }
};
