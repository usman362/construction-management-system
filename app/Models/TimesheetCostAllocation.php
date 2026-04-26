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
        'cost_type_id',
        // Separate cost type for per_diem_amount so reports don't roll per
        // diem dollars into the labor bucket. See migration
        // 2026_04_25_000002_add_per_diem_cost_type_id_to_timesheet_cost_allocations.
        'per_diem_cost_type_id',
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

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }

    /**
     * Cost type that the `per_diem_amount` rolls up under in reports.
     * Defaults to the seeded "PER DIEM" cost type (code 07) when set by the
     * controller; falls back to $this->costType if blank for older rows.
     */
    public function perDiemCostType(): BelongsTo
    {
        return $this->belongsTo(CostType::class, 'per_diem_cost_type_id');
    }
}
