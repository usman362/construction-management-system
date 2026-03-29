<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BudgetLineController;
use App\Http\Controllers\ChangeOrderController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\ManhourBudgetController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CraftController;
use App\Http\Controllers\CrewController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CostCodeController;
use App\Http\Controllers\SystemDeployController;
use App\Http\Middleware\VerifyDeployToken;

// ─── Guest (Auth) Routes ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Authenticated Routes ────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Projects with nested routes
    Route::resource('projects', ProjectController::class);

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        // Budget Lines
        Route::get('budget', [BudgetLineController::class, 'index'])->name('budget.index');
        Route::get('budget/create', [BudgetLineController::class, 'create'])->name('budget.create');
        Route::post('budget', [BudgetLineController::class, 'store'])->name('budget.store');
        Route::get('budget/{budgetLine}/edit', [BudgetLineController::class, 'edit'])->name('budget.edit');
        Route::put('budget/{budgetLine}', [BudgetLineController::class, 'update'])->name('budget.update');
        Route::delete('budget/{budgetLine}', [BudgetLineController::class, 'destroy'])->name('budget.destroy');

        // Change Orders
        Route::resource('change-orders', ChangeOrderController::class);
        Route::post('change-orders/{changeOrder}/approve', [ChangeOrderController::class, 'approve'])->name('change-orders.approve');
        Route::post('change-orders/{changeOrder}/items', [ChangeOrderController::class, 'addItem'])->name('change-orders.add-item');
        Route::post('change-orders/{changeOrder}/labor', [ChangeOrderController::class, 'addLabor'])->name('change-orders.add-labor');

        // Estimates
        Route::resource('estimates', EstimateController::class);
        Route::post('estimates/{estimate}/lines', [EstimateController::class, 'addLine'])->name('estimates.add-line');
        Route::put('estimates/lines/{estimateLine}', [EstimateController::class, 'updateLine'])->name('estimates.update-line');
        Route::delete('estimates/lines/{estimateLine}', [EstimateController::class, 'removeLine'])->name('estimates.remove-line');

        // Commitments
        Route::resource('commitments', CommitmentController::class);

        // Manhour Budgets
        Route::get('manhour-budgets', [ManhourBudgetController::class, 'index'])->name('manhour-budgets.index');
        Route::post('manhour-budgets', [ManhourBudgetController::class, 'store'])->name('manhour-budgets.store');
        Route::put('manhour-budgets/{manhourBudget}', [ManhourBudgetController::class, 'update'])->name('manhour-budgets.update');

        // Daily Logs
        Route::resource('daily-logs', DailyLogController::class)->except(['edit', 'update']);

        // Project Reports
        Route::get('reports/cost-report', [ReportController::class, 'costReport'])->name('reports.cost-report');
        Route::get('reports/forecast', [ReportController::class, 'forecast'])->name('reports.forecast');
        Route::get('reports/manhours', [ReportController::class, 'manhourReport'])->name('reports.manhours');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('reports/productivity', [ReportController::class, 'productivityReport'])->name('reports.productivity');
    });

    // Workforce Management
    Route::resource('employees', EmployeeController::class);
    Route::resource('crafts', CraftController::class);
    Route::resource('crews', CrewController::class);
    Route::post('crews/{crew}/members', [CrewController::class, 'addMember'])->name('crews.add-member');
    Route::delete('crews/{crew}/members/{crewMember}', [CrewController::class, 'removeMember'])->name('crews.remove-member');
    Route::resource('shifts', ShiftController::class);

    // Time & Labor
    Route::resource('timesheets', TimesheetController::class);
    Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve');
    Route::post('timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->name('timesheets.reject');
    Route::get('timesheets-bulk', [TimesheetController::class, 'bulkCreate'])->name('timesheets.bulk-create');
    Route::post('timesheets-bulk', [TimesheetController::class, 'bulkStore'])->name('timesheets.bulk-store');

    // Payroll
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/{payrollPeriod}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('payroll/{payrollPeriod}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('payroll/{payrollPeriod}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::get('payroll/{payrollPeriod}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payrollPeriod}/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::post('payroll/{payrollPeriod}/process', [PayrollController::class, 'process'])->name('payroll.process');

    // Costing
    Route::resource('cost-codes', CostCodeController::class);
    Route::resource('clients', ClientController::class);

    // Vendors & Procurement
    Route::resource('vendors', VendorController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
    Route::resource('equipment', EquipmentController::class);
    Route::post('equipment/{equipment}/assign', [EquipmentController::class, 'assign'])->name('equipment.assign');
    Route::delete('equipment/assignments/{equipmentAssignment}', [EquipmentController::class, 'unassign'])->name('equipment.unassign');
    Route::resource('materials', MaterialController::class);
    Route::post('materials/usage', [MaterialController::class, 'recordUsage'])->name('materials.record-usage');

    // Billing
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('billing/{billingInvoice}/edit', [BillingController::class, 'edit'])->name('billing.edit');
    Route::put('billing/{billingInvoice}', [BillingController::class, 'update'])->name('billing.update');
    Route::delete('billing/{billingInvoice}', [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::get('billing/{billingInvoice}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('billing/{billingInvoice}/generate', [BillingController::class, 'generate'])->name('billing.generate');
    Route::post('billing/{billingInvoice}/send', [BillingController::class, 'send'])->name('billing.send');
    Route::post('billing/{billingInvoice}/mark-paid', [BillingController::class, 'markPaid'])->name('billing.mark-paid');

    // Global Reports
    Route::get('reports/timesheets', [ReportController::class, 'timesheetReport'])->name('reports.timesheets');
});

/*
| Remote deploy (no SSH): GET with DEPLOY_TOKEN via ?token= or X-Deploy-Token header.
| Set DEPLOY_TOKEN in .env. Optional: DEPLOY_ALLOWED_IPS=comma,separated,ips
| Warning: GET is convenient but URLs may appear in logs; use a strong token and HTTPS.
*/
Route::middleware([VerifyDeployToken::class, 'throttle:10,1'])
    ->prefix('system/deploy')
    ->group(function () {
        Route::get('git-pull', [SystemDeployController::class, 'gitPull'])->name('deploy.git-pull');
        Route::get('migrate', [SystemDeployController::class, 'migrate'])->name('deploy.migrate');
        Route::get('seed', [SystemDeployController::class, 'seed'])->name('deploy.seed');
    });
