<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetCostAllocation extends Model
{
    protected $table = 'timesheet_cost_allocations';

    protected $fillable = [
        'timesheet_id',
        'cost_code_id',
        'hours',
        'cost',
        'per_diem_amount',
        'work_authorization',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'per_diem_amount' => 'decimal:2',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }
}
