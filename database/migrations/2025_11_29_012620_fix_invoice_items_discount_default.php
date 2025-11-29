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
        // Update any existing NULL discount values to 0
        \DB::table('invoice_items')->whereNull('discount')->update(['discount' => 0]);

        // For SQLite, we need to recreate the table to properly add the default constraint
        // This is a limitation of SQLite's ALTER TABLE
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this fix
    }
};
