<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BUG FIX (Brenda 04.28.2026, follow-up):
 *
 *   "When I click create project, it doesn't save and gives no error."
 *
 * The /projects/create dedicated page (not the modal — that was already
 * fixed in 2026_04_27_000004) sends `estimate` as part of its payload.
 * That column on the `projects` table was defined NOT NULL with default
 * '0.00'. When the user leaves Estimate blank, middleware turns "" → NULL,
 * `nullable|numeric` validation lets it pass, and the INSERT then fails at
 * MySQL with "Column 'estimate' cannot be null".
 *
 * Same root pattern as the contract_value/retainage_percent fix — relax
 * the column to allow NULL while preserving the default for existing rows.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE projects MODIFY COLUMN estimate DECIMAL(15, 2) NULL DEFAULT 0.00');
    }

    public function down(): void
    {
        DB::statement('UPDATE projects SET estimate = 0.00 WHERE estimate IS NULL');
        DB::statement('ALTER TABLE projects MODIFY COLUMN estimate DECIMAL(15, 2) NOT NULL DEFAULT 0.00');
    }
};
