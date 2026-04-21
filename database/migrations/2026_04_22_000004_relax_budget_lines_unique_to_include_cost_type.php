<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Relax the (project_id, cost_code_id) uniqueness so the SAME phase code can
 * appear twice on a single project — once per cost type. Client needs this so
 * they can budget Direct Labor AND Indirect Labor against the same phase code
 * (e.g. 01.10.000 used for both). New unique key: (project_id, cost_code_id,
 * cost_type_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: check the old single-pair index actually exists before dropping.
        $oldIndex = DB::selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'budget_lines'
               AND INDEX_NAME   = 'budget_lines_project_id_cost_code_id_unique'"
        );
        if ($oldIndex && $oldIndex->n > 0) {
            Schema::table('budget_lines', function (Blueprint $table) {
                $table->dropUnique('budget_lines_project_id_cost_code_id_unique');
            });
        }

        $newIndex = DB::selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'budget_lines'
               AND INDEX_NAME   = 'budget_lines_project_cost_code_cost_type_unique'"
        );
        if (!$newIndex || $newIndex->n == 0) {
            Schema::table('budget_lines', function (Blueprint $table) {
                $table->unique(
                    ['project_id', 'cost_code_id', 'cost_type_id'],
                    'budget_lines_project_cost_code_cost_type_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropUnique('budget_lines_project_cost_code_cost_type_unique');
            $table->unique(['project_id', 'cost_code_id'], 'budget_lines_project_id_cost_code_id_unique');
        });
    }
};
