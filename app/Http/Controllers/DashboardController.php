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
        $activeProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->with(['client', 'commitments', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->get();

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
