<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBillableRate extends Model
{
    protected $fillable = [
        'project_id',
        'craft_id',
        'employee_id',
        'base_hourly_rate',
        'base_ot_hourly_rate',
        // Straight-time markup rates
        'payroll_tax_rate',
        'burden_rate',
        'insurance_rate',
        'job_expenses_rate',
        'consumables_rate',
        'overhead_rate',
        'profit_rate',
        // Overtime markup rates (added per client's rate sheet)
        'payroll_tax_ot_rate',
        'burden_ot_rate',
        'insurance_ot_rate',
        'job_expenses_ot_rate',
        'consumables_ot_rate',
        'overhead_ot_rate',
        'profit_ot_rate',
        // Calculated loaded rates
        'straight_time_rate',
        'overtime_rate',
        'double_time_rate',
        'effective_date',
        'notes',
    ];

    protected $casts = [
        'base_hourly_rate' => 'decimal:2',
        'base_ot_hourly_rate' => 'decimal:4',
        'payroll_tax_rate' => 'decimal:4',
        'burden_rate' => 'decimal:4',
        'insurance_rate' => 'decimal:4',
        'job_expenses_rate' => 'decimal:4',
        'consumables_rate' => 'decimal:4',
        'overhead_rate' => 'decimal:4',
        'profit_rate' => 'decimal:4',
        'payroll_tax_ot_rate' => 'decimal:4',
        'burden_ot_rate' => 'decimal:4',
        'insurance_ot_rate' => 'decimal:4',
        'job_expenses_ot_rate' => 'decimal:4',
        'consumables_ot_rate' => 'decimal:4',
        'overhead_ot_rate' => 'decimal:4',
        'profit_ot_rate' => 'decimal:4',
        'straight_time_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'double_time_rate' => 'decimal:2',
        'effective_date' => 'date',
    ];

    /**
     * Relationship to Project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relationship to Craft (optional - rate may apply to entire craft)
     */
    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class);
    }

    /**
     * Relationship to Employee (optional - rate may apply to specific employee)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to Timesheets that use this rate
     */
    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    /**
     * Calculate the total loaded rate from base rate and all markup percentages
     *
     * This method computes the composite rate by applying all markups as percentages
     * of the base hourly rate, then applies overtime multipliers (1.5x for OT, 2x for DT).
     *
     * @return array<string, float> Array with 'straight_time', 'overtime', and 'double_time' keys
     */
    public function calculateLoadedRate(): array
    {
        $base = (float) $this->base_hourly_rate;

        // ST markup %: sum of straight-time markup rates
        $stMarkup = (float) $this->payroll_tax_rate
            + (float) $this->burden_rate
            + (float) $this->insurance_rate
            + (float) $this->job_expenses_rate
            + (float) $this->consumables_rate
            + (float) $this->overhead_rate
            + (float) $this->profit_rate;

        // OT markup %: sum of OT-specific markups.
        $otMarkup = (float) $this->payroll_tax_ot_rate
            + (float) $this->burden_ot_rate
            + (float) $this->insurance_ot_rate
            + (float) $this->job_expenses_ot_rate
            + (float) $this->consumables_ot_rate
            + (float) $this->overhead_ot_rate
            + (float) $this->profit_ot_rate;

        // OT base rate: use explicit `base_ot_hourly_rate` when client has set it
        // (union/prevailing wage schedules where OT isn't 1.5× ST), otherwise
        // default to ST base × 1.5.
        $hasExplicitOtBase = $this->base_ot_hourly_rate !== null && (float) $this->base_ot_hourly_rate > 0;
        $otBase = $hasExplicitOtBase ? (float) $this->base_ot_hourly_rate : $base * 1.5;

        // OT / DT billable rules (per client — OT must NEVER inherit ST burdens):
        //   1. OT burdens entered   → OT = OT base × (1 + OT markup)
        //   2. Only Base OT entered → OT = Base OT raw (no markup applied)
        //   3. Neither entered      → OT/DT = 0 (unusable until user provides data)
        $straightTimeRate = $base * (1 + $stMarkup);
        if ($otMarkup > 0) {
            $overtimeRate   = $otBase * (1 + $otMarkup);
            $doubleTimeRate = ($base * 2.0) * (1 + $otMarkup);
        } elseif ($hasExplicitOtBase) {
            $overtimeRate   = $otBase;        // raw Base OT, no burdens
            $doubleTimeRate = $base * 2.0;    // raw DT base, no burdens
        } else {
            $overtimeRate   = 0.0;
            $doubleTimeRate = 0.0;
        }

        return [
            'straight_time' => round($straightTimeRate, 2),
            'overtime' => round($overtimeRate, 2),
            'double_time' => round($doubleTimeRate, 2),
        ];
    }

    /**
     * Get the total markup percentage as an attribute
     *
     * Returns the sum of all markup rates (excluding base) as a percentage.
     *
     * @return float Total markup percentage (e.g., 0.5623 for 56.23%)
     */
    public function getMarkupPercentageAttribute(): float
    {
        return (float) $this->payroll_tax_rate
            + (float) $this->burden_rate
            + (float) $this->insurance_rate
            + (float) $this->job_expenses_rate
            + (float) $this->consumables_rate
            + (float) $this->overhead_rate
            + (float) $this->profit_rate;
    }

    /**
     * Scope: Filter rates for a specific project
     */
    public function scopeForProject(Builder $query, int|string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope: Filter rates effective on or before a specific date
     *
     * Returns the most recent rate that is effective on or before the given date.
     */
    public function scopeEffectiveOn(Builder $query, \DateTime|string $date): Builder
    {
        $dateString = $date instanceof \DateTime ? $date->format('Y-m-d') : $date;

        return $query->where('effective_date', '<=', $dateString);
    }

    /**
     * Scope: Get the current/latest effective rates (no future-dated rates)
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('effective_date', '<=', now()->toDateString());
    }

    /**
     * Scope: Filter by craft
     */
    public function scopeForCraft(Builder $query, int|string $craftId): Builder
    {
        return $query->where('craft_id', $craftId);
    }

    /**
     * Scope: Filter by employee
     */
    public function scopeForEmployee(Builder $query, int|string $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Boot the model to auto-calculate loaded rates when saving
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Auto-calculate loaded rates if base rate is set and rates aren't already calculated
            if ($model->base_hourly_rate && (!$model->straight_time_rate || $model->isDirty([
                'base_hourly_rate', 'base_ot_hourly_rate',
                'payroll_tax_rate', 'payroll_tax_ot_rate',
                'burden_rate',      'burden_ot_rate',
                'insurance_rate',   'insurance_ot_rate',
                'job_expenses_rate','job_expenses_ot_rate',
                'consumables_rate', 'consumables_ot_rate',
                'overhead_rate',    'overhead_ot_rate',
                'profit_rate',      'profit_ot_rate',
            ]))) {
                $rates = $model->calculateLoadedRate();
                $model->straight_time_rate = $rates['straight_time'];
                $model->overtime_rate = $rates['overtime'];
                $model->double_time_rate = $rates['double_time'];
            }
        });
    }
}
