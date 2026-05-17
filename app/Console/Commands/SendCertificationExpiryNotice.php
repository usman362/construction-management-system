<?php

namespace App\Console\Commands;

use App\Models\EmployeeCertification;
use App\Models\User;
use App\Notifications\CertificationExpiring;
use App\Notifications\CertificationExpiringEmployee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Cert expiry watchdog (Brenda — Phase 1, 2026-05-12).
 *
 * Two emails per run:
 *
 *  1) DIGEST to staff (Admin / Accountant / Site Manager / HR) — one
 *     email per recipient bundling everything expiring in the next
 *     90 days plus anything already expired. Same shape as the
 *     original 2026-04-30 implementation, just with two more roles.
 *
 *  2) PER-EMPLOYEE notice — when a cert crosses a milestone (60d /
 *     30d / 7d / expired) for the *first* time, the employee gets a
 *     focused email about their own cert. We track last-sent per
 *     milestone on the cert row (`notice_60_sent_at` etc.) so the
 *     scheduler can run nightly without spamming.
 *
 * Renewing a cert (updating its expiry_date) wipes the notice flags
 * automatically via the EmployeeCertification::booted() observer —
 * so a renewed cert gets a fresh round of milestones next cycle.
 */
class SendCertificationExpiryNotice extends Command
{
    protected $signature = 'certs:notify-expiring
                            {--dry-run : Preview the digest + per-employee emails without sending}';

    protected $description = 'Email a 90-day cert-expiry watch digest + per-employee milestone notices.';

    public function handle(): int
    {
        $now  = now()->startOfDay();
        $in30 = $now->copy()->addDays(30);
        $in60 = $now->copy()->addDays(60);
        $in90 = $now->copy()->addDays(90);

        $all = EmployeeCertification::whereNotNull('expiry_date')
            ->with('employee:id,first_name,last_name,employee_number,email')
            ->orderBy('expiry_date')
            ->get();

        // Digest buckets — sent to staff
        $expired = $all->filter(fn ($c) => $c->expiry_date->lt($now))->values();
        $b30     = $all->filter(fn ($c) => $c->expiry_date->gte($now)  && $c->expiry_date->lt($in30))->values();
        $b60     = $all->filter(fn ($c) => $c->expiry_date->gte($in30) && $c->expiry_date->lt($in60))->values();
        $b90     = $all->filter(fn ($c) => $c->expiry_date->gte($in60) && $c->expiry_date->lt($in90))->values();

        $total = $expired->count() + $b30->count() + $b60->count() + $b90->count();
        $this->info("Cert expiry digest: {$expired->count()} expired · {$b30->count()} ≤30d · {$b60->count()} 31-60d · {$b90->count()} 61-90d (total {$total})");

        // ─── Per-employee milestone emails ──────────────────────────────
        // For each cert, figure out which milestone (60/30/7/expired) it
        // falls into and whether that milestone has already been emailed.
        // Mark the timestamp the moment we send so re-runs are no-ops.
        $perEmployeeQueued = 0;
        $perEmployeeSkipped = 0;
        foreach ($all as $cert) {
            // Days from today TO expiry. Positive = future. Negative = past.
            $daysOut = (int) $now->diffInDays($cert->expiry_date->startOfDay(), false);

            [$milestone, $alreadyField] = match (true) {
                $daysOut < 0   => ['expired', 'notice_expired_sent_at'],
                $daysOut <= 7  => ['7',       'notice_7_sent_at'],
                $daysOut <= 30 => ['30',      'notice_30_sent_at'],
                $daysOut <= 60 => ['60',      'notice_60_sent_at'],
                default        => [null,      null],
            };

            if ($milestone === null) continue;                  // not in danger zone
            if ($cert->{$alreadyField} !== null) {
                $perEmployeeSkipped++;
                continue;                                       // already emailed for this milestone
            }

            $employeeEmail = trim((string) ($cert->employee?->email ?? ''));
            if ($employeeEmail === '') {
                // No employee email on file. Skip silently — admin digest
                // still surfaces this cert.
                continue;
            }

            $perEmployeeQueued++;
            if (!$this->option('dry-run')) {
                Notification::route('mail', $employeeEmail)
                    ->notify(new CertificationExpiringEmployee($cert, $milestone, $daysOut));
                // Mark the milestone as notified — saved direct so the
                // EmployeeCertification::booted() updating-observer's
                // expiry_date reset doesn't fire on this no-op change.
                $cert->forceFill([$alreadyField => now()])->saveQuietly();
            }
        }

        $this->info("Per-employee milestone notices: queued {$perEmployeeQueued} · already-notified skipped {$perEmployeeSkipped}");

        // ─── Digest to staff ────────────────────────────────────────────
        if ($total === 0) {
            $this->line('No certs in the 90-day window — skipping staff digest.');
            return self::SUCCESS;
        }

        // 2026-05-12 (Brenda): expand digest recipients to include the
        // newer roles (Site Manager + HR) that have employees access.
        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_ACCOUNTANT,
            User::ROLE_SITE_MANAGER,
            User::ROLE_HR,
        ]);

        if ($recipients->isEmpty()) {
            $this->warn('No active staff users with email — skipping digest.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('--dry-run: would email digest to ' . $recipients->count() . ' user(s):');
            $recipients->each(fn ($u) => $this->line("  - {$u->name} <{$u->email}>"));
            return self::SUCCESS;
        }

        Notification::send($recipients, new CertificationExpiring($expired, $b30, $b60, $b90));
        $this->info('Digest sent to ' . $recipients->count() . ' user(s).');

        return self::SUCCESS;
    }
}
