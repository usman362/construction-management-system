<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client request: "Can we put a place in the employee file for the direct
 * labor and indirect labor cost type so when we do a BULK timesheet it
 * defaults to what's in their employee file and if I need to change it, I
 * can change it on the timesheet."
 *
 * FK → cost_types so bulk-entry can pre-fill the per-row Cost Type dropdown
 * to the employee's normal classification (Direct Labor, Indirect Labor,
 * etc.), while still letting the user override per timesheet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'default_cost_type_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('default_cost_type_id')
                    ->nullable()
                    ->after('classification')
                    ->constrained('cost_types')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'default_cost_type_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['default_cost_type_id']);
                $table->dropColumn('default_cost_type_id');
            });
        }
    }
};
