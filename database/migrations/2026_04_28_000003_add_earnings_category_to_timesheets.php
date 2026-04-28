<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Earnings category on timesheets (Brenda 04.28.2026).
 *
 * Her legacy payroll system uses 2-letter codes that classify what kind of
 * earnings the hours represent:
 *   - HE = Hourly Earnings (worked hours; ST/OT split applies)
 *   - HO = Holiday        (paid time off, flat rate, no OT)
 *   - VA = Vacation       (paid time off, flat rate, no OT)
 *
 * Default to 'HE' so existing timesheets continue to behave as worked hours.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->string('earnings_category', 4)->default('HE')->after('rate_type')
                ->comment('HE=Hourly Earnings, HO=Holiday, VA=Vacation');
        });

        // Backfill existing rows to 'HE' explicitly (default already set, but
        // makes it audit-trail-friendly).
        \DB::table('timesheets')->whereNull('earnings_category')->update(['earnings_category' => 'HE']);
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('earnings_category');
        });
    }
};
