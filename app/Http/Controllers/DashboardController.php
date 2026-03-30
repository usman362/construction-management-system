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

        $recentProjects = Project::where('status', 'active')
            ->with(['client', 'budgetLines', 'commitments', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $allProjects = Project::where('status', 'active')
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
