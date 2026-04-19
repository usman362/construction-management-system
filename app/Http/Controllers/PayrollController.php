<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function create()
    {
        return redirect()->route('payroll.index');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }
        return view('payroll.index');
    }

    private function dataTable(Request $request): JsonResponse
    {
        $query = PayrollPeriod::query();
        $totalRecords = PayrollPeriod::count();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }
        $filteredRecords = $query->count();

        // Order
        $columns = ['id', 'name', 'start_date', 'end_date', 'status'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');
        $query->orderBy($orderCol, $orderDir);

        // Paginate
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data->map(function ($period) {
                $name = $period->name ?: ('Payroll Period '.$period->start_date?->format('M j, Y').' – '.$period->end_date?->format('M j, Y'));

                return [
                    'id' => $period->id,
                    'name' => $name,
                    'start_date' => $period->start_date?->format('M j, Y'),
                    'end_date' => $period->end_date?->format('M j, Y'),
                    'status' => $period->status,
                    'actions' => $period->id,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'name' => 'nullable|string|max:255',
        ]);

        $name = filled($validated['name'] ?? null)
            ? $validated['name']
            : ('Payroll Period '.Carbon::parse($validated['start_date'])->format('M j, Y').' – '.Carbon::parse($validated['end_date'])->format('M j, Y'));

        PayrollPeriod::create([
            ...$validated,
            'name' => $name,
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Payroll period created successfully']);
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        $payrollPeriod->load(['entries.employee', 'entries.project', 'entries.costCode']);

        return view('payroll.show', [
            'payrollPeriod' => $payrollPeriod,
            'entries' => $payrollPeriod->entries,
        ]);
    }

    public function edit(PayrollPeriod $payrollPeriod): JsonResponse
    {
        return response()->json([
            'id' => $payrollPeriod->id,
            'name' => $payrollPeriod->name ?? '',
            'start_date' => $payrollPeriod->start_date?->format('Y-m-d'),
            'end_date' => $payrollPeriod->end_date?->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, PayrollPeriod $payrollPeriod): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'name' => 'nullable|string|max:255',
        ]);

        $payrollPeriod->update($validated);
        return response()->json(['message' => 'Payroll period updated successfully']);
    }

    public function destroy(PayrollPeriod $payrollPeriod): JsonResponse
    {
        $payrollPeriod->delete();
        return response()->json(['message' => 'Payroll period deleted successfully']);
    }

    public function generate(Request $request, PayrollPeriod $payrollPeriod): JsonResponse|RedirectResponse
    {
        $timesheetsQuery = Timesheet::query()
            ->whereIn('status', ['submitted', 'approved'])
            ->whereBetween('date', [$payrollPeriod->start_date, $payrollPeriod->end_date])
            ->with('employee');

        $timesheetRows = (clone $timesheetsQuery)->with(['costAllocations'])->get();
        $timesheets = $timesheetRows->groupBy('employee_id');

        foreach ($timesheets as $employeeId => $employeeTimesheets) {
            $first = $employeeTimesheets->first();
            $costCodeId = $this->resolvePrimaryCostCodeFromTimesheets($employeeTimesheets);
            $regularHours = (float) $employeeTimesheets->sum('regular_hours');
            $overtimeHours = (float) $employeeTimesheets->sum('overtime_hours');
            $doubleTimeHours = (float) $employeeTimesheets->sum('double_time_hours');

            $regularPay = (float) $employeeTimesheets->sum(fn (Timesheet $t) => (float) $t->regular_hours * (float) $t->regular_rate);
            $overtimePay = (float) $employeeTimesheets->sum(fn (Timesheet $t) => (float) $t->overtime_hours * (float) $t->overtime_rate);
            $doubleTimePay = (float) $employeeTimesheets->sum(fn (Timesheet $t) => (float) $t->double_time_hours * (float) $t->regular_rate * 2);
            $totalPay = (float) $employeeTimesheets->sum('total_cost');
            $billableAmount = (float) $employeeTimesheets->sum('billable_amount');

            PayrollEntry::updateOrCreate(
                [
                    'payroll_period_id' => $payrollPeriod->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'project_id' => $first->project_id,
                    'cost_code_id' => $costCodeId,
                    'regular_hours' => $regularHours,
                    'overtime_hours' => $overtimeHours,
                    'double_time_hours' => $doubleTimeHours,
                    'regular_pay' => round($regularPay, 2),
                    'overtime_pay' => round($overtimePay, 2),
                    'double_time_pay' => round($doubleTimePay, 2),
                    'total_pay' => round($totalPay, 2),
                    'billable_amount' => round($billableAmount, 2),
                    'per_diem' => 0,
                ]
            );
        }

        $employeeCount = $timesheets->count();
        $rowCount = $timesheetRows->count();
        $message = $employeeCount > 0
            ? "Payroll generated: {$employeeCount} employee(s), {$rowCount} timesheet row(s) in period."
            : 'No submitted or approved timesheets in this period — no payroll entries were created. Approve or submit timesheets for dates within the payroll period, then try again.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'employees' => $employeeCount, 'timesheet_rows' => $rowCount])
            : redirect()->route('payroll.show', $payrollPeriod)->with('success', $message);
    }

    /**
     * Use the cost code with the most hours (from allocations or timesheet-level cost code).
     */
    private function resolvePrimaryCostCodeFromTimesheets(Collection $employeeTimesheets): ?int
    {
        $weights = [];
        foreach ($employeeTimesheets as $t) {
            $allocRows = $t->relationLoaded('costAllocations')
                ? $t->costAllocations
                : $t->costAllocations()->get();
            if ($allocRows->isNotEmpty()) {
                foreach ($allocRows as $a) {
                    $cid = $a->cost_code_id;
                    $weights[$cid] = ($weights[$cid] ?? 0) + (float) $a->hours;
                }
            } elseif ($t->cost_code_id) {
                $cid = $t->cost_code_id;
                $weights[$cid] = ($weights[$cid] ?? 0) + (float) $t->total_hours;
            }
        }

        if ($weights === []) {
            return null;
        }

        arsort($weights);

        return (int) array_key_first($weights);
    }

    public function process(Request $request, PayrollPeriod $payrollPeriod): JsonResponse|RedirectResponse
    {
        $payrollPeriod->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $message = 'Payroll period processed successfully';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('payroll.show', $payrollPeriod)->with('success', $message);
    }

    /**
     * Export a payroll period to a true .xlsx workbook with all legacy employee
     * info + hours + per diem. Two sheets:
     *  - "Detail"    — one row per payroll entry (per project/phase-code split)
     *  - "Employee Summary" — rolled up per employee with total hours, per-diem
     *                         days, and comma-separated job numbers.
     * Columns match what the client typically uploads into payroll software.
     */
    public function export(PayrollPeriod $payrollPeriod): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $entries = PayrollEntry::with(['employee.craft', 'project', 'costCode'])
            ->where('payroll_period_id', $payrollPeriod->id)
            ->orderBy('employee_id')
            ->get();

        // Group entries by employee for summary sheet + per-row enrichment.
        $perDiemDaysByEmployee = $entries->groupBy('employee_id')->map(function ($rows) {
            return $rows->where('per_diem', '>', 0)->count();
        });
        $projectsByEmployee = $entries->groupBy('employee_id')->map(function ($rows) {
            return $rows->pluck('project.project_number')->filter()->unique()->implode(', ');
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->populatePayrollDetailSheet($spreadsheet, $entries, $perDiemDaysByEmployee);
        $this->populatePayrollSummarySheet($spreadsheet, $entries, $perDiemDaysByEmployee, $projectsByEmployee);

        // Default to Detail sheet when the file is opened
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'payroll_' . str_replace(' ', '_', $payrollPeriod->name ?? ('period_' . $payrollPeriod->id)) . '.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'payroll_') . '.xlsx';
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build the "Detail" sheet — one row per PayrollEntry. Preserves the
     * line-level breakdown needed by accounting systems that want per-project
     * labor splits, not just an employee-level roll-up.
     */
    private function populatePayrollDetailSheet(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,
        \Illuminate\Support\Collection $entries,
        \Illuminate\Support\Collection $perDiemDaysByEmployee,
    ): void {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $header = [
            'Employee Number', 'Legacy ID', 'First Name', 'Middle Name', 'Last Name',
            'Craft', 'Classification', 'Department', 'Union',
            'Pay Cycle', 'Pay Type',
            'Hourly Rate', 'Overtime Rate', 'ST Burden', 'OT Burden',
            'Work Comp Code', 'SUTA State', 'State Tax', 'City Tax',
            'Hire Date',
            'Project #', 'Phase Code',
            'Regular Hours', 'OT Hours', 'DT Hours',
            'Regular Pay', 'OT Pay', 'DT Pay', 'Total Pay',
            'Per Diem Amount', 'Per Diem Days', 'Billable Amount',
        ];
        $sheet->fromArray($header, null, 'A1');

        $row = 2;
        foreach ($entries as $e) {
            $emp = $e->employee;
            if (!$emp) continue;

            $sheet->fromArray([
                [
                    $emp->employee_number,
                    $emp->legacy_employee_id,
                    $emp->first_name,
                    $emp->middle_name,
                    $emp->last_name,
                    $emp->craft?->name,
                    $emp->classification,
                    $emp->department,
                    $emp->union,
                    $emp->pay_cycle,
                    $emp->pay_type,
                    (float) $emp->hourly_rate,
                    (float) $emp->overtime_rate,
                    (float) $emp->st_burden_rate,
                    (float) $emp->ot_burden_rate,
                    $emp->work_comp_code,
                    $emp->suta_state,
                    $emp->state_tax,
                    $emp->city_tax,
                    optional($emp->hire_date)->format('Y-m-d'),
                    $e->project?->project_number,
                    $e->costCode?->code,
                    (float) $e->regular_hours,
                    (float) $e->overtime_hours,
                    (float) $e->double_time_hours,
                    (float) $e->regular_pay,
                    (float) $e->overtime_pay,
                    (float) $e->double_time_pay,
                    (float) $e->total_pay,
                    (float) $e->per_diem,
                    (int) ($perDiemDaysByEmployee[$emp->id] ?? 0),
                    (float) $e->billable_amount,
                ],
            ], null, 'A' . $row);
            $row++;
        }

        $this->applyPayrollSheetFormatting($sheet, $header, $row - 1, currencyColumns: ['L','M','N','O','Z','AA','AB','AC','AD','AF']);
    }

    /**
     * Build the "Employee Summary" sheet — one row per employee with totals
     * across the entire period, plus per-diem day count and a comma-separated
     * list of job numbers (client specifically asked for per-diem days + jobs).
     */
    private function populatePayrollSummarySheet(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,
        \Illuminate\Support\Collection $entries,
        \Illuminate\Support\Collection $perDiemDaysByEmployee,
        \Illuminate\Support\Collection $projectsByEmployee,
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Employee Summary');

        $header = [
            'Employee Number', 'Legacy ID', 'Name',
            'Craft', 'Department', 'Pay Type',
            'Reg Hrs', 'OT Hrs', 'DT Hrs', 'Total Hrs',
            'Reg Pay', 'OT Pay', 'DT Pay', 'Total Pay',
            'Per Diem $', 'Per Diem Days',
            'Jobs Worked (Project #s)',
            'Billable $',
        ];
        $sheet->fromArray($header, null, 'A1');

        $grouped = $entries->groupBy('employee_id');
        $row = 2;
        foreach ($grouped as $empId => $rows) {
            $emp = $rows->first()->employee;
            if (!$emp) continue;

            $sheet->fromArray([[
                $emp->employee_number,
                $emp->legacy_employee_id,
                trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                $emp->craft?->name,
                $emp->department,
                $emp->pay_type,
                (float) $rows->sum('regular_hours'),
                (float) $rows->sum('overtime_hours'),
                (float) $rows->sum('double_time_hours'),
                (float) $rows->sum(fn($r) => (float)$r->regular_hours + (float)$r->overtime_hours + (float)$r->double_time_hours),
                (float) $rows->sum('regular_pay'),
                (float) $rows->sum('overtime_pay'),
                (float) $rows->sum('double_time_pay'),
                (float) $rows->sum('total_pay'),
                (float) $rows->sum('per_diem'),
                (int) ($perDiemDaysByEmployee[$empId] ?? 0),
                $projectsByEmployee[$empId] ?? '',
                (float) $rows->sum('billable_amount'),
            ]], null, 'A' . $row);
            $row++;
        }

        $this->applyPayrollSheetFormatting($sheet, $header, $row - 1, currencyColumns: ['K','L','M','N','O','R']);
    }

    /**
     * Shared formatting — bold header row, frozen top, auto-width columns,
     * currency format on money columns. Pulled out so both sheets stay consistent.
     */
    private function applyPayrollSheetFormatting(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $header,
        int $lastRow,
        array $currencyColumns = [],
    ): void {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($header));

        // Header styling
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1F2937');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Freeze header row so scrolling long lists stays readable
        $sheet->freezePane('A2');

        // Auto-fit columns based on content
        for ($i = 1; $i <= count($header); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Currency formatting for money columns
        if ($lastRow >= 2) {
            foreach ($currencyColumns as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                    ->getNumberFormat()->setFormatCode('$#,##0.00;[Red]($#,##0.00);-');
            }
        }

        // Add thin borders to the data area for a tidy look
        if ($lastRow >= 1) {
            $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setRGB('CCCCCC');
        }
    }
}
