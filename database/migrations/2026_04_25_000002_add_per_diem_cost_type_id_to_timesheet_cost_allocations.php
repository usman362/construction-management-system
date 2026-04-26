<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per diem decoupling — give per_diem_amount its own cost type, separate from
 * the labor cost type the rest of the allocation lives under.
 *
 * Background:
 *   Each cost allocation row has a labor cost (under e.g. "Direct Labor" cost
 *   type) AND a per_diem_amount. Reports group only by the labor cost type, so
 *   per diem dollars get rolled into the labor bucket — the cost tracker can't
 *   see how much of "Direct Labor committed" is wages vs. per-diem allowance.
 *
 * Fix:
 *   - Add per_diem_cost_type_id to allocations.
 *   - Backfill any existing row with per_diem_amount > 0 to point at the
 *     "PER DIEM" cost type (code 07), which is already seeded.
 *   - Reports will then route per diem dollars to that bucket separately.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('timesheet_cost_allocations', function (Blueprint $table) {
            $table->foreignId('per_diem_cost_type_id')
                ->nullable()
                ->after('cost_type_id')
                ->constrained('cost_types')
                ->nullOnDelete();
        });

        // Backfill: every existing row that has a per diem amount gets the
        // "PER DIEM" cost type (code 07). We resolve the id by code rather
        // than hard-coding so it works on any seeded environment.
        $perDiemId = DB::table('cost_types')->where('code', '07')->value('id');
        if ($perDiemId) {
            DB::table('timesheet_cost_allocations')
                ->where('per_diem_amount', '>', 0)
                ->whereNull('per_diem_cost_type_id')
                ->update(['per_diem_cost_type_id' => $perDiemId]);
        }
    }

    public function down(): void
    {
        Schema::table('timesheet_cost_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('per_diem_cost_type_id');
        });
    }
};
