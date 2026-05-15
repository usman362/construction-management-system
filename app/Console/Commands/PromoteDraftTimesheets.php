<?php

namespace App\Console\Commands;

use App\Models\Timesheet;
use Illuminate\Console\Command;

/**
 * Brenda 2026-05-12: "Can you set all timesheets that say draft to
 * submitted?" — one-shot cleanup for rows that were saved as `draft`
 * before the bulk-entry-to-submitted fix landed (commit 942a0d6).
 * Those rows are invisible to the Bulk Approval flow until they're
 * promoted to `submitted`.
 *
 * Usage:
 *   php artisan timesheets:promote-drafts            # dry-run (prints count)
 *   php artisan timesheets:promote-drafts --apply    # actually update
 */
class PromoteDraftTimesheets extends Command
{
    protected $signature = 'timesheets:promote-drafts
                            {--apply : Actually perform the update (without this flag, runs in dry-run mode)}
                            {--date-from= : Only promote rows on/after this date (YYYY-MM-DD)}
                            {--date-to= : Only promote rows on/before this date (YYYY-MM-DD)}';

    protected $description = 'Promote all draft timesheets to submitted status (one-off cleanup).';

    public function handle(): int
    {
        $q = Timesheet::query()->where('status', 'draft');

        if ($from = $this->option('date-from')) $q->whereDate('date', '>=', $from);
        if ($to   = $this->option('date-to'))   $q->whereDate('date', '<=', $to);

        $count = (clone $q)->count();
        $range = ($from || $to)
            ? sprintf(' in range %s – %s', $from ?: 'beginning', $to ?: 'today')
            : '';

        if ($count === 0) {
            $this->info("No draft timesheets found{$range} — nothing to do.");
            return self::SUCCESS;
        }

        $this->info("Found {$count} draft timesheet(s){$range}.");

        if (!$this->option('apply')) {
            $this->warn('Dry-run mode. Re-run with --apply to actually promote them to submitted.');
            return self::SUCCESS;
        }

        $updated = $q->update(['status' => 'submitted']);
        $this->info("Promoted {$updated} timesheet(s) draft → submitted.");
        return self::SUCCESS;
    }
}
