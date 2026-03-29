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
        'payroll_tax_rate',
        'burden_rate',
        'insurance_rate',
        'job_expenses_rate',
        'consumables_rate',
        'overhead_rate',
        'profit_rate',
        'straight_time_rate',
        'overtime_rate',
        'double_time_rate',
        'effective_date',
        'notes',
    ];

    protected $casts = [
        'base_hourly_rate' => 'decimal:2',
        'payroll_tax_rate' => 'decimal:4',
        'burden_rate' => 'decimal:4',
        'insurance_rate' => 'decimal:4',
        'job_expenses_rate' => 'decimal:4',
        'consumables_rate' => 'decimal:4',
        'overhead_rate' => 'decimal:4',
        'profit_rate' => 'decimal:4',
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

        // Calculate total markup percentage
        $totalMarkup = (float) $this->payroll_tax_rate
            + (float) $this->burden_rate
            + (float) $this->insurance_rate
            + (float) $this->job_expenses_rate
            + (float) $this->consumables_rate
            + (float) $this->overhead_rate
            + (float) $this->profit_rate;

        // Base loaded rate = base * (1 + total markup %)
        $straightTimeRate = $base * (1 + $totalMarkup);

        // Overtime and double-time: multiply the entire loaded rate by the overtime factor
        $overtimeRate = $straightTimeRate * 1.5;
        $doubleTimeRate = $straightTimeRate * 2;

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
                'base_hourly_rate',
                'payroll_tax_rate',
                'burden_rate',
                'insurance_rate',
                'job_expenses_rate',
                'consumables_rate',
                'overhead_rate',
                'profit_rate',
            ]))) {
                $rates = $model->calculateLoadedRate();
                $model->straight_time_rate = $rates['straight_time'];
                $model->overtime_rate = $rates['overtime'];
                $model->double_time_rate = $rates['double_time'];
            }
        });
    }
}
