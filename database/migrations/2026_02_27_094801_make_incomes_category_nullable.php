<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // Use raw SQL to avoid Doctrine DBAL dependency issues in Laravel 12
        DB::statement('ALTER TABLE incomes MODIFY COLUMN category VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE incomes MODIFY COLUMN category VARCHAR(255) NOT NULL');
    }
};
