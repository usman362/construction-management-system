<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id',
        'project_id',
        'cost_code_id',
        'cost_type_id',
        'crew_id',
        'date',
        'shift_id',
        'work_order_number',
        'regular_hours',
        'overtime_hours',
        'double_time_hours',
        'force_overtime',
        'total_hours',
        'gate_log_hours',
        'work_through_lunch',
        'client_signature',
        'client_signature_name',
        'signed_at',
        'regular_rate',
        'overtime_rate',
        'total_cost',
        'billable_rate',
        'billable_amount',
        'is_billable',
        'rate_type',
        // 2026-04-28: Earnings category — HE/HO/VA per Brenda's payroll codes.
        'earnings_category',
        'project_billable_rate_id',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'double_time_hours' => 'decimal:2',
        'force_overtime' => 'boolean',
        'total_hours' => 'decimal:2',
        'gate_log_hours' => 'decimal:2',
        'work_through_lunch' => 'boolean',
        'signed_at' => 'datetime',
        'regular_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'billable_rate' => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'is_billable' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function costAllocations(): HasMany
    {
        return $this->hasMany(TimesheetCostAllocation::class);
    }

    public function projectBillableRate(): BelongsTo
    {
        return $this->belongsTo(ProjectBillableRate::class);
    }
}
