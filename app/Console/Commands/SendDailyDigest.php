<?php

namespace App\Console\Commands;

use App\Models\BillingInvoice;
use App\Models\ChangeOrder;
use App\Models\EmployeeCertification;
use App\Models\Invoice;
use App\Models\Rfi;
use App\Models\TimeClockEntry;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\DailyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Aggregates everything-that-needs-attention into a single morning email
 * per admin/PM/accountant.
 *
 * Run by scheduler every weekday morning at 7am. The same digest goes to
 * everyone (one shared snapshot of the company's pending work) — we don't
 * filter per-user assignment so an admin always sees the full picture and
 * a PM sees company-wide pending work, not just theirs.
 */
class SendDailyDigest extends Command
{
    protected $signature = 'digest:send
                            {--dry-run : Compute the digest but don\'t actually send}';

    protected $description = 'Email a one-shot morning summary of pending approvals, RFIs, COs, invoices, and clocked-in workers.';

    public function handle(): int
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        // Pre-load each bucket once — same payload sent to every recipient.
        $payload = [
            'pendingTimesheets'   => Timesheet::with(['employee:id,first_name,last_name', 'project:id,project_number'])
                ->where('status', 'submitted')->orderByDesc('id')->get(),

            'openRfis'            => Rfi::with(['project:id,project_number'])
                ->whereIn('status', ['submitted', 'in_review'])->orderByDesc('id')->get(),

            'pendingChangeOrders' => ChangeOrder::with(['project:id,project_number'])
                ->where('status', 'pending')->orderByDesc('id')->get(),

            'pendingInvoices'     => Invoice::with(['vendor:id,name'])
                ->where('status', 'pending')->orderByDesc('id')->get(),

            'expiredCerts'        => EmployeeCertification::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', $now->toDateString())->get(),

            'expiringCerts30'     => EmployeeCertification::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $now->toDateString())
                ->whereDate('expiry_date', '<', $now->copy()->addDays(30)->toDateString())
                ->get(),

            'clockedInNow'        => TimeClockEntry::where('status', 'open')->count(),

            'billedThisMonth'     => (float) BillingInvoice::query()
                ->where('invoice_date', '>=', $monthStart)
                ->sum('total_amount'),

            'collectedThisMonth'  => (float) BillingInvoice::query()
                ->where('paid_date', '>=', $monthStart)
                ->whereNotNull('paid_date')
                ->sum('total_amount'),
        ];

        $this->table(
            ['Bucket', 'Count'],
            collect($payload)->map(fn ($v, $k) => [$k, is_object($v) ? $v->count() : $v])->values()->all()
        );

        $recipients = User::notifiableForRoles([
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_MANAGER,
            User::ROLE_ACCOUNTANT,
        ]);

        if ($recipients->isEmpty()) {
            $this->warn('No notifiable recipients — skipping.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('--dry-run: would email ' . $recipients->count() . ' user(s).');
            return self::SUCCESS;
        }

        Notification::send($recipients, new DailyDigest($payload));
        $this->info('Daily digest sent to ' . $recipients->count() . ' user(s).');

        return self::SUCCESS;
    }
}
