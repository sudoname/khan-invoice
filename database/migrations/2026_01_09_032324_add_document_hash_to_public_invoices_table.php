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
        Schema::table('public_invoices', function (Blueprint $table) {
            $table->string('document_hash', 64)->nullable()->index()->after('status');
            $table->timestamp('document_hash_updated_at')->nullable()->after('document_hash');
            $table->unsignedTinyInteger('document_hash_version')->default(1)->after('document_hash_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            $table->dropColumn(['document_hash', 'document_hash_updated_at', 'document_hash_version']);
        });
    }
};
