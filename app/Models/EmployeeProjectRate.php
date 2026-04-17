<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-project override of an employee's pay rates.
 * Timesheet cost calculation checks this first; if none found it falls
 * back to the rates on the employee's main profile.
 */
class EmployeeProjectRate extends Model
{
    protected $table = 'employee_project_rates';

    protected $fillable = [
        'project_id',
        'employee_id',
        'hourly_rate',
        'overtime_rate',
        'double_time_rate',
        'billable_rate',
        'st_burden_rate',
        'ot_burden_rate',
        'effective_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'double_time_rate' => 'decimal:2',
        'billable_rate' => 'decimal:2',
        'st_burden_rate' => 'decimal:4',
        'ot_burden_rate' => 'decimal:4',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
