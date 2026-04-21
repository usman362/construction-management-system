<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client request: "I did not add a spot for the work order number that my
 * shop uses on their timesheets. Can you add an optional tab on the add new
 * timesheet and the bulk entry timesheet for work order number?"
 *
 * Optional free-text column — the shop uses this to tie a timesheet row back
 * to an internal work order number that isn't the same as the project number.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timesheets') && !Schema::hasColumn('timesheets', 'work_order_number')) {
            Schema::table('timesheets', function (Blueprint $table) {
                $table->string('work_order_number', 100)->nullable()->after('shift_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('timesheets', 'work_order_number')) {
            Schema::table('timesheets', function (Blueprint $table) {
                $table->dropColumn('work_order_number');
            });
        }
    }
};
