<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per Rev 3 Notes (R3): Change Orders screen should match the Commitments
 * layout. Commitments already have both Phase Code (cost_code_id) + Cost Type
 * (cost_type_id); change_order_items only has cost_code_id. Add cost_type_id
 * so CO line items carry the same two-axis classification, which lets the
 * list view surface "Cost Type" as its own column and keeps the data
 * compatible with cost reporting that groups by type.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('change_order_items') && !Schema::hasColumn('change_order_items', 'cost_type_id')) {
            Schema::table('change_order_items', function (Blueprint $table) {
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
        if (Schema::hasColumn('change_order_items', 'cost_type_id')) {
            Schema::table('change_order_items', function (Blueprint $table) {
                $table->dropForeign(['cost_type_id']);
                $table->dropColumn('cost_type_id');
            });
        }
    }
};
