<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Brenda 2026-06-12: "Add Invoice" failing on save —
 *   SQLSTATE[HY000]: 1364 Field 'cost_code_id' doesn't have a default value
 *
 * The basic Add Invoice modal never exposed cost_code_id, but the column
 * was created NOT NULL. Make it nullable so quick invoice entry works.
 * Snap-an-Invoice and the new dropdown still set it when picked.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the FK first, change column to nullable, then re-add the FK.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['cost_code_id']);
        });
        DB::statement('ALTER TABLE invoices MODIFY cost_code_id BIGINT UNSIGNED NULL');
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('cost_code_id')->references('id')->on('cost_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // We don't restore NOT NULL — any rows added during this window
        // would already have NULL and the migration-down would fail anyway.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['cost_code_id']);
            $table->foreign('cost_code_id')->references('id')->on('cost_codes')->cascadeOnDelete();
        });
    }
};
