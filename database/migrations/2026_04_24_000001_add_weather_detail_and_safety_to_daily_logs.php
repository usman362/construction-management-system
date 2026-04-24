<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            // Structured weather detail (keeps existing free-form `weather` + `temperature` columns).
            $table->decimal('temperature_high', 5, 1)->nullable()->after('temperature');
            $table->decimal('temperature_low', 5, 1)->nullable()->after('temperature_high');
            $table->string('precipitation', 100)->nullable()->after('temperature_low');
            $table->string('wind_speed', 50)->nullable()->after('precipitation');

            // Safety incident counters — let PM quickly surface crew safety trends.
            $table->unsignedSmallInteger('incidents_count')->default(0)->after('safety_issues');
            $table->unsignedSmallInteger('near_misses_count')->default(0)->after('incidents_count');
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn([
                'temperature_high',
                'temperature_low',
                'precipitation',
                'wind_speed',
                'incidents_count',
                'near_misses_count',
            ]);
        });
    }
};
