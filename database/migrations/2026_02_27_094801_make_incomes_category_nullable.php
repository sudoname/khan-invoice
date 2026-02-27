<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: SQLSTATE[HY000]: General error: 1364 Field 'category' doesn't have a default value
     *
     * The 'category' field is a fallback field for the old system. It should be nullable
     * since the form hides it when custom categories (income_category_id) exist.
     */
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
        });
    }
};
