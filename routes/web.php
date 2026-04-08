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
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProjectBillableRateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

// ─── Guest (Auth) Routes ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    // Public self-registration is disabled — admin must create users via Users module.
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Authenticated Routes ────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard — everyone
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile — every logged-in user
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Settings — Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('settings', [ProfileController::class, 'updateSettings'])->name('settings.update');
    });

    // ─── User Management — Admin only ────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // ─── Projects — everyone can view, PM+ can manage ────────────
    Route::resource('projects', ProjectController::class);

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        // Budget Lines — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::get('budget', [BudgetLineController::class, 'index'])->name('budget.index');
            Route::get('budget/create', [BudgetLineController::class, 'create'])->name('budget.create');
            Route::post('budget', [BudgetLineController::class, 'store'])->name('budget.store');
            Route::get('budget/{budgetLine}/edit', [BudgetLineController::class, 'edit'])->name('budget.edit');
            Route::put('budget/{budgetLine}', [BudgetLineController::class, 'update'])->name('budget.update');
            Route::delete('budget/{budgetLine}', [BudgetLineController::class, 'destroy'])->name('budget.destroy');
        });

        // Change Orders — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::resource('change-orders', ChangeOrderController::class);
            Route::post('change-orders/{changeOrder}/approve', [ChangeOrderController::class, 'approve'])->name('change-orders.approve');
            Route::get('change-orders/{changeOrder}/pdf', [ChangeOrderController::class, 'downloadPdf'])->name('change-orders.pdf');
            Route::post('change-orders/{changeOrder}/items', [ChangeOrderController::class, 'addItem'])->name('change-orders.add-item');
            Route::post('change-orders/{changeOrder}/labor', [ChangeOrderController::class, 'addLabor'])->name('change-orders.add-labor');
        });

        // Estimates — Admin, PM
        Route::middleware('role:admin,project_manager')->group(function () {
            Route::resource('estimates', EstimateController::class);
            Route::post('estimates/{estimate}/lines', [EstimateController::class, 'addLine'])->name('estimates.add-line');
            Route::put('estimates/lines/{estimateLine}', [EstimateController::class, 'updateLine'])->name('estimates.update-line');
            Route::delete('estimates/lines/{estimateLine}', [EstimateController::class, 'removeLine'])->name('estimates.remove-line');
        });

        // Commitments — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::resource('commitments', CommitmentController::class);
        });

        // Manhour Budgets — Admin, PM
        Route::middleware('role:admin,project_manager')->group(function () {
            Route::get('manhour-budgets', [ManhourBudgetController::class, 'index'])->name('manhour-budgets.index');
            Route::post('manhour-budgets', [ManhourBudgetController::class, 'store'])->name('manhour-budgets.store');
            Route::put('manhour-budgets/{manhourBudget}', [ManhourBudgetController::class, 'update'])->name('manhour-budgets.update');
        });

        // Project Billable Rates — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::get('billable-rates', [ProjectBillableRateController::class, 'index'])->name('billable-rates.index');
            Route::post('billable-rates', [ProjectBillableRateController::class, 'store'])->name('billable-rates.store');
            Route::get('billable-rates/{projectBillableRate}/edit', [ProjectBillableRateController::class, 'edit'])->name('billable-rates.edit');
            Route::put('billable-rates/{projectBillableRate}', [ProjectBillableRateController::class, 'update'])->name('billable-rates.update');
            Route::delete('billable-rates/{projectBillableRate}', [ProjectBillableRateController::class, 'destroy'])->name('billable-rates.destroy');
        });

        // Daily Logs — Admin, PM, Field
        Route::middleware('role:admin,project_manager,field')->group(function () {
            Route::resource('daily-logs', DailyLogController::class);
        });

        // Project Reports — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::get('reports/cost-report', [ReportController::class, 'costReport'])->name('reports.cost-report');
            Route::get('reports/forecast', [ReportController::class, 'forecast'])->name('reports.forecast');
            Route::get('reports/manhours', [ReportController::class, 'manhourReport'])->name('reports.manhours');
            Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('reports/productivity', [ReportController::class, 'productivityReport'])->name('reports.productivity');

            // PDF Report Downloads
            Route::get('reports/cost-report/pdf', [ReportController::class, 'costReportPdf'])->name('reports.cost-report.pdf');
            Route::get('reports/forecast/pdf', [ReportController::class, 'forecastPdf'])->name('reports.forecast.pdf');
            Route::get('reports/manhours/pdf', [ReportController::class, 'manhourReportPdf'])->name('reports.manhours.pdf');
            Route::get('reports/profit-loss/pdf', [ReportController::class, 'profitLossPdf'])->name('reports.profit-loss.pdf');
            Route::get('reports/productivity/pdf', [ReportController::class, 'productivityReportPdf'])->name('reports.productivity.pdf');
        });
    });

    // ─── Workforce Management ────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::resource('employees', EmployeeController::class);
    });

    Route::middleware('role:admin,project_manager')->group(function () {
        Route::resource('crafts', CraftController::class);
        Route::resource('shifts', ShiftController::class);
    });

    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::resource('crews', CrewController::class);
        Route::post('crews/{crew}/members', [CrewController::class, 'addMember'])->name('crews.add-member');
        Route::delete('crews/{crew}/members/{crewMember}', [CrewController::class, 'removeMember'])->name('crews.remove-member');
    });

    // ─── Time & Labor ────────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant,field')->group(function () {
        Route::resource('timesheets', TimesheetController::class);
        Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve');
        Route::post('timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->name('timesheets.reject');
        Route::get('timesheets-bulk', [TimesheetController::class, 'bulkCreate'])->name('timesheets.bulk-create');
        Route::post('timesheets-bulk', [TimesheetController::class, 'bulkStore'])->name('timesheets.bulk-store');
    });

    // Payroll — Admin, Accountant
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{payrollPeriod}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::get('payroll/{payrollPeriod}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::put('payroll/{payrollPeriod}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::delete('payroll/{payrollPeriod}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::post('payroll/{payrollPeriod}/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/{payrollPeriod}/process', [PayrollController::class, 'process'])->name('payroll.process');
    });

    // ─── Costing ─────────────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::resource('cost-codes', CostCodeController::class);
        Route::resource('clients', ClientController::class);
    });

    // ─── Procurement ─────────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/issue', [PurchaseOrderController::class, 'issue'])->name('purchase-orders.issue');
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'addItem'])->name('purchase-orders.add-item');
        Route::delete('purchase-orders/{purchaseOrder}/items/{purchaseOrderItem}', [PurchaseOrderController::class, 'removeItem'])->name('purchase-orders.remove-item');
        Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchase-orders.pdf');
        Route::post('purchase-orders/{purchaseOrder}/commit', [PurchaseOrderController::class, 'commitToProject'])->name('purchase-orders.commit');
    });

    // Vendors — Admin, PM, Accountant
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::resource('vendors', VendorController::class);
    });

    // Invoices — Admin, Accountant
    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
    });

    // Equipment — Admin, PM, Field
    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::resource('equipment', EquipmentController::class);
        Route::post('equipment/{equipment}/assign', [EquipmentController::class, 'assign'])->name('equipment.assign');
        Route::delete('equipment/assignments/{equipmentAssignment}', [EquipmentController::class, 'unassign'])->name('equipment.unassign');
    });

    // Materials — Admin, PM, Field
    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::resource('materials', MaterialController::class);
        Route::post('materials/usage', [MaterialController::class, 'recordUsage'])->name('materials.record-usage');
    });

    // ─── Billing — Admin, Accountant ─────────────────────────────
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('billing', [BillingController::class, 'store'])->name('billing.store');
        Route::get('billing/{billingInvoice}/edit', [BillingController::class, 'edit'])->name('billing.edit');
        Route::put('billing/{billingInvoice}', [BillingController::class, 'update'])->name('billing.update');
        Route::delete('billing/{billingInvoice}', [BillingController::class, 'destroy'])->name('billing.destroy');
        Route::get('billing/{billingInvoice}', [BillingController::class, 'show'])->name('billing.show');
        Route::post('billing/{billingInvoice}/generate', [BillingController::class, 'generate'])->name('billing.generate');
        Route::post('billing/{billingInvoice}/send', [BillingController::class, 'send'])->name('billing.send');
        Route::post('billing/{billingInvoice}/mark-paid', [BillingController::class, 'markPaid'])->name('billing.mark-paid');
        Route::get('billing/{billingInvoice}/pdf', [BillingController::class, 'downloadPdf'])->name('billing.pdf');
    });

    // ─── Global Reports ──────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::get('reports/timesheets', [ReportController::class, 'timesheetReport'])->name('reports.timesheets');
        Route::get('reports/timesheets/pdf', [ReportController::class, 'timesheetReportPdf'])->name('reports.timesheets.pdf');
    });
});
