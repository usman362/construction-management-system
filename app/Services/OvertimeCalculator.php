<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;

/**
 * Splits a single "hours worked" figure into Regular / Overtime / Double-time
 * using the client's payroll rule:
 *
 *   - OT threshold: 40 hours per work-week (Monday → Sunday)
 *   - "Special" hours: if `force_overtime` is set, the ENTIRE entry lands in
 *     the OT bucket regardless of how many hours have been worked that week
 *     (covers holiday premiums, client-approved OT, etc.)
 *   - Double-time: NOT triggered automatically by the weekly rule. DT stays
 *     available as a manual override for contract-specific cases.
 *
 * Week math uses Carbon's ISO week (Mon=start, Sun=end).
 *
 * The calculator reads existing hours from the `timesheets` table, which
 * means every entry already stored for that employee in the same week
 * feeds into the OT decision — whether those were captured via single
 * entry, bulk entry, or an import.
 */
class OvertimeCalculator
{
    public const WEEKLY_OT_THRESHOLD = 40.0;

    /**
     * Split a worked-hours figure into [regular, overtime, double_time].
     *
     * @param  Employee  $employee
     * @param  Carbon|string  $date          The day these hours were worked (any format Carbon parses).
     * @param  float  $hoursWorked           Total hours the employee worked that day (ST + OT combined).
     * @param  bool  $forceOvertime          If true, entire $hoursWorked lands in OT.
     * @param  int|null  $excludeTimesheetId  Pass the current timesheet ID when editing so it
     *                                        isn't double-counted in the week-so-far total.
     * @return array{regular: float, overtime: float, double: float, week_hours_before: float}
     */
    public function splitWeekly(
        Employee $employee,
        Carbon|string $date,
        float $hoursWorked,
        bool $forceOvertime = false,
        ?int $excludeTimesheetId = null
    ): array {
        $hoursWorked = max(0.0, (float) $hoursWorked);

        if ($forceOvertime) {
            return [
                'regular'           => 0.0,
                'overtime'          => $hoursWorked,
                'double'            => 0.0,
                'week_hours_before' => $this->weekHoursSoFar($employee, $date, $excludeTimesheetId),
            ];
        }

        $weekSoFar = $this->weekHoursSoFar($employee, $date, $excludeTimesheetId);

        // Regular capacity = how many hours we can still log before crossing 40.
        $regularCapacity = max(0.0, self::WEEKLY_OT_THRESHOLD - $weekSoFar);

        $regular  = min($hoursWorked, $regularCapacity);
        $overtime = max(0.0, $hoursWorked - $regular);

        return [
            'regular'           => round($regular, 2),
            'overtime'          => round($overtime, 2),
            'double'            => 0.0,
            'week_hours_before' => round($weekSoFar, 2),
        ];
    }

    /**
     * Total hours an employee has already logged in the Monday-Sunday week
     * containing $date, optionally excluding one timesheet (used when
     * re-splitting an edit so the old row doesn't count twice).
     */
    public function weekHoursSoFar(
        Employee $employee,
        Carbon|string $date,
        ?int $excludeTimesheetId = null
    ): float {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $weekStart = $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd   = $d->copy()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString();

        $query = Timesheet::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$weekStart, $weekEnd]);

        if ($excludeTimesheetId) {
            $query->where('id', '!=', $excludeTimesheetId);
        }

        // Sum logged regular + OT + DT so the week total reflects everything
        // already on the employee's card.
        $row = $query
            ->selectRaw('COALESCE(SUM(regular_hours),0) + COALESCE(SUM(overtime_hours),0) + COALESCE(SUM(double_time_hours),0) AS total')
            ->first();

        return (float) ($row->total ?? 0);
    }

    /**
     * Helper — returns [Monday date, Sunday date] for the week containing $date.
     *
     * @return array{0: string, 1: string}
     */
    public function weekRange(Carbon|string $date): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $start = $d->copy()->startOfWeek(Carbon::MONDAY);

        return [$start->toDateString(), $start->copy()->addDays(6)->toDateString()];
    }
}
