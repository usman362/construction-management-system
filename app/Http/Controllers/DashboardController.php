<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\ChangeOrder;
use App\Models\EmployeeCertification;
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
        $pendingTimesheets = Timesheet::where('status', 'pending')->count();
        $openChangeOrders  = ChangeOrder::where('status', 'pending')->count();

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
                'activeProjects'    => $activeProjectsCount,
                'totalEmployees'    => $totalEmployees,
                'pendingTimesheets' => $pendingTimesheets,
                'openChangeOrders'  => $openChangeOrders,
                'overBudget'        => $overBudgetCount,
                'nearBudget'        => $nearBudgetCount,
                'expiredCerts'      => $expiredCerts->count(),
                'expiring30Certs'   => $expiring30Certs->count(),
                'expiring60Certs'   => $expiring60Certs->count(),
                'expiring90Certs'   => $expiring90Certs->count(),
            ],
            'recentProjects' => $activeProjects,
            'allProjects'    => $allProjects,
            'certWatchList'  => $certWatchList,
        ]);
    }
}
