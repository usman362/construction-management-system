<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-12 (Brenda — Phase 1 / Cert Expiry Alerts).
 *
 * Per-employee expiry emails need a way to avoid spamming on every
 * scheduler run. We mark *when* each milestone (60d / 30d / 7d / expired)
 * was emailed so the next run can skip it. A model observer wipes these
 * flags whenever expiry_date changes, so renewing a cert resets the
 * notification cycle automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_certifications', function (Blueprint $table) {
            $table->timestamp('notice_60_sent_at')->nullable()->after('uploaded_by');
            $table->timestamp('notice_30_sent_at')->nullable()->after('notice_60_sent_at');
            $table->timestamp('notice_7_sent_at')->nullable()->after('notice_30_sent_at');
            $table->timestamp('notice_expired_sent_at')->nullable()->after('notice_7_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_certifications', function (Blueprint $table) {
            $table->dropColumn([
                'notice_60_sent_at',
                'notice_30_sent_at',
                'notice_7_sent_at',
                'notice_expired_sent_at',
            ]);
        });
    }
};
