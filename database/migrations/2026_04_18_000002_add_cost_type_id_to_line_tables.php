<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per client: Phase Code + Cost Type are two independent fields on every
 * line entry. The same Phase Code can be charged against different Cost
 * Types (e.g. 01.10.000 T&M Labor → Direct Labor OR Indirect Labor).
 * So we add a nullable cost_type_id FK to each line-table where the user
 * enters cost data:
 *   - budget_lines
 *   - estimate_lines
 *   - commitments
 *   - purchase_orders
 *   - cost_entries
 */
return new class extends Migration
{
    private array $tables = [
        'budget_lines',
        'estimate_lines',
        'commitments',
        'purchase_orders',
        'cost_entries',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'cost_type_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('cost_type_id')
                        ->nullable()
                        ->after('cost_code_id')
                        ->constrained('cost_types')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'cost_type_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['cost_type_id']);
                    $t->dropColumn('cost_type_id');
                });
            }
        }
    }
};
