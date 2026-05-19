<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 2026-05-12: Per-job slice of a TimeClockEntry. See the migration
 * for context — shop crew works multiple jobs in a day without
 * re-badging, so the foreman splits the day's hours across jobs
 * after the punch closes.
 */
class TimeClockAllocation extends Model
{
    protected $fillable = [
        'time_clock_entry_id',
        'project_id',
        'cost_code_id',
        'hours',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'hours'      => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TimeClockEntry::class, 'time_clock_entry_id');
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
