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
use App\Http\Controllers\RotationGroupController;
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
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeCertificationController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\SystemMaintenanceController;

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

    // ─── Global Search — Phase 7D ────────────────────────────────
    // Single endpoint that searches across projects, employees, POs, invoices,
    // RFIs, change orders, vendors, clients, and cost codes. Used by the
    // top-nav search dropdown. Available to anyone authenticated; results are
    // filtered to data the user already has access to via existing role
    // middleware on each detail page.
    Route::get('search', [\App\Http\Controllers\SearchController::class, 'search'])
        ->name('search');

    // ─── Excel Exports — Phase 7E ────────────────────────────────
    // One endpoint per list page; each honors the same filters as the source
    // index (status, project_id, date_from/to, etc.) via query string. Adding
    // a new export = controller method + a route here + a button on the list
    // view. Role middleware is applied separately further down so each export
    // matches the access rules of its list page.
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('projects',        [\App\Http\Controllers\ExportController::class, 'projects'])->name('projects');
        Route::get('employees',       [\App\Http\Controllers\ExportController::class, 'employees'])->name('employees');
        Route::get('vendors',         [\App\Http\Controllers\ExportController::class, 'vendors'])->name('vendors');
        Route::get('timesheets',      [\App\Http\Controllers\ExportController::class, 'timesheets'])->name('timesheets');
        Route::get('invoices',        [\App\Http\Controllers\ExportController::class, 'invoices'])->name('invoices');
        Route::get('purchase-orders', [\App\Http\Controllers\ExportController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('change-orders',   [\App\Http\Controllers\ExportController::class, 'changeOrders'])->name('change-orders');
        Route::get('rfis',            [\App\Http\Controllers\ExportController::class, 'rfis'])->name('rfis');
    });

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

        // Database Backups — Admin only
        Route::get('admin/backups', [BackupController::class, 'index'])->name('admin.backups');
        Route::post('admin/backup', [BackupController::class, 'create'])->name('admin.backup.create');
        Route::get('admin/backups/{filename}/download', [BackupController::class, 'download'])->name('admin.backup.download');
        Route::delete('admin/backups/{filename}', [BackupController::class, 'destroy'])->name('admin.backup.destroy');

        // System Maintenance — Admin only (cache clear, storage link)
        Route::get('admin/system/status', [SystemMaintenanceController::class, 'status'])->name('admin.system.status');
        Route::post('admin/system/clear-cache', [SystemMaintenanceController::class, 'clearCache'])->name('admin.system.clear-cache');
        Route::post('admin/system/storage-link', [SystemMaintenanceController::class, 'storageLink'])->name('admin.system.storage-link');

        // Audit Log — Admin only (append-only history of changes to Timesheets, COs, Invoices)
        Route::get('admin/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    });

    // ─── Documents (polymorphic — any authenticated user can view/download) ──
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // ─── Employee Certifications ─────────────────────────────────
    // 2026-05-01 (Brenda): "We will need a certification training matrix"
    // — single-page pivot view across every employee × every cert type.
    Route::get('certifications/matrix', [EmployeeCertificationController::class, 'matrix'])->name('certifications.matrix');
    Route::post('employees/{employee}/certifications', [EmployeeCertificationController::class, 'store'])->name('employees.certifications.store');
    Route::put('employees/{employee}/certifications/{certification}', [EmployeeCertificationController::class, 'update'])->name('employees.certifications.update');
    Route::delete('employees/{employee}/certifications/{certification}', [EmployeeCertificationController::class, 'destroy'])->name('employees.certifications.destroy');

    // Per-project pay rates for an employee
    Route::post('employees/{employee}/project-rates', [\App\Http\Controllers\EmployeeProjectRateController::class, 'store'])->name('employees.project-rates.store');
    Route::put('employees/{employee}/project-rates/{projectRate}', [\App\Http\Controllers\EmployeeProjectRateController::class, 'update'])->name('employees.project-rates.update');
    Route::delete('employees/{employee}/project-rates/{projectRate}', [\App\Http\Controllers\EmployeeProjectRateController::class, 'destroy'])->name('employees.project-rates.destroy');
    Route::get('certifications/{certification}/download', [EmployeeCertificationController::class, 'download'])->name('certifications.download');

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
            // Phase 7F — e-signature capture
            Route::post('change-orders/{changeOrder}/sign', [ChangeOrderController::class, 'sign'])->name('change-orders.sign');
            // Brenda 04.25.2026 — "smaller estimate module" tied to a CO.
            Route::post('change-orders/{changeOrder}/build-estimate', [ChangeOrderController::class, 'buildEstimate'])->name('change-orders.build-estimate');
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
            // Estimating Phase 1 — section CRUD
            Route::post('estimates/{estimate}/sections', [EstimateController::class, 'storeSection'])->name('estimates.sections.store');
            Route::put('estimates/{estimate}/sections/{section}', [EstimateController::class, 'updateSection'])->name('estimates.sections.update');
            Route::delete('estimates/{estimate}/sections/{section}', [EstimateController::class, 'destroySection'])->name('estimates.sections.destroy');
            // Estimating Phase 2 + 3 — workflow + PDF
            Route::post('estimates/{estimate}/convert', [EstimateController::class, 'convertToProject'])->name('estimates.convert');
            Route::post('estimates/{estimate}/mark-sent', [EstimateController::class, 'markSent'])->name('estimates.mark-sent');
            Route::post('estimates/{estimate}/response', [EstimateController::class, 'recordResponse'])->name('estimates.response');
            Route::get('estimates/{estimate}/pdf', [EstimateController::class, 'downloadPdf'])->name('estimates.pdf');
            Route::get('estimates/{estimate}/lines/import/template', [ImportController::class, 'estimateLineTemplate'])->name('estimates.lines.import.template');
            Route::post('estimates/{estimate}/lines/import', [ImportController::class, 'estimateLineImport'])->name('estimates.lines.import');
        });

        // Commitments — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::resource('commitments', CommitmentController::class);
        });

        // Lien Waivers (project-scoped) — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::get('lien-waivers', [\App\Http\Controllers\LienWaiverController::class, 'projectIndex'])->name('lien-waivers.index');
            Route::post('lien-waivers', [\App\Http\Controllers\LienWaiverController::class, 'store'])->name('lien-waivers.store');
            Route::get('lien-waivers/{lienWaiver}', [\App\Http\Controllers\LienWaiverController::class, 'show'])->name('lien-waivers.show');
            Route::put('lien-waivers/{lienWaiver}', [\App\Http\Controllers\LienWaiverController::class, 'update'])->name('lien-waivers.update');
            Route::delete('lien-waivers/{lienWaiver}', [\App\Http\Controllers\LienWaiverController::class, 'destroy'])->name('lien-waivers.destroy');
            // Phase 7F — e-signature capture
            Route::post('lien-waivers/{lienWaiver}/sign', [\App\Http\Controllers\LienWaiverController::class, 'sign'])->name('lien-waivers.sign');
        });

        // RFIs (project-scoped) — Admin, PM, Field (field users can submit + view)
        Route::middleware('role:admin,project_manager,field_user')->group(function () {
            Route::get('rfis', [\App\Http\Controllers\RfiController::class, 'projectIndex'])->name('rfis.index');
            Route::post('rfis', [\App\Http\Controllers\RfiController::class, 'store'])->name('rfis.store');
            Route::get('rfis/{rfi}', [\App\Http\Controllers\RfiController::class, 'show'])->name('rfis.show');
            Route::put('rfis/{rfi}', [\App\Http\Controllers\RfiController::class, 'update'])->name('rfis.update');
            Route::delete('rfis/{rfi}', [\App\Http\Controllers\RfiController::class, 'destroy'])->name('rfis.destroy');
            Route::post('rfis/{rfi}/respond', [\App\Http\Controllers\RfiController::class, 'respond'])->name('rfis.respond');
            Route::post('rfis/{rfi}/close', [\App\Http\Controllers\RfiController::class, 'close'])->name('rfis.close');
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
            Route::get('billable-rates/import/template', [ImportController::class, 'billableRateTemplate'])->name('billable-rates.import.template');
            Route::post('billable-rates/import', [ImportController::class, 'billableRateImport'])->name('billable-rates.import');
            Route::get('billable-rates/{projectBillableRate}/edit', [ProjectBillableRateController::class, 'edit'])->name('billable-rates.edit');
            Route::put('billable-rates/{projectBillableRate}', [ProjectBillableRateController::class, 'update'])->name('billable-rates.update');
            Route::delete('billable-rates/{projectBillableRate}', [ProjectBillableRateController::class, 'destroy'])->name('billable-rates.destroy');
        });

        // Daily Logs — Admin, PM, Field
        Route::middleware('role:admin,project_manager,field')->group(function () {
            // Mobile-first daily log creator — registered BEFORE the resource
            // so /daily-logs/mobile-create isn't matched as {dailyLog}.
            Route::get('daily-logs/mobile-create', [DailyLogController::class, 'mobileCreate'])->name('daily-logs.mobile-create');
            Route::resource('daily-logs', DailyLogController::class);
        });

        // Field & Mobile Pack — Photo Gallery (aggregates docs across the project)
        Route::middleware('role:admin,project_manager,field,accountant')->group(function () {
            Route::get('photos', [\App\Http\Controllers\ProjectPhotoGalleryController::class, 'index'])->name('photos.index');
        });

        // Project Reports — Admin, PM, Accountant
        Route::middleware('role:admin,project_manager,accountant')->group(function () {
            Route::get('reports/cost-report', [ReportController::class, 'costReport'])->name('reports.cost-report');
            Route::get('reports/forecast', [ReportController::class, 'forecast'])->name('reports.forecast');
            Route::get('reports/manhours', [ReportController::class, 'manhourReport'])->name('reports.manhours');
            Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('reports/productivity', [ReportController::class, 'productivityReport'])->name('reports.productivity');

            // Excel/CSV Report Downloads
            Route::get('reports/cost-report/excel', [ReportController::class, 'costReportExcel'])->name('reports.cost-report.excel');
            Route::get('reports/forecast/excel', [ReportController::class, 'forecastExcel'])->name('reports.forecast.excel');

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
        // Import routes must be registered BEFORE resource routes so they don't
        // get captured by the {employee} parameter.
        Route::get('employees/import/template', [ImportController::class, 'employeeTemplate'])->name('employees.import.template');
        Route::post('employees/import', [ImportController::class, 'employeeImport'])->name('employees.import');
        Route::resource('employees', EmployeeController::class);
    });

    Route::middleware('role:admin,project_manager')->group(function () {
        // Import routes registered BEFORE resource routes.
        Route::get('crafts/import/template', [ImportController::class, 'craftTemplate'])->name('crafts.import.template');
        Route::post('crafts/import', [ImportController::class, 'craftImport'])->name('crafts.import');
        Route::resource('crafts', CraftController::class);
        Route::resource('shifts', ShiftController::class);
        Route::resource('rotation-groups', RotationGroupController::class)->parameters(['rotation-groups' => 'rotationGroup']);
    });

    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::resource('crews', CrewController::class);
        Route::post('crews/{crew}/members', [CrewController::class, 'addMember'])->name('crews.add-member');
        Route::delete('crews/{crew}/members/{crewMember}', [CrewController::class, 'removeMember'])->name('crews.remove-member');
    });

    // ─── Time & Labor ────────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant,field')->group(function () {
        // Week-hours preview endpoint must be registered BEFORE the resource route
        // so it isn't swallowed by the {timesheet} parameter.
        Route::get('timesheets/week-hours', [TimesheetController::class, 'weekHours'])->name('timesheets.week-hours');
        // Print routes must live BEFORE the resource so "print-batch" isn't
        // swallowed by the {timesheet} wildcard. The single-timesheet print
        // uses the bound model AFTER these fixed URIs resolve.
        Route::get('timesheets/print-batch', [TimesheetController::class, 'printBatch'])->name('timesheets.print-batch');
        Route::get('timesheets/{timesheet}/print', [TimesheetController::class, 'print'])->name('timesheets.print');
        // Bulk approve/reject — must come BEFORE the resource so "bulk-approve"
        // isn't matched as a {timesheet} parameter on the (PUT/PATCH/DELETE)
        // resource routes. Brenda asked for these 04.25.2026.
        Route::post('timesheets/bulk-approve', [TimesheetController::class, 'bulkApprove'])->name('timesheets.bulk-approve');
        Route::post('timesheets/bulk-reject',  [TimesheetController::class, 'bulkReject'])->name('timesheets.bulk-reject');
        Route::resource('timesheets', TimesheetController::class);
        Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve');
        Route::post('timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->name('timesheets.reject');
        Route::get('timesheets-bulk', [TimesheetController::class, 'bulkCreate'])->name('timesheets.bulk-create');
        Route::post('timesheets-bulk', [TimesheetController::class, 'bulkStore'])->name('timesheets.bulk-store');

        // 2026-04-29 — Snap-a-Timesheet (AI OCR). Upload a photo of a paper
        // timesheet, Claude extracts the rows, office confirms and bulk-creates.
        Route::post('timesheets/scan-photo',  [TimesheetController::class, 'scanPhoto'])->name('timesheets.scan-photo');
        Route::post('timesheets/scan-commit', [TimesheetController::class, 'scanCommit'])->name('timesheets.scan-commit');
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
        Route::get('payroll/{payrollPeriod}/export', [PayrollController::class, 'export'])->name('payroll.export');
    });

    // ─── Costing ─────────────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        // Cost code import routes registered BEFORE resource so they don't collide with {cost_code}.
        Route::get('cost-codes/import/template', [ImportController::class, 'costCodeTemplate'])->name('cost-codes.import.template');
        Route::post('cost-codes/import', [ImportController::class, 'costCodeImport'])->name('cost-codes.import');
        Route::resource('cost-codes', CostCodeController::class);
        // Client import routes registered BEFORE resource so they don't collide with {client}.
        Route::get('clients/import/template', [ImportController::class, 'clientTemplate'])->name('clients.import.template');
        Route::post('clients/import', [ImportController::class, 'clientImport'])->name('clients.import');
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
        // Import routes registered BEFORE resource so they don't collide with {vendor}.
        Route::get('vendors/import/template', [ImportController::class, 'vendorTemplate'])->name('vendors.import.template');
        Route::post('vendors/import', [ImportController::class, 'vendorImport'])->name('vendors.import');
        Route::resource('vendors', VendorController::class);
    });

    // Invoices — Admin, Accountant
    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
    });

    // Equipment — Admin, PM, Field
    Route::middleware('role:admin,project_manager,field')->group(function () {
        // QR scan + sticker print — registered BEFORE the resource so
        // /equipment/scan/{token} doesn't get matched as {equipment}.
        Route::get('equipment/scan/{token}',           [\App\Http\Controllers\EquipmentQrController::class, 'scan'])->name('equipment.scan');
        Route::post('equipment/scan/{token}/check-out',[\App\Http\Controllers\EquipmentQrController::class, 'checkOut'])->name('equipment.scan.check-out');
        Route::post('equipment/scan/{token}/check-in', [\App\Http\Controllers\EquipmentQrController::class, 'checkIn'])->name('equipment.scan.check-in');
        Route::get('equipment/{equipment}/qr-sticker', [\App\Http\Controllers\EquipmentQrController::class, 'printSticker'])->name('equipment.qr-sticker');

        // Brenda 04.28.2026 — Gantt rental calendar (registered before resource
        // so /equipment/rental-calendar isn't matched as {equipment}).
        Route::get('equipment/rental-calendar', [\App\Http\Controllers\RentalCalendarController::class, 'index'])->name('equipment.rental-calendar');

        Route::resource('equipment', EquipmentController::class);
        Route::post('equipment/{equipment}/assign', [EquipmentController::class, 'assign'])->name('equipment.assign');
        Route::delete('equipment/assignments/{equipmentAssignment}', [EquipmentController::class, 'unassign'])->name('equipment.unassign');
    });

    // Materials — Admin, PM, Field
    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::resource('materials', MaterialController::class);
        Route::post('materials/usage', [MaterialController::class, 'recordUsage'])->name('materials.record-usage');
        // Mobile quick-log (project-scoped) — registered outside the resource
        // so /projects/{project}/materials/quick-log doesn't collide.
        Route::get('projects/{project}/materials/quick-log', [MaterialController::class, 'mobileQuickLog'])->name('projects.materials.quick-log');
    });

    // ─── Tool Tracker (Field Pack) ───────────────────────────────
    Route::middleware('role:admin,project_manager,field')->group(function () {
        Route::get('tools',                       [\App\Http\Controllers\ToolController::class, 'index'])->name('tools.index');
        Route::post('tools',                      [\App\Http\Controllers\ToolController::class, 'store'])->name('tools.store');
        Route::put('tools/{tool}',                [\App\Http\Controllers\ToolController::class, 'update'])->name('tools.update');
        Route::delete('tools/{tool}',             [\App\Http\Controllers\ToolController::class, 'destroy'])->name('tools.destroy');
        Route::post('tools/{tool}/issue',         [\App\Http\Controllers\ToolController::class, 'issue'])->name('tools.issue');
        Route::post('tools/{tool}/return',        [\App\Http\Controllers\ToolController::class, 'return_'])->name('tools.return');
    });

    // ─── Fuel Logs (Field Pack) ──────────────────────────────────
    Route::middleware('role:admin,project_manager,field,accountant')->group(function () {
        Route::get('fuel-logs',              [\App\Http\Controllers\FuelLogController::class, 'index'])->name('fuel-logs.index');
        Route::post('fuel-logs',             [\App\Http\Controllers\FuelLogController::class, 'store'])->name('fuel-logs.store');
        Route::delete('fuel-logs/{fuelLog}', [\App\Http\Controllers\FuelLogController::class, 'destroy'])->name('fuel-logs.destroy');
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

        // Mark retainage released (project-scoped)
        Route::post('billing/{billingInvoice}/release-retainage', [BillingController::class, 'releaseRetainage'])->name('billing.release-retainage');
    });

    // ─── Lien Waivers — Admin, PM, Accountant ────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::get('lien-waivers', [\App\Http\Controllers\LienWaiverController::class, 'index'])->name('lien-waivers.index');
    });

    // ─── RFIs — Admin, PM, Field User ────────────────────────────
    Route::middleware('role:admin,project_manager,field_user')->group(function () {
        Route::get('rfis', [\App\Http\Controllers\RfiController::class, 'index'])->name('rfis.index');
    });

    // ─── Top-level Estimates Portfolio (Brenda 04.25.2026) ───────
    // Distinct from project-scoped estimates (which are change-order-focused).
    // This is the company-wide "all bids" view. Standalone bids without a
    // project_id can be created here — a Project gets auto-spawned on accept.
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::get('estimates', [\App\Http\Controllers\EstimatePortfolioController::class, 'index'])->name('estimates.portfolio');
        Route::post('estimates', [\App\Http\Controllers\EstimatePortfolioController::class, 'store'])->name('estimates.portfolio.store');
        Route::get('estimates/{estimate}', [\App\Http\Controllers\EstimatePortfolioController::class, 'show'])->name('estimates.portfolio.show');
        Route::post('estimates/{estimate}/spawn-project', [\App\Http\Controllers\EstimatePortfolioController::class, 'spawnProject'])->name('estimates.portfolio.spawn-project');
    });

    // ─── Foreman Dashboard — "My Crew Today" ─────────────────────
    // Foreman gets their own crews; admin/PM see all crews.
    Route::get('foreman', [\App\Http\Controllers\ForemanDashboardController::class, 'index'])->name('foreman.dashboard');

    // ─── Mobile Time Clock — everyone who can touch a timesheet ───
    // "My Time" is open to anyone logged in; they can only see their own punches.
    Route::get('my-time', [\App\Http\Controllers\TimeClockController::class, 'index'])->name('time-clock.index');
    Route::post('my-time/clock-in', [\App\Http\Controllers\TimeClockController::class, 'clockIn'])->name('time-clock.clock-in');
    Route::post('my-time/{entry}/clock-out', [\App\Http\Controllers\TimeClockController::class, 'clockOut'])->name('time-clock.clock-out');
    // Crew bulk clock — foreman clocks the entire crew in/out at once
    Route::post('crew-clock/in',  [\App\Http\Controllers\TimeClockController::class, 'crewClockIn'])->name('time-clock.crew-in');
    Route::post('crew-clock/out', [\App\Http\Controllers\TimeClockController::class, 'crewClockOut'])->name('time-clock.crew-out');

    // Admin / PM review — review + convert punches to timesheets.
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::get('admin/time-clock', [\App\Http\Controllers\TimeClockController::class, 'adminIndex'])->name('time-clock.admin');
        Route::post('admin/time-clock/convert', [\App\Http\Controllers\TimeClockController::class, 'convertToTimesheet'])->name('time-clock.convert');
        Route::post('admin/time-clock/{entry}', [\App\Http\Controllers\TimeClockController::class, 'updateEntry'])->name('time-clock.update');
        Route::post('admin/time-clock/{entry}/void', [\App\Http\Controllers\TimeClockController::class, 'void'])->name('time-clock.void');
    });

    // ─── Global Reports ──────────────────────────────────────────
    Route::middleware('role:admin,project_manager,accountant')->group(function () {
        Route::get('reports/timesheets', [ReportController::class, 'timesheetReport'])->name('reports.timesheets');
        Route::get('reports/timesheets/pdf', [ReportController::class, 'timesheetReportPdf'])->name('reports.timesheets.pdf');
        // Phase 7+ — Work-in-Progress (WIP) report (banks/bonding requirement)
        Route::get('reports/wip', [\App\Http\Controllers\WipReportController::class, 'index'])->name('reports.wip');
    });
});
