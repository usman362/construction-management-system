<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsToExcel;
use App\Models\ChangeOrder;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Rfi;
use App\Models\Timesheet;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Centralized "Export to Excel" actions.
 *
 * Every list page in the app gets a download button that hits one of these
 * methods. Filters from the source list are forwarded as query strings so the
 * export honors what the user is currently viewing (status filters, date
 * ranges, project scope).
 *
 * Adding a new export = add one method here + one route + one button.
 */
class ExportController extends Controller
{
    use ExportsToExcel;

    private function stamp(): string
    {
        return now()->format('Y-m-d');
    }

    // ─── Projects ─────────────────────────────────────────────────────
    public function projects(Request $request): BinaryFileResponse
    {
        $query = Project::with('client');
        if ($status = $request->input('status'))     $query->where('status', $status);
        if ($clientId = $request->input('client_id')) $query->where('client_id', $clientId);

        return $this->streamExcel(
            filename:  'projects-' . $this->stamp() . '.xlsx',
            sheetName: 'Projects',
            rows:      $query->orderBy('project_number')->get(),
            columns: [
                ['header' => 'Project #',       'value' => 'project_number',                'width' => 14],
                ['header' => 'Name',            'value' => 'name',                          'width' => 35],
                ['header' => 'Client',          'value' => fn ($p) => $p->client?->name,    'width' => 25],
                ['header' => 'Status',          'value' => fn ($p) => ucfirst($p->status),  'width' => 12],
                ['header' => 'Start',           'value' => fn ($p) => optional($p->start_date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'End',             'value' => fn ($p) => optional($p->end_date)->format('Y-m-d'),   'format' => 'date', 'width' => 12],
                ['header' => 'Original Budget', 'value' => fn ($p) => (float) $p->original_budget, 'format' => 'currency', 'width' => 16],
                ['header' => 'Current Budget',  'value' => fn ($p) => (float) $p->current_budget,  'format' => 'currency', 'width' => 16],
                ['header' => 'Contract Value',  'value' => fn ($p) => (float) $p->contract_value,  'format' => 'currency', 'width' => 16],
                ['header' => 'Estimate',        'value' => fn ($p) => (float) $p->estimate,        'format' => 'currency', 'width' => 16],
            ],
            title: 'Projects — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Employees ────────────────────────────────────────────────────
    public function employees(Request $request): BinaryFileResponse
    {
        $query = Employee::with('craft');
        if ($status = $request->input('status')) $query->where('status', $status);

        return $this->streamExcel(
            filename:  'employees-' . $this->stamp() . '.xlsx',
            sheetName: 'Employees',
            rows:      $query->orderBy('last_name')->orderBy('first_name')->get(),
            columns: [
                ['header' => 'Employee #', 'value' => 'employee_number',                  'width' => 14],
                ['header' => 'First',      'value' => 'first_name',                       'width' => 18],
                ['header' => 'Last',       'value' => 'last_name',                        'width' => 18],
                ['header' => 'Craft',      'value' => fn ($e) => $e->craft?->name,        'width' => 22],
                ['header' => 'Email',      'value' => 'email',                            'width' => 28],
                ['header' => 'Phone',      'value' => 'phone',                            'width' => 16],
                ['header' => 'Status',     'value' => fn ($e) => ucfirst($e->status ?? '—'), 'width' => 12],
                ['header' => 'Hire Date',  'value' => fn ($e) => optional($e->hire_date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Base Rate',  'value' => fn ($e) => (float) ($e->base_hourly_rate ?? 0), 'format' => 'currency', 'width' => 12],
            ],
            title: 'Employees — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Vendors ──────────────────────────────────────────────────────
    public function vendors(): BinaryFileResponse
    {
        return $this->streamExcel(
            filename:  'vendors-' . $this->stamp() . '.xlsx',
            sheetName: 'Vendors',
            rows:      Vendor::orderBy('name')->get(),
            columns: [
                ['header' => 'Code',    'value' => 'vendor_code',  'width' => 14],
                ['header' => 'Name',    'value' => 'name',         'width' => 32],
                ['header' => 'Contact', 'value' => 'contact_name', 'width' => 22],
                ['header' => 'Email',   'value' => 'email',        'width' => 28],
                ['header' => 'Phone',   'value' => 'phone',        'width' => 16],
                ['header' => 'City',    'value' => 'city',         'width' => 16],
                ['header' => 'State',   'value' => 'state',        'width' => 8],
            ],
            title: 'Vendors — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Timesheets ───────────────────────────────────────────────────
    public function timesheets(Request $request): BinaryFileResponse
    {
        $query = Timesheet::with(['employee:id,first_name,last_name,employee_number',
                                  'project:id,project_number,name',
                                  'costCode:id,code,name']);

        if ($status = $request->input('status'))         $query->where('status', $status);
        if ($projectId = $request->input('project_id'))  $query->where('project_id', $projectId);
        if ($employeeId = $request->input('employee_id'))$query->where('employee_id', $employeeId);
        if ($from = $request->input('date_from'))        $query->whereDate('date', '>=', $from);
        if ($to = $request->input('date_to'))            $query->whereDate('date', '<=', $to);

        return $this->streamExcel(
            filename:  'timesheets-' . $this->stamp() . '.xlsx',
            sheetName: 'Timesheets',
            rows:      $query->orderByDesc('date')->orderBy('employee_id')->get(),
            columns: [
                ['header' => 'Date',        'value' => fn ($t) => optional($t->date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Employee',    'value' => fn ($t) => trim(($t->employee->first_name ?? '') . ' ' . ($t->employee->last_name ?? '')), 'width' => 25],
                ['header' => 'Emp #',       'value' => fn ($t) => $t->employee->employee_number ?? '—', 'width' => 10],
                ['header' => 'Project #',   'value' => fn ($t) => $t->project->project_number ?? '—', 'width' => 14],
                ['header' => 'Project',     'value' => fn ($t) => $t->project->name ?? '—', 'width' => 28],
                ['header' => 'Cost Code',   'value' => fn ($t) => $t->costCode ? ($t->costCode->code . ' — ' . $t->costCode->name) : '—', 'width' => 28],
                ['header' => 'Reg Hours',   'value' => fn ($t) => (float) $t->regular_hours,  'format' => 'number', 'width' => 11],
                ['header' => 'OT Hours',    'value' => fn ($t) => (float) $t->overtime_hours, 'format' => 'number', 'width' => 11],
                ['header' => 'Total Hours', 'value' => fn ($t) => (float) $t->total_hours,    'format' => 'number', 'width' => 12],
                ['header' => 'Cost',        'value' => fn ($t) => (float) $t->total_cost,     'format' => 'currency', 'width' => 14],
                ['header' => 'Billable',    'value' => fn ($t) => (float) $t->billable_amount, 'format' => 'currency', 'width' => 14],
                ['header' => 'Status',      'value' => fn ($t) => ucfirst($t->status ?? '—'), 'width' => 12],
            ],
            title: 'Timesheets — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Invoices (Vendor) ────────────────────────────────────────────
    public function invoices(Request $request): BinaryFileResponse
    {
        $query = Invoice::with(['vendor:id,name', 'project:id,project_number', 'costCode:id,code,name']);
        if ($status = $request->input('status')) $query->where('status', $status);

        return $this->streamExcel(
            filename:  'invoices-' . $this->stamp() . '.xlsx',
            sheetName: 'Invoices',
            rows:      $query->orderByDesc('invoice_date')->get(),
            columns: [
                ['header' => 'Invoice #',  'value' => 'invoice_number',                          'width' => 16],
                ['header' => 'Vendor',     'value' => fn ($i) => $i->vendor->name ?? '—',        'width' => 24],
                ['header' => 'Project',    'value' => fn ($i) => $i->project->project_number ?? '—', 'width' => 14],
                ['header' => 'Cost Code',  'value' => fn ($i) => $i->costCode ? $i->costCode->code : '—', 'width' => 12],
                ['header' => 'Amount',     'value' => fn ($i) => (float) $i->amount, 'format' => 'currency', 'width' => 14],
                ['header' => 'Date',       'value' => fn ($i) => optional($i->invoice_date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Due',        'value' => fn ($i) => optional($i->due_date)->format('Y-m-d'),     'format' => 'date', 'width' => 12],
                ['header' => 'Paid',       'value' => fn ($i) => optional($i->paid_date)->format('Y-m-d'),    'format' => 'date', 'width' => 12],
                ['header' => 'Status',     'value' => fn ($i) => ucfirst($i->status ?? '—'),     'width' => 12],
            ],
            title: 'Vendor Invoices — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Purchase Orders ──────────────────────────────────────────────
    public function purchaseOrders(Request $request): BinaryFileResponse
    {
        $query = PurchaseOrder::with(['vendor:id,name', 'project:id,project_number']);
        if ($status = $request->input('status')) $query->where('status', $status);

        return $this->streamExcel(
            filename:  'purchase-orders-' . $this->stamp() . '.xlsx',
            sheetName: 'Purchase Orders',
            rows:      $query->orderByDesc('date')->get(),
            columns: [
                ['header' => 'PO #',     'value' => 'po_number',                              'width' => 14],
                ['header' => 'Vendor',   'value' => fn ($p) => $p->vendor->name ?? '—',       'width' => 24],
                ['header' => 'Project',  'value' => fn ($p) => $p->project->project_number ?? '—', 'width' => 14],
                ['header' => 'Date',     'value' => fn ($p) => optional($p->date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Total',    'value' => fn ($p) => (float) $p->total_amount, 'format' => 'currency', 'width' => 14],
                ['header' => 'Status',   'value' => fn ($p) => ucfirst($p->status ?? '—'), 'width' => 12],
                ['header' => 'Description', 'value' => 'description',                       'width' => 40],
            ],
            title: 'Purchase Orders — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── Change Orders ────────────────────────────────────────────────
    public function changeOrders(Request $request): BinaryFileResponse
    {
        $query = ChangeOrder::with('project:id,project_number,name');
        if ($status = $request->input('status'))         $query->where('status', $status);
        if ($projectId = $request->input('project_id'))  $query->where('project_id', $projectId);

        return $this->streamExcel(
            filename:  'change-orders-' . $this->stamp() . '.xlsx',
            sheetName: 'Change Orders',
            rows:      $query->orderByDesc('date')->get(),
            columns: [
                ['header' => 'CO #',     'value' => 'co_number',                                  'width' => 12],
                ['header' => 'Project',  'value' => fn ($c) => $c->project->project_number ?? '—', 'width' => 14],
                ['header' => 'Title',    'value' => 'title',                                      'width' => 32],
                ['header' => 'Date',     'value' => fn ($c) => optional($c->date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Amount',   'value' => fn ($c) => (float) $c->amount, 'format' => 'currency', 'width' => 14],
                ['header' => 'Days',     'value' => fn ($c) => (int) $c->contract_time_change_days, 'format' => 'integer', 'width' => 8],
                ['header' => 'Status',   'value' => fn ($c) => ucfirst($c->status ?? '—'), 'width' => 12],
            ],
            title: 'Change Orders — exported ' . now()->format('M j, Y g:i A'),
        );
    }

    // ─── RFIs ─────────────────────────────────────────────────────────
    public function rfis(Request $request): BinaryFileResponse
    {
        $query = Rfi::with(['project:id,project_number', 'submitter:id,name', 'assignee:id,name']);
        if ($status = $request->input('status'))         $query->where('status', $status);
        if ($priority = $request->input('priority'))     $query->where('priority', $priority);
        if ($projectId = $request->input('project_id'))  $query->where('project_id', $projectId);
        if ($assignedTo = $request->input('assigned_to')) $query->where('assigned_to', $assignedTo);

        return $this->streamExcel(
            filename:  'rfis-' . $this->stamp() . '.xlsx',
            sheetName: 'RFIs',
            rows:      $query->orderByDesc('submitted_date')->get(),
            columns: [
                ['header' => 'RFI #',     'value' => 'rfi_number',                                'width' => 14],
                ['header' => 'Project',   'value' => fn ($r) => $r->project->project_number ?? '—', 'width' => 14],
                ['header' => 'Subject',   'value' => 'subject',                                   'width' => 40],
                ['header' => 'Status',    'value' => fn ($r) => ucfirst(str_replace('_', ' ', $r->status ?? '—')), 'width' => 14],
                ['header' => 'Priority',  'value' => fn ($r) => ucfirst($r->priority ?? '—'),     'width' => 10],
                ['header' => 'Category',  'value' => fn ($r) => ucfirst(str_replace('_', ' ', $r->category ?? '—')), 'width' => 16],
                ['header' => 'Submitted', 'value' => fn ($r) => optional($r->submitted_date)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Needed By', 'value' => fn ($r) => optional($r->needed_by)->format('Y-m-d'), 'format' => 'date', 'width' => 12],
                ['header' => 'Submitter', 'value' => fn ($r) => $r->submitter->name ?? '—', 'width' => 22],
                ['header' => 'Assignee',  'value' => fn ($r) => $r->assignee->name ?? '—',  'width' => 22],
            ],
            title: 'RFIs — exported ' . now()->format('M j, Y g:i A'),
        );
    }
}
