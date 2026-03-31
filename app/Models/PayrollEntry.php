<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    protected $table = 'payroll_entries';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'project_id',
        'cost_code_id',
        'regular_hours',
        'overtime_hours',
        'double_time_hours',
        'regular_pay',
        'overtime_pay',
        'double_time_pay',
        'total_pay',
        'billable_amount',
        'per_diem',
    ];

    protected $casts = [
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'double_time_hours' => 'decimal:2',
        'regular_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'double_time_pay' => 'decimal:2',
        'total_pay' => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'per_diem' => 'decimal:2',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

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
}
