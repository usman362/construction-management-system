<?php

namespace App\Services;

use App\Models\Timesheet;
use App\Models\TimeClockEntry;

/**
 * 2026-05-22 (Brenda): 16-hour daily cap shared between timesheet entry
 * (where the cap rejects with an error) and time-clock punches (where
 * the cap CLAMPS the punch's saved hours instead — clocking out is a
 * physical event, you can't refuse to save it). One service so the math
 * stays consistent across both surfaces.
 *
 * Cap configurable via TIMESHEET_DAILY_CAP_HOURS env (0 disables).
 */
class DailyHoursCap
{
    public static function capHours(): float
    {
        return (float) env('TIMESHEET_DAILY_CAP_HOURS', 16);
    }

    /**
     * Combined day total = approved/submitted/draft timesheets +
     * closed/converted time-clock punches for this employee on this
     * date, excluding optional self-references.
     */
    public static function existingDayTotal(
        int $employeeId,
        string $date,
        ?int $excludeTimesheetId = null,
        ?int $excludePunchId = null
    ): float {
        $tsSum = Timesheet::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->when($excludeTimesheetId, fn ($q) => $q->where('id', '!=', $excludeTimesheetId))
            ->sum('total_hours');

        $punchSum = TimeClockEntry::query()
            ->where('employee_id', $employeeId)
            ->whereDate('clock_in_at', $date)
            ->whereIn('status', ['closed', 'converted'])
            ->when($excludePunchId, fn ($q) => $q->where('id', '!=', $excludePunchId))
            ->sum('hours');

        // Converted punches will already be reflected in their linked
        // Timesheet rows — subtract them out to avoid double-counting.
        $convertedDouble = TimeClockEntry::query()
            ->where('employee_id', $employeeId)
            ->whereDate('clock_in_at', $date)
            ->where('status', 'converted')
            ->when($excludePunchId, fn ($q) => $q->where('id', '!=', $excludePunchId))
            ->sum('hours');

        return (float) $tsSum + (float) $punchSum - (float) $convertedDouble;
    }

    /**
     * Used by timesheet store/update — returns a friendly error string
     * if the requested hours would push the day over the cap, else null.
     */
    public static function checkTimesheet(
        int $employeeId,
        string $date,
        float $newHours,
        ?int $excludeTimesheetId = null
    ): ?string {
        $cap = self::capHours();
        if ($cap <= 0) return null;

        $existing = self::existingDayTotal($employeeId, $date, $excludeTimesheetId);
        $combined = $existing + $newHours;
        if ($combined > $cap + 0.001) {
            $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
            return "Daily cap of {$cap} hours exceeded for {$date}. "
                . "Already on file: {$fmt($existing)} hrs; this entry would add {$fmt($newHours)} hrs (total {$fmt($combined)}). "
                . "Adjust the hours, or edit one of the existing rows for this day.";
        }
        return null;
    }

    /**
     * Used at clock-out time — CLAMPS the punch's recorded hours to
     * whatever fits under the cap. Returns:
     *   [
     *     'hours'       => float,   // what to actually save
     *     'raw'         => float,   // what was requested (input)
     *     'was_clamped' => bool,
     *     'note'        => ?string, // human-readable note if clamped
     *   ]
     */
    public static function clampPunch(
        int $employeeId,
        string $date,
        float $rawHours,
        ?int $excludePunchId = null
    ): array {
        $cap = self::capHours();
        if ($cap <= 0) {
            return ['hours' => $rawHours, 'raw' => $rawHours, 'was_clamped' => false, 'note' => null];
        }

        $existing = self::existingDayTotal($employeeId, $date, null, $excludePunchId);
        $remaining = max(0.0, $cap - $existing);
        if ($rawHours <= $remaining + 0.001) {
            return ['hours' => $rawHours, 'raw' => $rawHours, 'was_clamped' => false, 'note' => null];
        }

        $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
        $clamped = round($remaining, 2);
        $note = sprintf(
            'Capped at %s hrs daily limit. Punch ran %s hrs; %s hrs already on file for this day; saved %s hrs.',
            $fmt($cap), $fmt($rawHours), $fmt($existing), $fmt($clamped)
        );

        return ['hours' => $clamped, 'raw' => $rawHours, 'was_clamped' => true, 'note' => $note];
    }
}
