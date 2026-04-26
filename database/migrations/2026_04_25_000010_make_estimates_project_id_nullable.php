<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Standalone-bid support — make estimates.project_id nullable.
 *
 * The original `estimates` table required a project_id, which made sense
 * when estimates were always created from inside a project. With the new
 * top-level Estimates portfolio (Brenda 04.25.2026), bids can exist before
 * any project does — project_id is back-filled when the bid is accepted
 * and converted (or when "Open as Project Draft" is clicked).
 *
 * Using raw SQL because Laravel's column->change() needs doctrine/dbal,
 * which isn't installed.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE estimates MODIFY COLUMN project_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Best-effort revert. Won't restore the FK constraint but won't break.
        DB::statement('ALTER TABLE estimates MODIFY COLUMN project_id BIGINT UNSIGNED NOT NULL');
    }
};
