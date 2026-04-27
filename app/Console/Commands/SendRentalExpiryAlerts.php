<?php

namespace App\Console\Commands;

use App\Models\EquipmentAssignment;
use App\Models\User;
use App\Notifications\RentalExpiringAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily rental-expiry digest. Run by scheduler at 7am weekdays.
 *
 * Pulls every open assignment with an expected_return_date and buckets
 * them by urgency (overdue / 1-day / 2-3 / 4-7). Sends ONE digest email
 * per admin / PM that contains all four buckets. No notification fires
 * if there's nothing to alert on.
 */
class SendRentalExpiryAlerts extends Command
{
    protected $signature = 'equipment:rental-expiry-alerts
                            {--dry-run : Preview without sending}';

    protected $description = 'Email a digest of equipment rentals approaching their off-rent date.';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $in1   = $today->copy()->addDays(1);
        $in3   = $today->copy()->addDays(3);
        $in7   = $today->copy()->addDays(7);

        $assignments = EquipmentAssignment::query()
            ->whereNull('returned_date')
            ->whereNotNull('expected_return_date')
            ->whereDate('expected_return_date', '<=', $in7)
            ->with(['equipment.vendor:id,name', 'project:id,project_number,name'])
            ->get();

        $overdue = $assignments->filter(fn ($a) => $a->expected_return_date->lt($today))->values();
        $b1      = $assignments->filter(fn ($a) => $a->expected_return_date->gte($today) && $a->expected_return_date->lte($in1))->values();
        $b3      = $assignments->filter(fn ($a) => $a->expected_return_date->gt($in1) && $a->expected_return_date->lte($in3))->values();
        $b7      = $assignments->filter(fn ($a) => $a->expected_return_date->gt($in3) && $a->expected_return_date->lte($in7))->values();

        $total = $overdue->count() + $b1->count() + $b3->count() + $b7->count();
        $this->info("Rental expiry digest: {$overdue->count()} overdue · {$b1->count()} due ≤1d · {$b3->count()} ≤3d · {$b7->count()} ≤7d (total {$total})");

        if ($total === 0) {
            $this->line('Nothing to send — no rentals approaching off-rent.');
            return self::SUCCESS;
        }

        $recipients = User::notifiableForRoles([User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER]);
        if ($recipients->isEmpty()) {
            $this->warn('No admin / PM users with email — skipping.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('--dry-run: would email ' . $recipients->count() . ' user(s).');
            return self::SUCCESS;
        }

        Notification::send($recipients, new RentalExpiringAlert($overdue, $b1, $b3, $b7));
        $this->info('Digest sent to ' . $recipients->count() . ' user(s).');

        return self::SUCCESS;
    }
}
