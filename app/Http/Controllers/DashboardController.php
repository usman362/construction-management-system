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
        // Eager-load `estimates` so we can fall back to the sum of approved
        // estimate records when the project's direct `estimate` column is
        // empty. Clients often build estimates through the Estimates module
        // rather than typing a single number into the project form, so the
        // dashboard should pick up either source.
        $activeProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->with(['client', 'commitments', 'invoices', 'estimates'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Compute a single `dashboard_estimate` value per project:
        //   1. Direct `estimate` column if > 0
        //   2. Otherwise, sum of approved Estimate records
        //   3. Otherwise, `contract_value` (last-resort fallback)
        foreach ($activeProjects as $p) {
            $direct = (float) ($p->estimate ?? 0);
            if ($direct > 0) {
                $p->dashboard_estimate = $direct;
                continue;
            }
            $approvedSum = (float) $p->estimates->where('status', 'approved')->sum('total_amount');
            if ($approvedSum > 0) {
                $p->dashboard_estimate = $approvedSum;
                continue;
            }
            $anySum = (float) $p->estimates->sum('total_amount');
            $p->dashboard_estimate = $anySum > 0 ? $anySum : (float) ($p->contract_value ?? 0);
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
