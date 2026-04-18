<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: "We will also need cost type added to the timesheet entry.
 * Singular timesheet and bulk entry."
 *
 * We already have cost_code_id on timesheet_cost_allocations; add
 * cost_type_id alongside it so each allocation carries both Phase Code
 * and Cost Type (Direct Labor, Indirect Labor, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timesheet_cost_allocations') && !Schema::hasColumn('timesheet_cost_allocations', 'cost_type_id')) {
            Schema::table('timesheet_cost_allocations', function (Blueprint $table) {
                $table->foreignId('cost_type_id')
                    ->nullable()
                    ->after('cost_code_id')
                    ->constrained('cost_types')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('timesheets') && !Schema::hasColumn('timesheets', 'cost_type_id')) {
            Schema::table('timesheets', function (Blueprint $table) {
                $table->foreignId('cost_type_id')
                    ->nullable()
                    ->after('cost_code_id')
                    ->constrained('cost_types')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['timesheet_cost_allocations', 'timesheets'] as $table) {
            if (Schema::hasColumn($table, 'cost_type_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['cost_type_id']);
                    $t->dropColumn('cost_type_id');
                });
            }
        }
    }
};
