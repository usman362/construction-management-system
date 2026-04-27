<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brenda 04.28.2026 — rental return tracking.
 *
 *   "For the third party equipment I rent on purchase orders, can we build
 *    a bar calendar that shows the rental duration and have it email me
 *    when it is getting close to the off rent date?"
 *
 * To answer "when is the rental due back?", we add `expected_return_date`
 * to equipment_assignments. The QR check-out flow asks for it; the bar
 * calendar uses it as the right edge of each rental bar; the scheduled
 * `equipment:rental-expiry-alerts` command emails when it's approaching.
 *
 * Nullable so existing assignments don't break — they just won't surface
 * on the calendar's "expiring soon" alert until someone fills them in.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->date('expected_return_date')->nullable()->after('assigned_date');
            $table->index(['expected_return_date', 'returned_date'], 'eq_assn_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->dropIndex('eq_assn_expiry_idx');
            $table->dropColumn('expected_return_date');
        });
    }
};
