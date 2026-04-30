<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\TimesheetCostAllocation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectBillableRate;
use App\Models\Crew;
use App\Models\Shift;
use App\Models\CostCode;
use App\Models\Craft;
use App\Models\TimesheetScanLog;
use App\Services\OvertimeCalculator;
use App\Services\TimesheetOcrService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TimesheetController extends Controller
{
    public function __construct(private OvertimeCalculator $overtimeCalculator)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        return view('timesheets.index', $this->timesheetFormOptions());
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = Timesheet::with(['employee', 'project', 'crew', 'costCode']);

        // Apply filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Get total records count before filtering
        $totalRecords = Timesheet::count();

        // Apply search (DataTables sends 'search[value]')
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('employee', function ($eq) use ($searchValue) {
                    $eq->where('first_name', 'like', "%{$searchValue}%")
                        ->orWhere('last_name', 'like', "%{$searchValue}%");
                })->orWhereHas('project', function ($pq) use ($searchValue) {
                    $pq->where('name', 'like', "%{$searchValue}%");
                })->orWhereHas('crew', function ($cq) use ($searchValue) {
                    $cq->where('name', 'like', "%{$searchValue}%");
                })->orWhere('status', 'like', "%{$searchValue}%");
            });
        }

        // Get filtered records count
        $recordsFiltered = $query->count();

        // Ordering (columns match index DataTable: date, employee, project, cost code, crew, reg, ot, dt, total, cost, status, actions)
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = [
            0 => 'date',
            1 => 'employee_id',
            2 => 'project_id',
            3 => 'cost_code_id',
            4 => 'crew_id',
            5 => 'regular_hours',
            6 => 'overtime_hours',
            7 => 'double_time_hours',
            8 => 'total_hours',
            9 => 'total_cost',
            10 => 'status',
        ];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('date', 'desc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $timesheets = $query->offset($start)->limit($length)->get();

        // Format data for DataTables (must match resources/views/timesheets/index.blade.php columns)
        $data = $timesheets->map(function ($timesheet) {
            $emp = $timesheet->employee;

            return [
                'id' => $timesheet->id,
                'employee_id' => $timesheet->employee_id,
                'employee_name' => $emp ? trim($emp->first_name.' '.$emp->last_name) : '—',
                'project_id' => $timesheet->project_id,
                'project_name' => $timesheet->project->name ?? '',
                'cost_code' => $timesheet->costCode?->code ?? '—',
                'crew_id' => $timesheet->crew_id,
                'crew_name' => $timesheet->crew->name ?? '—',
                'date' => optional($timesheet->date)->format('Y-m-d'),
                'shift_id' => $timesheet->shift_id,
                'regular_hours' => $timesheet->regular_hours,
                'overtime_hours' => $timesheet->overtime_hours,
                'double_time_hours' => $timesheet->double_time_hours,
                'total_hours' => $timesheet->total_hours,
                'cost' => $timesheet->total_cost,
                'rate_type' => $timesheet->rate_type ?? 'standard',
                'status' => $timesheet->status,
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($request->input('draw', 0)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'required|exists:projects,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'crew_id' => 'nullable|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'work_order_number' => 'nullable|string|max:100',
            // Either enter a single "hours_worked" total (system splits into
            // Reg/OT via the weekly-40 rule) OR override each bucket manually.
            'hours_worked' => 'nullable|numeric|min:0',
            'regular_hours' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'double_time_hours' => 'nullable|numeric|min:0',
            'force_overtime' => 'nullable|boolean',
            'gate_log_hours' => 'nullable|numeric|min:0',
            'work_through_lunch' => 'nullable|boolean',
            'is_billable' => 'nullable|boolean',
            'per_diem' => 'nullable|boolean',
            'per_diem_amount' => 'nullable|numeric|min:0',
            'client_signature' => 'nullable|string',
            'client_signature_name' => 'nullable|string|max:150',
            // 2026-04-28: Earnings category (HE/HO/VA). Default 'HE' = worked hours.
            'earnings_category' => 'nullable|in:HE,HO,VA',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $forceOT = $request->boolean('force_overtime');
        $split = $this->resolveHourSplit(
            $employee,
            $validated['date'],
            $validated,
            $forceOT,
            null
        );
        $reg = $split['regular_hours'];
        $ot  = $split['overtime_hours'];
        $dt  = $split['double_time_hours'];

        $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);
        // Billable flag: default true on create (hours exist to bill), honor explicit unchecked box
        $isBillable = $request->has('is_billable') ? $request->boolean('is_billable') : true;

        $timesheet = Timesheet::create([
            'employee_id' => $validated['employee_id'],
            'project_id' => $validated['project_id'],
            'cost_code_id' => $validated['cost_code_id'] ?? null,
            'cost_type_id' => $validated['cost_type_id'] ?? null,
            'crew_id' => $validated['crew_id'] ?? null,
            'date' => $validated['date'],
            'shift_id' => $validated['shift_id'] ?? null,
            'work_order_number' => $validated['work_order_number'] ?? null,
            'gate_log_hours' => $validated['gate_log_hours'] ?? null,
            'work_through_lunch' => $request->boolean('work_through_lunch'),
            'client_signature' => $validated['client_signature'] ?? null,
            'client_signature_name' => $validated['client_signature_name'] ?? null,
            'signed_at' => !empty($validated['client_signature']) ? now() : null,
            'regular_hours' => $reg,
            'overtime_hours' => $ot,
            'double_time_hours' => $dt,
            'force_overtime' => $forceOT,
            'total_hours' => $totals['total_hours'],
            'regular_rate' => $totals['regular_rate'],
            'overtime_rate' => $totals['overtime_rate'],
            'total_cost' => $totals['total_cost'],
            'billable_rate' => $totals['billable_rate'],
            'billable_amount' => $isBillable ? $totals['billable_amount'] : 0,
            'is_billable' => $isBillable,
            'rate_type' => $totals['rate_type'],
            'earnings_category' => $validated['earnings_category'] ?? 'HE',
            'project_billable_rate_id' => $totals['project_billable_rate_id'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncTimesheetCostAllocation($timesheet->fresh());
        $this->applyPerDiemOverride($timesheet, $request);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet created successfully.',
            'timesheet' => $timesheet,
        ], 201);
    }

    public function show(Timesheet $timesheet): View
    {
        $timesheet->load(['employee', 'project', 'crew', 'shift', 'costCode', 'costAllocations.costCode']);

        return view('timesheets.show', array_merge(
            ['timesheet' => $timesheet],
            $this->timesheetFormOptions()
        ));
    }

    public function edit(Timesheet $timesheet): JsonResponse
    {
        $timesheet->load(['employee', 'project', 'crew', 'costAllocations']);

        // Serialize with cost_allocations so the edit modal can populate
        // per_diem state (first/only allocation carries the amount).
        return response()->json($timesheet->toArray());
    }

    /**
     * Single-timesheet print view — shared by field (client signature) and
     * office (billing) use cases. Returns HTML with auto-print dialog unless
     * `?mode=pdf` is supplied, in which case a DomPDF download is streamed.
     */
    public function print(Request $request, Timesheet $timesheet): \Illuminate\Http\Response|View
    {
        $timesheet->load([
            'employee', 'project.client', 'crew', 'shift', 'costCode',
            'costAllocations.costCode', 'approver',
        ]);

        $data = [
            'timesheets'   => collect([$timesheet]),
            'single'       => true,
            'heading'      => 'Timesheet — ' . $timesheet->date->format('M j, Y'),
            'printMode'    => $request->query('mode', 'html'),
            'generatedAt'  => now(),
            'companyName'  => \App\Models\Setting::get('company_name', 'BuildTrack'),
            'companyLogo'  => \App\Models\Setting::get('company_logo'),
            'primaryColor' => \App\Models\Setting::get('primary_color', '#2563eb'),
        ];

        if ($request->query('mode') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('timesheets.print', $data)
                ->setPaper('letter', 'portrait');
            return $pdf->download('timesheet-' . $timesheet->id . '-' . $timesheet->date->format('Y-m-d') . '.pdf');
        }

        return view('timesheets.print', $data);
    }

    /**
     * Batch print — office billing workflow. Accepts the same filter params as
     * the timesheet index (employee_id/project_id/date_from/date_to/crew_id/status)
     * and renders every matching row on one printable sheet, page-breaking
     * between timesheets so each can be signed/filed individually.
     */
    public function printBatch(Request $request): \Illuminate\Http\Response|View
    {
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'project_id'  => 'nullable|exists:projects,id',
            'crew_id'     => 'nullable|exists:crews,id',
            'status'      => 'nullable|string',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'mode'        => 'nullable|in:html,pdf',
            // 2026-04-28 (Brenda): "weekly" = one page per employee per
            // Mon–Sun week, full 7-day grid. Default 'daily' keeps the
            // legacy one-page-per-timesheet output.
            'layout'      => 'nullable|in:daily,weekly',
        ]);

        $query = Timesheet::with([
            'employee', 'project.client', 'crew', 'shift', 'costCode',
            'costAllocations.costCode', 'approver',
        ]);

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('project_id'))  $query->where('project_id', $request->project_id);
        if ($request->filled('crew_id'))     $query->where('crew_id', $request->crew_id);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('date_from'))   $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('date', '<=', $request->date_to);

        // Sort for readability — group by project, then by date, then by employee
        $timesheets = $query->orderBy('project_id')
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        // 2026-04-29 (Brenda): Empty result no longer aborts with a 404
        // page. The clerk picked filters that just didn't match anything —
        // show a friendly screen with the filters they used + a "back"
        // link so they can adjust and try again. Only abort 404 when
        // someone hits the URL directly without any filters at all.
        if ($timesheets->isEmpty()) {
            // For PDF mode, fall back to redirecting with a flash so the
            // browser shows the message instead of an empty PDF.
            if ($request->query('mode') === 'pdf') {
                return redirect()->route('timesheets.index')
                    ->with('error', 'No timesheets match the selected filters. Try widening the date range or removing a filter.');
            }
            return response()->view('timesheets.print-empty', [
                'companyName'  => \App\Models\Setting::get('company_name', 'BuildTrack'),
                'companyLogo'  => \App\Models\Setting::get('company_logo'),
                'primaryColor' => \App\Models\Setting::get('primary_color', '#2563eb'),
                'filters'      => $request->only(['employee_id','project_id','crew_id','status','date_from','date_to','layout']),
                'projectName'  => $request->filled('project_id')
                    ? optional(\App\Models\Project::find($request->project_id))->name
                    : null,
                'crewName'     => $request->filled('crew_id')
                    ? optional(\App\Models\Crew::find($request->crew_id))->name
                    : null,
                'employeeName' => $request->filled('employee_id')
                    ? optional(\App\Models\Employee::find($request->employee_id))->fullName ?? null
                    : null,
            ], 200);
        }

        $layout = $request->query('layout', 'daily');

        // Safety guard: weekly layout against the entire history is meaningless
        // and will OOM on a busy DB. Require at least one filter (date range,
        // employee, project, or crew) before letting the report run.
        if ($layout === 'weekly'
            && ! $request->filled('date_from')
            && ! $request->filled('date_to')
            && ! $request->filled('employee_id')
            && ! $request->filled('project_id')
            && ! $request->filled('crew_id')
        ) {
            abort(422, 'Weekly summary requires at least one filter (date range, employee, project, or crew).');
        }

        $heading = ($layout === 'weekly' ? 'Weekly Timesheet Summary — ' : 'Timesheet Batch — ');
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $heading .= ($request->date_from ?: '…') . ' to ' . ($request->date_to ?: '…');
        } else {
            $heading .= 'All Dates';
        }

        $data = [
            'timesheets'   => $timesheets,
            'single'       => false,
            'heading'      => $heading,
            'printMode'    => $request->query('mode', 'html'),
            'generatedAt'  => now(),
            'companyName'  => \App\Models\Setting::get('company_name', 'BuildTrack'),
            'companyLogo'  => \App\Models\Setting::get('company_logo'),
            'primaryColor' => \App\Models\Setting::get('primary_color', '#2563eb'),
            'filters'      => $request->only(['employee_id','project_id','crew_id','status','date_from','date_to']),
        ];

        // Weekly layout — bucket timesheets by employee_id + ISO week (Mon–Sun),
        // then build one printable page per (employee, week) pair with Mon–Sun
        // columns and per-day ST/OT/PR cells. Brenda's payroll/billing flow
        // reviews a whole week at a glance per worker.
        if ($layout === 'weekly') {
            $data['weeks'] = $this->groupTimesheetsByEmployeeWeek($timesheets);
            $view = 'timesheets.print-weekly';
            $filename = 'timesheets-weekly-' . now()->format('Y-m-d-His') . '.pdf';
        } else {
            $view = 'timesheets.print';
            $filename = 'timesheets-batch-' . now()->format('Y-m-d-His') . '.pdf';
        }

        if ($request->query('mode') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)
                ->setPaper('letter', $layout === 'weekly' ? 'landscape' : 'portrait');
            return $pdf->download($filename);
        }

        return view($view, $data);
    }

    /**
     * Bucket a flat collection of timesheets into one entry per
     * (employee_id, ISO week-start). Each bucket carries a 7-element
     * `days` array (Mon=0 … Sun=6) of timesheet collections plus
     * pre-totaled ST/OT/PR/total/cost numbers used by the weekly print.
     *
     * Returns a list (sortable, indexed) so the blade can foreach over
     * it and emit one printable page per element.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Timesheet> $timesheets
     * @return \Illuminate\Support\Collection<int, array{
     *     employee: \App\Models\Employee,
     *     week_start: \Carbon\CarbonImmutable,
     *     week_end: \Carbon\CarbonImmutable,
     *     days: array<int, \Illuminate\Support\Collection>,
     *     totals: array{regular: float, overtime: float, double_time: float, total: float, cost: float, billable: float}
     * }>
     */
    protected function groupTimesheetsByEmployeeWeek(\Illuminate\Support\Collection $timesheets): \Illuminate\Support\Collection
    {
        $buckets = [];

        foreach ($timesheets as $ts) {
            $date = \Carbon\CarbonImmutable::parse($ts->date);
            // ISO week starts Monday — matches the rest of the system's
            // weekly-OT calculation (App\Services\OvertimeCalculator).
            $weekStart = $date->startOfWeek(\Carbon\CarbonImmutable::MONDAY);
            $weekEnd   = $weekStart->addDays(6);
            $key = $ts->employee_id . '|' . $weekStart->format('Y-m-d');
            // Mon = 0, Sun = 6
            $dow = (int) $date->dayOfWeekIso - 1;

            if (! isset($buckets[$key])) {
                // BUG FIX 2026-04-29: array_fill(0, 7, collect()) makes all 7
                // slots reference the SAME Collection object — pushing one
                // timesheet into days[$dow] would clone it into every other
                // day too, blowing up the print as 4× duplicates per cell.
                // Build the array with distinct collect() instances.
                $days = [];
                for ($d = 0; $d < 7; $d++) {
                    $days[$d] = collect();
                }
                $buckets[$key] = [
                    'employee'   => $ts->employee,
                    'week_start' => $weekStart,
                    'week_end'   => $weekEnd,
                    'days'       => $days,
                    'totals'     => [
                        'regular'     => 0.0,
                        'overtime'    => 0.0,
                        'double_time' => 0.0,
                        'total'       => 0.0,
                        'cost'        => 0.0,
                        'billable'    => 0.0,
                        // 2026-04-30 (Brenda): per diem rolls up from
                        // TimesheetCostAllocation.per_diem_amount per row.
                        'per_diem'    => 0.0,
                    ],
                ];
            }

            $buckets[$key]['days'][$dow]->push($ts);
            $buckets[$key]['totals']['regular']     += (float) $ts->regular_hours;
            $buckets[$key]['totals']['overtime']    += (float) $ts->overtime_hours;
            $buckets[$key]['totals']['double_time'] += (float) $ts->double_time_hours;
            $buckets[$key]['totals']['total']       += (float) $ts->total_hours;
            $buckets[$key]['totals']['cost']        += (float) $ts->total_cost;
            $buckets[$key]['totals']['billable']    += (float) $ts->billable_amount;
            // Sum every cost allocation's per_diem_amount on this timesheet.
            // costAllocations is eager-loaded by printBatch().
            $buckets[$key]['totals']['per_diem']    += (float) $ts->costAllocations->sum('per_diem_amount');
        }

        // Sort: employee name, then week_start
        return collect($buckets)
            ->sortBy(function ($b) {
                $emp = $b['employee'];
                $name = $emp ? trim(($emp->last_name ?? '') . ' ' . ($emp->first_name ?? '')) : 'zzz';
                return $name . '|' . $b['week_start']->format('Y-m-d');
            })
            ->values();
    }

    /**
     * @return array{employees: \Illuminate\Database\Eloquent\Collection, projects: \Illuminate\Database\Eloquent\Collection, crews: \Illuminate\Database\Eloquent\Collection, shifts: \Illuminate\Database\Eloquent\Collection}
     */
    private function timesheetFormOptions(): array
    {
        return [
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'crews' => Crew::query()->with('project')->orderBy('name')->get(),
            'shifts' => Shift::query()->orderBy('name')->get(),
            'costCodes' => CostCode::query()->orderBy('code')->get(['id', 'code', 'name']),
            'costTypes' => \App\Models\CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
        ];
    }

    public function update(Request $request, Timesheet $timesheet): JsonResponse
    {
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'required|exists:projects,id',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'crew_id' => 'nullable|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'work_order_number' => 'nullable|string|max:100',
            'hours_worked' => 'nullable|numeric|min:0',
            'regular_hours' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'double_time_hours' => 'nullable|numeric|min:0',
            'force_overtime' => 'nullable|boolean',
            'gate_log_hours' => 'nullable|numeric|min:0',
            'work_through_lunch' => 'nullable|boolean',
            'is_billable' => 'nullable|boolean',
            'per_diem' => 'nullable|boolean',
            'per_diem_amount' => 'nullable|numeric|min:0',
            'client_signature' => 'nullable|string',
            'client_signature_name' => 'nullable|string|max:150',
            'earnings_category' => 'nullable|in:HE,HO,VA',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $forceOT = $request->boolean('force_overtime');
        // Exclude this timesheet from the week-so-far tally so an edit
        // doesn't double-count the hours we're about to overwrite.
        $split = $this->resolveHourSplit(
            $employee,
            $validated['date'],
            $validated,
            $forceOT,
            $timesheet->id
        );
        $reg = $split['regular_hours'];
        $ot  = $split['overtime_hours'];
        $dt  = $split['double_time_hours'];

        $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);
        // Honor the "Billable" checkbox the user explicitly sent. If the field
        // wasn't submitted at all (e.g. a partial update), keep the previous value.
        $isBillable = $request->has('is_billable')
            ? $request->boolean('is_billable')
            : (bool) $timesheet->is_billable;

        $timesheet->update([
            'employee_id' => $validated['employee_id'],
            'project_id' => $validated['project_id'],
            'cost_code_id' => $validated['cost_code_id'] ?? null,
            'cost_type_id' => $validated['cost_type_id'] ?? null,
            'crew_id' => $validated['crew_id'] ?? null,
            'date' => $validated['date'],
            'shift_id' => $validated['shift_id'] ?? null,
            'work_order_number' => $validated['work_order_number'] ?? null,
            'gate_log_hours' => $validated['gate_log_hours'] ?? null,
            'work_through_lunch' => $request->boolean('work_through_lunch'),
            'client_signature' => $validated['client_signature'] ?? $timesheet->client_signature,
            'client_signature_name' => $validated['client_signature_name'] ?? $timesheet->client_signature_name,
            'signed_at' => !empty($validated['client_signature']) && $timesheet->signed_at === null ? now() : $timesheet->signed_at,
            'regular_hours' => $reg,
            'overtime_hours' => $ot,
            'double_time_hours' => $dt,
            'force_overtime' => $forceOT,
            'total_hours' => $totals['total_hours'],
            'regular_rate' => $totals['regular_rate'],
            'overtime_rate' => $totals['overtime_rate'],
            'total_cost' => $totals['total_cost'],
            'billable_rate' => $totals['billable_rate'],
            'billable_amount' => $isBillable ? $totals['billable_amount'] : 0,
            'is_billable' => $isBillable,
            'rate_type' => $totals['rate_type'],
            'earnings_category' => $validated['earnings_category'] ?? $timesheet->earnings_category ?? 'HE',
            'project_billable_rate_id' => $totals['project_billable_rate_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncTimesheetCostAllocation($timesheet->fresh());
        $this->applyPerDiemOverride($timesheet->fresh(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet updated successfully.',
            'timesheet' => $timesheet->fresh(),
        ]);
    }

    /**
     * Keep a single allocation row in sync so payroll and reports can resolve cost code by hours.
     * Auto-fills per_diem_amount from the project's default_per_diem_rate if set,
     * and tags any per diem with the dedicated PER DIEM cost type (code 07) so
     * reports don't lump it in with labor.
     */
    private function syncTimesheetCostAllocation(Timesheet $timesheet): void
    {
        $timesheet->costAllocations()->delete();
        if ($timesheet->cost_code_id) {
            $perDiem = 0;
            if ($timesheet->project_id) {
                $rate = $timesheet->project?->default_per_diem_rate ?? 0;
                // Apply per diem only for days the employee actually worked (total_hours > 0)
                $perDiem = $timesheet->total_hours > 0 ? (float) $rate : 0;
            }

            // Resolve the dedicated per-diem cost type once per request. Looked
            // up by code (not a hard-coded id) so it works on any seeded
            // environment. Cached statically to avoid a DB hit per timesheet
            // during bulk-create.
            static $perDiemCostTypeId = null;
            if ($perDiemCostTypeId === null && $perDiem > 0) {
                $perDiemCostTypeId = \App\Models\CostType::where('code', '07')->value('id');
            }

            TimesheetCostAllocation::create([
                'timesheet_id' => $timesheet->id,
                'cost_code_id' => $timesheet->cost_code_id,
                'cost_type_id' => $timesheet->cost_type_id,
                // Only tag per_diem_cost_type_id when there is actual per diem
                // on this row — keeps it NULL for hours-only allocations.
                'per_diem_cost_type_id' => $perDiem > 0 ? $perDiemCostTypeId : null,
                'hours' => $timesheet->total_hours,
                'cost' => $timesheet->total_cost,
                'per_diem_amount' => $perDiem,
            ]);
        }
    }

    public function destroy(Timesheet $timesheet): JsonResponse
    {
        $timesheet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timesheet deleted successfully.',
        ]);
    }

    public function approve(Request $request, Timesheet $timesheet): JsonResponse|RedirectResponse
    {
        // 2026-04-28 (Brenda): Only the Admin (her) and Site Managers may
        // approve timesheets. Foremen submit on behalf of crew but cannot
        // sign off on their own labor.
        abort_unless(auth()->user()?->canApproveTimesheets(), 403,
            'Only an Admin or Site Manager may approve timesheets.');

        $timesheet->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // The Approve button on timesheets/show.blade.php is a plain HTML form, so a
        // JSON response would render as raw text and look like an error page to the
        // user. Only return JSON when the caller actually asked for it (AJAX).
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Timesheet approved.',
                'timesheet' => $timesheet->fresh(),
            ]);
        }

        return redirect()
            ->route('timesheets.show', $timesheet)
            ->with('success', 'Timesheet approved.');
    }

    /**
     * Bulk approve / reject — operates on an array of timesheet IDs in one
     * shot. Requested by Brenda 04.25.2026: "we might need a bulk approval
     * for timesheets."
     *
     * Wrapped in a single transaction so partial-failure leaves the data
     * untouched. Only acts on timesheets whose status is currently
     * 'submitted' so re-running with already-approved IDs is a no-op.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->canApproveTimesheets(), 403,
            'Only an Admin or Site Manager may approve timesheets.');

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:timesheets,id',
        ]);

        $count = 0;
        \DB::transaction(function () use ($data, &$count) {
            $count = Timesheet::whereIn('id', $data['ids'])
                ->where('status', 'submitted')
                ->update([
                    'status'      => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
        });

        $skipped = count($data['ids']) - $count;
        $msg = "{$count} timesheet(s) approved." . ($skipped > 0
            ? " {$skipped} skipped (not in 'submitted' status)." : '');

        return response()->json(['success' => true, 'message' => $msg, 'approved' => $count, 'skipped' => $skipped]);
    }

    public function bulkReject(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->canApproveTimesheets(), 403,
            'Only an Admin or Site Manager may reject timesheets.');

        $data = $request->validate([
            'ids'              => 'required|array|min:1',
            'ids.*'            => 'integer|exists:timesheets,id',
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $reason = $data['rejection_reason'] ?? null;
        $count  = 0;

        \DB::transaction(function () use ($data, $reason, &$count) {
            $rows = Timesheet::whereIn('id', $data['ids'])
                ->where('status', 'submitted')
                ->get();

            foreach ($rows as $ts) {
                $notes = $ts->notes;
                if ($reason) {
                    $notes = trim(($notes ? $notes . "\n\n" : '') . 'Rejection: ' . $reason);
                }
                $ts->update(['status' => 'rejected', 'notes' => $notes]);
                $count++;
            }
        });

        $skipped = count($data['ids']) - $count;
        $msg = "{$count} timesheet(s) rejected." . ($skipped > 0
            ? " {$skipped} skipped (not in 'submitted' status)." : '');

        return response()->json(['success' => true, 'message' => $msg, 'rejected' => $count, 'skipped' => $skipped]);
    }

    public function reject(Request $request, Timesheet $timesheet): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->canApproveTimesheets(), 403,
            'Only an Admin or Site Manager may reject timesheets.');

        $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $notes = $timesheet->notes;
        if ($request->filled('rejection_reason')) {
            $notes = trim(($notes ? $notes."\n\n" : '').'Rejection: '.$request->input('rejection_reason'));
        }

        $timesheet->update([
            'status' => 'rejected',
            'notes' => $notes,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Timesheet rejected.',
                'timesheet' => $timesheet->fresh(),
            ]);
        }

        return redirect()
            ->route('timesheets.show', $timesheet)
            ->with('success', 'Timesheet rejected.');
    }

    public function bulkCreate(Request $request): View
    {
        $crews = Crew::with(['project', 'foreman'])->get();
        $projects = Project::whereIn('status', ['active', 'awarded', 'bidding'])
            ->orderBy('project_number')
            ->get(['id', 'project_number', 'name']);
        $shifts = Shift::all();
        // 2026-04-29 (Brenda): default Shift = Day Shift unless changed.
        // Match by name (case-insensitive 'day') so seeded variations like
        // "Day Shift" / "Day" / "1st Shift / Day" all resolve.
        $defaultShiftId = optional(
            $shifts->first(fn ($s) => stripos($s->name ?? '', 'day') !== false)
        )->id ?? optional($shifts->first())->id;
        $costCodes = CostCode::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        $costTypes = \App\Models\CostType::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']);
        // 2026-04-28 — legacy-layout bulk entry needs the full employee + craft
        // catalogs because the form lets payroll clerks key in a worker by ID
        // and (optionally) mark the craft per row.
        $employees = Employee::where('status', 'active')
            ->orderBy('employee_number')
            ->get(['id', 'employee_number', 'first_name', 'last_name', 'craft_id']);
        $crafts = Craft::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $crewMembers = collect();
        if ($request->filled('crew_id')) {
            $crew = Crew::find($request->crew_id);
            if ($crew) {
                $crewMembers = $crew->employees;
            }
        }

        return view('timesheets.bulk-create', [
            'crews'          => $crews,
            'projects'       => $projects,
            'shifts'         => $shifts,
            'defaultShiftId' => $defaultShiftId,
            'crewMembers'    => $crewMembers,
            'costCodes'      => $costCodes,
            'costTypes'      => $costTypes,
            'employees'      => $employees,
            'crafts'         => $crafts,
        ]);
    }

    public function bulkStore(Request $request): JsonResponse|RedirectResponse
    {
        // Normalize empty strings to null for nullable columns — HTML forms
        // send "" for unselected <select> and empty inputs, and MySQL rejects
        // ""  on integer FK / numeric columns (SQLSTATE 1366). This was
        // causing "Server Error" when a user hit "Create All Timesheets"
        // without picking a per-row Cost Type or filling Gate Log hours.
        $request->merge([
            'cost_code_id' => $request->filled('cost_code_id') ? $request->cost_code_id : null,
            'cost_type_id' => $request->filled('cost_type_id') ? $request->cost_type_id : null,
        ]);
        $entries = $request->input('entries', []);
        foreach ($entries as $i => $row) {
            foreach (['cost_type_id', 'gate_log_hours', 'per_diem_amount',
                      'hours_worked', 'regular_hours', 'overtime_hours', 'double_time_hours'] as $col) {
                if (array_key_exists($col, $row) && $row[$col] === '') {
                    $entries[$i][$col] = null;
                }
            }
        }
        $request->merge(['entries' => $entries]);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'crew_id' => 'required|exists:crews,id',
            'date' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
            'work_order_number' => 'nullable|string|max:100',
            'cost_code_id' => 'nullable|exists:cost_codes,id',
            'cost_type_id' => 'nullable|exists:cost_types,id',
            'entries' => 'required|array|min:1',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.present' => 'nullable|boolean',
            'entries.*.work_order_number' => 'nullable|string|max:100',
            // Preferred path: single "hours_worked" input per row → calculator splits.
            'entries.*.hours_worked' => 'nullable|numeric|min:0',
            // Manual override path: explicit per-bucket entry.
            'entries.*.regular_hours' => 'nullable|numeric|min:0',
            'entries.*.overtime_hours' => 'nullable|numeric|min:0',
            'entries.*.double_time_hours' => 'nullable|numeric|min:0',
            'entries.*.force_overtime' => 'nullable|boolean',
            'entries.*.gate_log_hours' => 'nullable|numeric|min:0',
            'entries.*.per_diem' => 'nullable|boolean',
            'entries.*.per_diem_amount' => 'nullable|numeric|min:0',
            'entries.*.work_through_lunch' => 'nullable|boolean',
            'entries.*.cost_type_id' => 'nullable|exists:cost_types,id',
        ]);

        $timesheets = [];

        // Default per diem rate for this project (used when the row checkbox
        // is ticked but the user didn't type a specific amount).
        $project = \App\Models\Project::find($validated['project_id']);
        $projectPerDiem = (float) ($project->default_per_diem_rate ?? 0);

        $skipped = 0;
        foreach ($validated['entries'] as $entry) {
            // Skip absent employees — the "Present" checkbox on the bulk form
            // defaults ON, so unchecked rows mean the foreman marked them absent.
            // No timesheet is created for them, keeping reports clean.
            if (empty($entry['present'])) {
                $skipped++;
                continue;
            }
            $employee = Employee::findOrFail($entry['employee_id']);
            $forceOT = !empty($entry['force_overtime']);
            $split = $this->resolveHourSplit(
                $employee,
                $validated['date'],
                $entry,
                $forceOT,
                null
            );
            $reg = $split['regular_hours'];
            $ot  = $split['overtime_hours'];
            $dt  = $split['double_time_hours'];

            $totals = $this->computeLaborTotals($employee, $reg, $ot, $dt, (int) $validated['project_id'], $validated['date']);

            $timesheet = Timesheet::create([
                'employee_id' => $entry['employee_id'],
                'project_id' => $validated['project_id'],
                'cost_code_id' => $validated['cost_code_id'] ?? null,
                // Per-row cost_type_id wins; otherwise fall back to the
                // crew-level cost_type_id picked at the top of the form.
                'cost_type_id' => $entry['cost_type_id'] ?? $validated['cost_type_id'] ?? null,
                'crew_id' => $validated['crew_id'],
                'date' => $validated['date'],
                'shift_id' => $validated['shift_id'],
                // Per-row work order # wins; falls back to the crew-level
                // work order # entered at the top of the form.
                'work_order_number' => $entry['work_order_number']
                    ?? $validated['work_order_number']
                    ?? null,
                'regular_hours' => $reg,
                'overtime_hours' => $ot,
                'double_time_hours' => $dt,
                'force_overtime' => $forceOT,
                'total_hours' => $totals['total_hours'],
                'gate_log_hours' => $entry['gate_log_hours'] ?? null,
                'work_through_lunch' => !empty($entry['work_through_lunch']),
                'regular_rate' => $totals['regular_rate'],
                'overtime_rate' => $totals['overtime_rate'],
                'total_cost' => $totals['total_cost'],
                'billable_rate' => $totals['billable_rate'],
                'billable_amount' => $totals['billable_amount'],
                'rate_type' => $totals['rate_type'],
                'project_billable_rate_id' => $totals['project_billable_rate_id'],
                'status' => 'draft',
            ]);

            $this->syncTimesheetCostAllocation($timesheet->fresh());

            // Override per_diem on the allocation if user supplied one for this row
            if (!empty($entry['per_diem']) || !empty($entry['per_diem_amount'])) {
                $amount = (float) ($entry['per_diem_amount'] ?? $projectPerDiem);
                $timesheet->costAllocations()->update(['per_diem_amount' => $amount]);
            }

            $timesheets[] = $timesheet;
        }

        $msg = count($timesheets) . ' timesheet' . (count($timesheets) === 1 ? '' : 's') . ' created'
            . ($skipped > 0 ? ", {$skipped} absent " . ($skipped === 1 ? 'employee' : 'employees') . ' skipped' : '')
            . '.';

        // If traditional form POST (not AJAX), redirect back with success message
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('timesheets.index')->with('success', $msg);
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'timesheets' => $timesheets,
            'count' => count($timesheets),
            'skipped' => $skipped,
        ], 201);
    }

    /**
     * Find the best matching ProjectBillableRate for a given project, employee, and date.
     * Priority: employee-specific rate > craft-specific rate > null (use standard)
     */
    private function findProjectBillableRate(int $projectId, Employee $employee, string $date): ?ProjectBillableRate
    {
        // First try employee-specific rate for this project
        $rate = ProjectBillableRate::forProject($projectId)
            ->forEmployee($employee->id)
            ->effectiveOn($date)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate) {
            return $rate;
        }

        // Then try craft-specific rate for this project
        if ($employee->craft_id) {
            $rate = ProjectBillableRate::forProject($projectId)
                ->forCraft($employee->craft_id)
                ->whereNull('employee_id')
                ->effectiveOn($date)
                ->orderByDesc('effective_date')
                ->first();

            if ($rate) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Compute timesheet totals.
     *
     * Architecture (per client):
     *   - COST (what we pay, used in budgets/cost reports)
     *     = (wage + burden) × hours, always sourced from the employee:
     *         ST cost = (hourly_rate + st_burden_rate) × regular_hours
     *         OT cost = (overtime_rate + ot_burden_rate) × overtime_hours
     *         DT cost = (hourly_rate × 2 + ot_burden_rate) × dt_hours
     *
     *   - BILLABLE (what we charge the client)
     *     = Project Billable Rate if one exists for this project+employee/craft+date,
     *       otherwise falls back to employee's billable_rate × multipliers.
     *
     * @return array{total_hours: float, total_cost: float, regular_rate: string|float, overtime_rate: string|float, billable_rate: string|float, billable_amount: float, rate_type: string, project_billable_rate_id: int|null}
     */
    private function computeLaborTotals(Employee $employee, float $regularHours, float $overtimeHours, float $doubleTimeHours, int $projectId = 0, string $date = ''): array
    {
        $totalHours = $regularHours + $overtimeHours + $doubleTimeHours;

        // ── COST (employee's rate, or a project-specific override if one exists) ──
        // Per-project pay rates take precedence so an employee can be paid
        // different wages on different jobs.
        $projRate = null;
        if ($projectId) {
            $projRate = \App\Models\EmployeeProjectRate::where('project_id', $projectId)
                ->where('employee_id', $employee->id)
                ->when($date, function ($q) use ($date) {
                    $q->where(function ($qq) use ($date) {
                        $qq->whereNull('effective_date')->orWhere('effective_date', '<=', $date);
                    })->where(function ($qq) use ($date) {
                        $qq->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    });
                })
                ->orderByDesc('effective_date')
                ->first();
        }

        $stWage   = (float) ($projRate->hourly_rate   ?? $employee->hourly_rate);
        $otWage   = (float) ($projRate->overtime_rate ?? $employee->overtime_rate);
        $stBurden = (float) ($projRate->st_burden_rate ?? $employee->st_burden_rate ?? 0);
        $otBurden = (float) ($projRate->ot_burden_rate ?? $employee->ot_burden_rate ?? 0);

        $regularCost = $regularHours * ($stWage + $stBurden);
        $otCost      = $overtimeHours * ($otWage + $otBurden);
        $dtCost      = $doubleTimeHours * (($stWage * 2) + $otBurden);
        $totalCost   = $regularCost + $otCost + $dtCost;

        // ── BILLABLE (project rate if set, else employee billable_rate) ───
        $projectRate = null;
        if ($projectId && $date) {
            $projectRate = $this->findProjectBillableRate($projectId, $employee, $date);
        }

        if ($projectRate) {
            $stRate = (float) $projectRate->straight_time_rate;
            $otRate = (float) $projectRate->overtime_rate;
            $dtRate = (float) $projectRate->double_time_rate;
            $billableAmount = ($regularHours * $stRate)
                + ($overtimeHours * $otRate)
                + ($doubleTimeHours * $dtRate);

            return [
                'total_hours' => $totalHours,
                'total_cost' => $totalCost,
                'regular_rate' => $stWage,
                'overtime_rate' => $otWage,
                'billable_rate' => $stRate,
                'billable_amount' => $billableAmount,
                'rate_type' => 'loaded',
                'project_billable_rate_id' => $projectRate->id,
            ];
        }

        $bRate = (float) ($projRate->billable_rate ?? $employee->billable_rate ?? $employee->hourly_rate);
        $billableAmount = ($regularHours * $bRate)
            + ($overtimeHours * $bRate * 1.5)
            + ($doubleTimeHours * $bRate * 2);

        return [
            'total_hours' => $totalHours,
            'total_cost' => $totalCost,
            'regular_rate' => $employee->hourly_rate,
            'overtime_rate' => $employee->overtime_rate,
            'billable_rate' => $bRate,
            'billable_amount' => $billableAmount,
            'rate_type' => 'standard',
            'project_billable_rate_id' => null,
        ];
    }

    /**
     * For single-entry saves: if the user ticked "per diem" or typed a custom
     * amount, overwrite the auto-filled allocation per-diem field.
     * Bulk has its own inline version of this inside the loop.
     */
    private function applyPerDiemOverride(Timesheet $timesheet, Request $request): void
    {
        $hasPerDiem   = $request->has('per_diem');
        $hasPerDiemAmt = $request->filled('per_diem_amount');

        if (!$hasPerDiem && !$hasPerDiemAmt) {
            return; // User left both blank — leave the default-filled value alone.
        }

        $projectPerDiem = (float) ($timesheet->project?->default_per_diem_rate ?? 0);
        $amount = $hasPerDiemAmt
            ? (float) $request->input('per_diem_amount')
            : ($request->boolean('per_diem') ? $projectPerDiem : 0);

        $timesheet->costAllocations()->update(['per_diem_amount' => $amount]);
    }

    /**
     * Turn a submitted row (either $validated from single-entry or an
     * `entries.*` row from bulk) into Reg/OT/DT numbers using the
     * weekly-40 calculator.
     *
     * Two inputs drive the decision:
     *   1. `hours_worked` present → pass to calculator (preferred path).
     *   2. Otherwise fall through to whatever explicit buckets the user
     *      typed (manual override — lets the client split hours however
     *      they want for edge cases).
     *
     * @param  array<string, mixed>  $source
     * @return array{regular_hours: float, overtime_hours: float, double_time_hours: float}
     */
    private function resolveHourSplit(
        Employee $employee,
        string $date,
        array $source,
        bool $forceOvertime,
        ?int $excludeTimesheetId
    ): array {
        $hasWorked = array_key_exists('hours_worked', $source)
            && $source['hours_worked'] !== null
            && $source['hours_worked'] !== '';

        if ($hasWorked) {
            $split = $this->overtimeCalculator->splitWeekly(
                $employee,
                $date,
                (float) $source['hours_worked'],
                $forceOvertime,
                $excludeTimesheetId
            );
            return [
                'regular_hours'     => $split['regular'],
                'overtime_hours'    => $split['overtime'],
                'double_time_hours' => $split['double'],
            ];
        }

        // Manual override — user typed explicit buckets, trust them.
        // If "force overtime" is ticked, roll Reg into OT before saving so
        // the checkbox still means something in manual mode.
        $reg = (float) ($source['regular_hours']     ?? 0);
        $ot  = (float) ($source['overtime_hours']    ?? 0);
        $dt  = (float) ($source['double_time_hours'] ?? 0);

        if ($forceOvertime && $reg > 0) {
            $ot += $reg;
            $reg = 0;
        }

        return [
            'regular_hours'     => $reg,
            'overtime_hours'    => $ot,
            'double_time_hours' => $dt,
        ];
    }

    /**
     * AJAX endpoint used by the timesheet forms to show a live preview of
     * how "hours worked" will split against the weekly 40-hr threshold.
     *
     * GET /timesheets/week-hours?employee_id=&date=&hours_worked=&force_overtime=&exclude_id=
     */
    public function weekHours(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'date'            => 'required|date',
            'hours_worked'    => 'nullable|numeric|min:0',
            'force_overtime'  => 'nullable|boolean',
            'exclude_id'      => 'nullable|integer',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $force    = (bool) ($data['force_overtime'] ?? false);
        $hours    = (float) ($data['hours_worked'] ?? 0);
        $exclude  = $data['exclude_id'] ?? null;

        $weekSoFar = $this->overtimeCalculator->weekHoursSoFar($employee, $data['date'], $exclude);
        [$weekStart, $weekEnd] = $this->overtimeCalculator->weekRange($data['date']);

        $split = $this->overtimeCalculator->splitWeekly(
            $employee,
            $data['date'],
            $hours,
            $force,
            $exclude
        );

        return response()->json([
            'week_start'        => $weekStart,
            'week_end'          => $weekEnd,
            'week_hours_before' => round($weekSoFar, 2),
            'regular'           => $split['regular'],
            'overtime'          => $split['overtime'],
            'double'            => $split['double'],
            'threshold'         => OvertimeCalculator::WEEKLY_OT_THRESHOLD,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    //  Snap-a-Timesheet (AI OCR) — Brenda's killer feature 04.29.2026
    //  Foreman uploads a photo of a paper timesheet → Claude vision
    //  extracts every row → office reviews + bulk-creates timesheets.
    //  Two endpoints: scanPhoto() does the AI extraction, scanCommit()
    //  takes the (possibly user-edited) entries and inserts the rows.
    // ─────────────────────────────────────────────────────────────────

    /**
     * Accept an uploaded image, send it to Claude for OCR, return the
     * structured entries (with employee/project pre-matched against the
     * live catalog) plus a scan_log_id the client uses on commit.
     */
    public function scanPhoto(Request $request, TimesheetOcrService $ocr): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|max:10240',  // 10 MB cap
        ]);

        $file = $request->file('photo');
        $bytes = file_get_contents($file->getRealPath());
        $b64 = base64_encode($bytes);
        $mime = $file->getMimeType() ?: 'image/jpeg';

        // Persist the image first so the audit log always has it,
        // even if the AI call later fails. Stored under a private
        // disk so a leaked URL can't expose payroll photos.
        $stored = $file->store('timesheet-scans', 'local');

        $log = TimesheetScanLog::create([
            'user_id'           => auth()->id(),
            'image_path'        => $stored,
            'original_filename' => $file->getClientOriginalName(),
            'file_size_bytes'   => strlen($bytes),
            'status'            => 'extracted',
        ]);

        try {
            $result = $ocr->extractFromImage($b64, $mime);
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        $log->update([
            'extracted_payload' => [
                'entries' => $result['entries'],
                'summary' => $result['summary'],
                'common'  => $result['common'],
            ],
            'raw_response'      => $result['raw'],
        ]);

        return response()->json([
            'success'      => true,
            'scan_log_id'  => $log->id,
            'summary'      => $result['summary'],
            'common'       => $result['common'],
            'entries'      => $result['entries'],
        ]);
    }

    /**
     * Commit a previously-scanned set of entries as real timesheets.
     * The client sends back the entries (likely with user corrections)
     * and we run them through the same business logic Timesheet::create
     * uses (rate computation, cost allocation sync, per-diem default).
     */
    public function scanCommit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scan_log_id'           => 'required|exists:timesheet_scan_logs,id',
            'entries'               => 'required|array|min:1',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.project_id'  => 'required|exists:projects,id',
            'entries.*.date'        => 'required|date',
            'entries.*.shift_id'        => 'nullable|exists:shifts,id',
            'entries.*.cost_code_id'    => 'nullable|exists:cost_codes,id',
            'entries.*.cost_type_id'    => 'nullable|exists:cost_types,id',
            'entries.*.regular_hours'   => 'nullable|numeric|min:0',
            'entries.*.overtime_hours'  => 'nullable|numeric|min:0',
            'entries.*.double_time_hours' => 'nullable|numeric|min:0',
            'entries.*.earnings_category' => 'nullable|in:HE,HO,VA',
            'entries.*.notes'           => 'nullable|string|max:500',
        ]);

        $createdIds = [];
        \DB::transaction(function () use ($data, &$createdIds) {
            foreach ($data['entries'] as $row) {
                $employee = Employee::findOrFail($row['employee_id']);
                $reg = (float) ($row['regular_hours']     ?? 0);
                $ot  = (float) ($row['overtime_hours']    ?? 0);
                $dt  = (float) ($row['double_time_hours'] ?? 0);

                // Skip empty rows the user un-selected.
                if ($reg + $ot + $dt <= 0) continue;

                $totals = $this->computeLaborTotals(
                    $employee, $reg, $ot, $dt, (int) $row['project_id'], $row['date']
                );

                $ts = Timesheet::create([
                    'employee_id'      => $row['employee_id'],
                    'project_id'       => $row['project_id'],
                    'cost_code_id'     => $row['cost_code_id']   ?? null,
                    'cost_type_id'     => $row['cost_type_id']   ?? null,
                    'shift_id'         => $row['shift_id']       ?? null,
                    'date'             => $row['date'],
                    'regular_hours'    => $reg,
                    'overtime_hours'   => $ot,
                    'double_time_hours'=> $dt,
                    // OCR'd splits are explicit — don't let the weekly-40 rule
                    // re-bucket them after the user has confirmed.
                    'force_overtime'   => true,
                    'total_hours'      => $totals['total_hours'],
                    'regular_rate'     => $totals['regular_rate'],
                    'overtime_rate'    => $totals['overtime_rate'],
                    'total_cost'       => $totals['total_cost'],
                    'billable_rate'    => $totals['billable_rate'],
                    'billable_amount'  => $totals['billable_amount'],
                    'is_billable'      => true,
                    'rate_type'        => $totals['rate_type'],
                    'earnings_category' => $row['earnings_category'] ?? 'HE',
                    'project_billable_rate_id' => $totals['project_billable_rate_id'],
                    'status'           => 'submitted',
                    'notes'            => trim(($row['notes'] ?? '') . ' [via Snap-a-Timesheet OCR]'),
                ]);
                $this->syncTimesheetCostAllocation($ts->fresh());
                $createdIds[] = $ts->id;
            }

            TimesheetScanLog::where('id', $data['scan_log_id'])->update([
                'status'                => 'confirmed',
                'created_timesheet_ids' => $createdIds,
            ]);
        });

        return response()->json([
            'success'  => true,
            'message'  => count($createdIds) . ' timesheet(s) created from scan.',
            'created'  => $createdIds,
        ]);
    }
}
