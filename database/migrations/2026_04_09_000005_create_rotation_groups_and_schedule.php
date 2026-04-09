<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rotation schedules mirror the "Rotation Schedule" tab on legacy Excel
 * templates (e.g., BM-5286 Nucor Maintenance):
 *
 *   GROUP 1              GROUP 3
 *   Week Ending | Rot    Week Ending | Rot
 *   2025-09-21  | Yes    2025-09-21  | Yes
 *   2025-09-28  | No     2025-09-28  | No
 *   ...
 *
 * Supports both patterns the user runs:
 *   - Rolling 4's (BM-5400 Mat'lHand): 4 on / 4 off, single shift
 *   - Rolling 8's (BM-11367 Operators): 4 on days → 4 off → 4 on nights
 *
 * Employees link to a group via employees.rotation_group_id. A group's
 * working weeks + shift type for each week are stored in rotation_schedule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('code', 50);                  // "Group 1", "A-Shift", "NF Group 3"
            $table->string('name', 100)->nullable();
            $table->enum('pattern', [
                '4_on_4_off',                             // Rolling 4's
                '8_on_8_off_rotating',                    // Rolling 8's (days ↔ nights)
                '4_on_3_off',
                'custom',
            ])->default('custom');
            $table->enum('current_shift', ['days', 'nights', 'swing'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'code']);
        });

        Schema::create('rotation_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rotation_group_id')->constrained('rotation_groups')->cascadeOnDelete();
            $table->date('week_ending_date');
            $table->boolean('is_working')->default(false);  // Yes / No from the Excel tab
            $table->enum('shift_type', ['days', 'nights', 'swing'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['rotation_group_id', 'week_ending_date'], 'rotation_schedule_group_week_unique');
            $table->index('week_ending_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('rotation_group_id')
                ->nullable()
                ->after('craft_id')
                ->constrained('rotation_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['rotation_group_id']);
            $table->dropColumn('rotation_group_id');
        });

        Schema::dropIfExists('rotation_schedule');
        Schema::dropIfExists('rotation_groups');
    }
};
