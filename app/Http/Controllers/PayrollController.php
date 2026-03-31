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
}
