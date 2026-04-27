<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\ChangeOrder;
use App\Models\CostCode;
use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Rfi;
use App\Models\TimeClockEntry;
use App\Models\Timesheet;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // ── Active projects (non-closed/completed) ──
        // Eager-load `estimates` AND `budgetLines` so we can fall back to those
        // when the project's direct columns are empty. Clients enter project
        // values in three different places depending on workflow:
        //   - The `estimate` / `current_budget` columns on the project form
        //   - Line items in the Estimates module (estimates → total_amount)
        //   - Line items in the Budget tab (budget_lines → budget_amount /
        //     revised_amount)
        // The dashboard should surface a value if ANY of these sources have one.
        $activeProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->with(['client', 'commitments', 'invoices', 'estimates', 'budgetLines'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Compute display values for the dashboard table.
        //
        // dashboard_estimate fallback order:
        //   1. `projects.estimate` column (manually typed)
        //   2. Sum of approved Estimate records
        //   3. Sum of any Estimate records (drafts etc.)
        //   4. Sum of budget_lines.revised_amount (when client uses Budget tab
        //      as their estimate source)
        //   5. `contract_value` (last-resort fallback)
        //
        // dashboard_budget fallback order:
        //   1. `projects.current_budget` column (manually typed)
        //   2. Sum of budget_lines.revised_amount (or budget_amount if revised
        //      not set) — this is how most clients actually enter budgets
        //
        // Without the budget_lines fallback, projects like BM-5400 (which has
        // $462k in budget lines but no value typed into the project form) show
        // $0 on the dashboard despite having real data.
        foreach ($activeProjects as $p) {
            // Reusable budget-lines total (revised_amount preferred, falls back
            // to budget_amount per line).
            $budgetLineTotal = (float) $p->budgetLines->sum(function ($line) {
                return (float) ($line->revised_amount ?? $line->budget_amount ?? 0);
            });

            // ── Estimate ─────────────────────────────────────────────
            $direct = (float) ($p->estimate ?? 0);
            if ($direct > 0) {
                $p->dashboard_estimate = $direct;
            } else {
                $approvedSum = (float) $p->estimates->where('status', 'approved')->sum('total_amount');
                if ($approvedSum > 0) {
                    $p->dashboard_estimate = $approvedSum;
                } else {
                    $anySum = (float) $p->estimates->sum('total_amount');
                    if ($anySum > 0) {
                        $p->dashboard_estimate = $anySum;
                    } elseif ($budgetLineTotal > 0) {
                        $p->dashboard_estimate = $budgetLineTotal;
                    } else {
                        $p->dashboard_estimate = (float) ($p->contract_value ?? 0);
                    }
                }
            }

            // ── Budget ───────────────────────────────────────────────
            $directBudget = (float) ($p->current_budget ?? 0);
            $p->dashboard_budget = $directBudget > 0 ? $directBudget : $budgetLineTotal;
        }

        $activeProjectsCount = $activeProjects->count();

        // Over-budget = committed amount exceeds current_budget (and budget > 0).
        // Near-budget = committed is at 90–100% of budget (early warning).
        $overBudgetCount = 0;
        $nearBudgetCount = 0;
        foreach ($activeProjects as $p) {
            $pct = $p->committed_percentage;
            if ($pct > 100) {
                $overBudgetCount++;
            } elseif ($pct >= 90) {
                $nearBudgetCount++;
            }
        }

        $totalEmployees    = Employee::where('status', 'active')->count();
        // 'submitted' is the timesheet workflow's "needs approval" state
        // (status enum: draft, submitted, approved, rejected). The label on
        // the dashboard tile still reads "Pending T-Sheets" because that's the
        // user-facing wording.
        $pendingTimesheets = Timesheet::where('status', 'submitted')->count();
        $openChangeOrders  = ChangeOrder::where('status', 'pending')->count();

        // ─── Phase 7C: Live operations widgets ─────────────────────
        // These four metrics power the new "What's happening right now" tile
        // strip on the dashboard. Everything is a single COUNT or SUM so the
        // dashboard load stays under one DB roundtrip per metric.
        $clockedInNow      = TimeClockEntry::where('status', 'open')->count();
        $clockedInList     = TimeClockEntry::with(['employee:id,first_name,last_name', 'project:id,project_number'])
            ->where('status', 'open')
            ->orderBy('clock_in_at')
            ->get();
        $pendingInvoices   = Invoice::where('status', 'pending')->count();
        $openRfisCount     = Rfi::whereIn('status', ['submitted', 'in_review'])->count();

        // Combined "Pending Approvals" tile — one number the admin can scan.
        $pendingApprovalsTotal = $pendingTimesheets + $openChangeOrders + $pendingInvoices;

        // Cash flow snapshot — billed vs. collected this calendar month.
        // Billed = sum of billing_invoices issued this month.
        // Collected = sum of billing_invoices marked paid this month.
        $monthStart = now()->startOfMonth();
        $billedThisMonth    = (float) BillingInvoice::where('invoice_date', '>=', $monthStart)->sum('total_amount');
        $collectedThisMonth = (float) BillingInvoice::where('paid_date', '>=', $monthStart)
            ->whereNotNull('paid_date')
            ->sum('total_amount');

        // Overdue RFIs — submitted/in_review with a `needed_by` date in the past.
        $overdueRfisCount = Rfi::whereIn('status', ['submitted', 'in_review'])
            ->whereNotNull('needed_by')
            ->whereDate('needed_by', '<', now()->toDateString())
            ->count();

        // ─── Cash Flow Forecast (12-week rolling) ─────────────────
        // Bucket expected receivables (sent + unpaid billing invoices, by due_date)
        // and expected payables (pending vendor invoices + draft/issued POs delivery
        // dates) into 12 weekly buckets starting THIS week. Net per week tells the
        // owner whether next month is cash-positive or whether they need a draw.
        $cashFlowWeeks = $this->buildCashFlowForecast();

        // ── Certification expiry tracking ──
        // Bucket upcoming/past expirations so the PM can act on them each morning.
        $now      = now()->startOfDay();
        $in30     = $now->copy()->addDays(30);
        $in60     = $now->copy()->addDays(60);
        $in90     = $now->copy()->addDays(90);

        $allCertsWithExpiry = EmployeeCertification::whereNotNull('expiry_date')
            ->with('employee:id,first_name,last_name,employee_number')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $expiredCerts      = $allCertsWithExpiry->filter(fn ($c) => $c->expiry_date->lt($now))->values();
        $expiring30Certs   = $allCertsWithExpiry->filter(fn ($c) => $c->expiry_date->gte($now) && $c->expiry_date->lt($in30))->values();
        $expiring60Certs   = $allCertsWithExpiry->filter(fn ($c) => $c->expiry_date->gte($in30) && $c->expiry_date->lt($in60))->values();
        $expiring90Certs   = $allCertsWithExpiry->filter(fn ($c) => $c->expiry_date->gte($in60) && $c->expiry_date->lt($in90))->values();

        // Watch list — everything expired + expiring within 90 days, most urgent first.
        $certWatchList = $expiredCerts
            ->concat($expiring30Certs)
            ->concat($expiring60Certs)
            ->concat($expiring90Certs)
            ->take(20);

        $allProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->orderBy('name')
            ->get(['id', 'name', 'project_number']);

        return view('dashboard', [
            'stats' => [
                'activeProjects'        => $activeProjectsCount,
                'totalEmployees'        => $totalEmployees,
                'pendingTimesheets'     => $pendingTimesheets,
                'openChangeOrders'      => $openChangeOrders,
                'overBudget'            => $overBudgetCount,
                'nearBudget'            => $nearBudgetCount,
                'expiredCerts'          => $expiredCerts->count(),
                'expiring30Certs'       => $expiring30Certs->count(),
                'expiring60Certs'       => $expiring60Certs->count(),
                'expiring90Certs'       => $expiring90Certs->count(),
                // Phase 7C live widgets
                'clockedInNow'          => $clockedInNow,
                'pendingInvoices'       => $pendingInvoices,
                'openRfis'              => $openRfisCount,
                'overdueRfis'           => $overdueRfisCount,
                'pendingApprovalsTotal' => $pendingApprovalsTotal,
                'billedThisMonth'       => $billedThisMonth,
                'collectedThisMonth'    => $collectedThisMonth,
            ],
            'recentProjects' => $activeProjects,
            'allProjects'    => $allProjects,
            'certWatchList'  => $certWatchList,
            'clockedInList'  => $clockedInList,
            'cashFlowWeeks'  => $cashFlowWeeks,
            // Quick-action modal data — Brenda 04.28.2026 wanted the dashboard
            // "New X" buttons to open modals in-place instead of redirecting.
            // Each partial modal needs the dropdown data inline.
            'allEmployees'   => Employee::where('status', 'active')
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number']),
            'allClients'     => \App\Models\Client::orderBy('name')->get(['id', 'name']),
            'allCostCodes'   => CostCode::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Build a 12-week rolling cash flow forecast.
     *
     * - Receivables: billing invoices that are sent/approved but not paid yet,
     *   bucketed by due_date.
     * - Payables: vendor invoices in pending/approved status (not paid),
     *   bucketed by due_date.
     *
     * Anything past-due rolls into "Week 1" (this week) so the owner sees the
     * urgency.
     */
    private function buildCashFlowForecast(): array
    {
        $weeks = [];
        $weekStart = now()->startOfWeek();
        for ($i = 0; $i < 12; $i++) {
            $weeks[] = (object) [
                'index' => $i + 1,
                'start' => $weekStart->copy()->addWeeks($i),
                'end'   => $weekStart->copy()->addWeeks($i + 1)->subDay(),
                'inflow' => 0.0,
                'outflow' => 0.0,
            ];
        }
        $horizonEnd = $weeks[11]->end;

        // Receivables — billing invoices not yet paid
        $receivables = BillingInvoice::query()
            ->whereNotIn('status', ['draft', 'voided', 'paid'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $horizonEnd)
            ->get(['id', 'due_date', 'total_amount']);

        foreach ($receivables as $r) {
            $idx = $this->bucketIndex($r->due_date, $weeks);
            if ($idx !== null) $weeks[$idx]->inflow += (float) $r->total_amount;
        }

        // Payables — vendor invoices not yet paid
        $payables = \App\Models\Invoice::query()
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $horizonEnd)
            ->get(['id', 'due_date', 'amount']);

        foreach ($payables as $p) {
            $idx = $this->bucketIndex($p->due_date, $weeks);
            if ($idx !== null) $weeks[$idx]->outflow += (float) $p->amount;
        }

        // Compute net + running balance
        $running = 0.0;
        foreach ($weeks as $w) {
            $w->net = $w->inflow - $w->outflow;
            $running += $w->net;
            $w->cumulative = $running;
        }

        return $weeks;
    }

    /**
     * Map an arbitrary date to the matching 12-week bucket index.
     * Past-due dates collapse into bucket 0 (this week).
     */
    private function bucketIndex($date, array $weeks): ?int
    {
        $d = \Carbon\Carbon::parse($date);
        if ($d->lt($weeks[0]->start)) return 0;   // overdue → this week
        foreach ($weeks as $i => $w) {
            if ($d->between($w->start, $w->end)) return $i;
        }
        return null;
    }
}
