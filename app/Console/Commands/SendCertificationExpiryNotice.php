<?php

namespace App\Console\Commands;

use App\Models\EmployeeCertification;
use App\Models\User;
use App\Notifications\CertificationExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily cert expiry digest.
 *
 * Run by scheduler each weekday morning. Pulls every certification with an
 * expiry_date, buckets by urgency (expired, ≤30, 31-60, 61-90 days), and
 * sends ONE digest email per admin/accountant containing all four buckets.
 *
 * Why a digest instead of one email per cert: HR has dozens of certs to
 * track. A single morning summary they can scan once is far more useful
 * than 12 separate "X is expiring" emails.
 */
class SendCertificationExpiryNotice extends Command
{
    protected $signature = 'certs:notify-expiring
                            {--dry-run : Preview the digest without sending}';

    protected $description = 'Email a 90-day cert-expiry watch digest to admins/accountants.';

    public function handle(): int
    {
        $now  = now()->startOfDay();
        $in30 = $now->copy()->addDays(30);
        $in60 = $now->copy()->addDays(60);
        $in90 = $now->copy()->addDays(90);

        $all = EmployeeCertification::whereNotNull('expiry_date')
            ->with('employee:id,first_name,last_name,employee_number')
            ->orderBy('expiry_date')
            ->get();

        $expired = $all->filter(fn ($c) => $c->expiry_date->lt($now))->values();
        $b30     = $all->filter(fn ($c) => $c->expiry_date->gte($now)  && $c->expiry_date->lt($in30))->values();
        $b60     = $all->filter(fn ($c) => $c->expiry_date->gte($in30) && $c->expiry_date->lt($in60))->values();
        $b90     = $all->filter(fn ($c) => $c->expiry_date->gte($in60) && $c->expiry_date->lt($in90))->values();

        $total = $expired->count() + $b30->count() + $b60->count() + $b90->count();
        $this->info("Cert expiry digest: {$expired->count()} expired · {$b30->count()} ≤30d · {$b60->count()} 31-60d · {$b90->count()} 61-90d (total {$total})");

        if ($total === 0) {
            $this->line('Nothing to send — all certs are >90 days from expiry.');
            return self::SUCCESS;
        }

        $recipients = User::notifiableForRoles([User::ROLE_ADMIN, User::ROLE_ACCOUNTANT]);
        if ($recipients->isEmpty()) {
            $this->warn('No active admin/accountant users with email — skipping.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('--dry-run: would email ' . $recipients->count() . ' user(s):');
            $recipients->each(fn ($u) => $this->line("  - {$u->name} <{$u->email}>"));
            return self::SUCCESS;
        }

        Notification::send($recipients, new CertificationExpiring($expired, $b30, $b60, $b90));
        $this->info('Digest sent to ' . $recipients->count() . ' user(s).');

        return self::SUCCESS;
    }
}
