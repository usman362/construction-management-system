<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\ChangeOrder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeProjectsCount = Project::where('status', 'active')->count();
        $totalEmployees = Employee::where('status', 'active')->count();
        $pendingTimesheets = Timesheet::where('status', 'pending')->count();
        $openChangeOrders = ChangeOrder::where('status', 'pending')->count();

        // Show all non-closed projects, newest first (was: active + limit 5, which
        // meant newly-created projects weren't visible once 5 older ones existed).
        $recentProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->with(['client', 'budgetLines', 'commitments', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->get();

        $allProjects = Project::whereNotIn('status', ['closed', 'completed'])
            ->orderBy('name')
            ->get(['id', 'name', 'project_number']);

        return view('dashboard', [
            'stats' => [
                'activeProjects' => $activeProjectsCount,
                'totalEmployees' => $totalEmployees,
                'pendingTimesheets' => $pendingTimesheets,
                'openChangeOrders' => $openChangeOrders,
            ],
            'recentProjects' => $recentProjects,
            'allProjects' => $allProjects,
        ]);
    }
}
