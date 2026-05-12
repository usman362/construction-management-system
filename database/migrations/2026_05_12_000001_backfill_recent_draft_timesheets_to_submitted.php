<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: flip recent draft timesheets to 'submitted'.
 *
 * 2026-05-12 — TimesheetController::store() was hardcoding status='draft'
 * regardless of what the request sent. The bulk-entry page sends
 * status='submitted', but the field was silently discarded, leaving every
 * keyed-in timesheet stuck as 'draft' and invisible to Bulk Approval.
 *
 * Brenda hit this today: "I am trying the bulk approval and it is telling
 * me that there are not timesheets, but I keyed them today." The store()
 * bug is fixed in the same commit. This migration cleans up the existing
 * drafts so her in-flight batch is approvable on next deploy.
 *
 * Safety rails:
 *  - Only touches drafts created in the last 14 days (catches recent batches,
 *    leaves any older intentional drafts alone).
 *  - Idempotent — running again finds nothing new to flip.
 *  - Reversible (down() flips them back to draft).
 */
return new class extends Migration {
    public function up(): void
    {
        $count = DB::table('timesheets')
            ->where('status', 'draft')
            ->where('created_at', '>=', now()->subDays(14))
            ->update(['status' => 'submitted']);

        if ($count > 0) {
            // Visible in the deploy log so Brenda + Usman can confirm the cleanup
            // ran without having to query the table by hand.
            echo "  [backfill] flipped {$count} draft timesheets → submitted\n";
        }
    }

    public function down(): void
    {
        // Best-effort reverse — flips back any submitted-but-never-approved
        // rows from the same window. Won't undo a manual approval after the fact.
        DB::table('timesheets')
            ->where('status', 'submitted')
            ->whereNull('approved_at')
            ->where('created_at', '>=', now()->subDays(14))
            ->update(['status' => 'draft']);
    }
};
