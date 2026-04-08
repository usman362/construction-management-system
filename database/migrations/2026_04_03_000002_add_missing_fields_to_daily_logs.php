<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_logs', 'visitors')) {
                $table->string('visitors')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('daily_logs', 'safety_issues')) {
                $table->text('safety_issues')->nullable()->after('visitors');
            }
            if (!Schema::hasColumn('daily_logs', 'delays')) {
                $table->text('delays')->nullable()->after('safety_issues');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn(['visitors', 'safety_issues', 'delays']);
        });
    }
};
