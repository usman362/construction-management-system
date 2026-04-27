<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BUG FIX (Brenda 04.27.2026):
 *
 *   "I am trying to set up a new project and it is not saving."
 *
 * Root cause: the `projects.contract_value` and `projects.retainage_percent`
 * columns were defined as NOT NULL with a default of '0.00'. The Project
 * create form leaves these fields blank when the user doesn't have a
 * contract value or retainage % yet (typical for early-stage bids).
 * Laravel's `ConvertEmptyStringsToNull` middleware turns "" → NULL, the
 * controller's `nullable|numeric` validation lets NULL pass, and then the
 * INSERT explodes because the DB column doesn't accept NULL.
 *
 * Fix: make both columns nullable. Existing rows keep their 0.00 defaults
 * — no data churn, just lifts the constraint that was blocking saves.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE projects MODIFY COLUMN contract_value DECIMAL(15, 2) NULL DEFAULT 0.00');
        DB::statement('ALTER TABLE projects MODIFY COLUMN retainage_percent DECIMAL(5, 2) NULL DEFAULT 0.00');
    }

    public function down(): void
    {
        // Backfill any nulls that snuck in before re-applying NOT NULL.
        DB::statement('UPDATE projects SET contract_value = 0.00 WHERE contract_value IS NULL');
        DB::statement('UPDATE projects SET retainage_percent = 0.00 WHERE retainage_percent IS NULL');
        DB::statement('ALTER TABLE projects MODIFY COLUMN contract_value DECIMAL(15, 2) NOT NULL DEFAULT 0.00');
        DB::statement('ALTER TABLE projects MODIFY COLUMN retainage_percent DECIMAL(5, 2) NOT NULL DEFAULT 0.00');
    }
};
