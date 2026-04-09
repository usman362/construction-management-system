<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (rotation_group, week_ending_date) mirroring the "Rotation
 * Schedule" tab on legacy Excel templates. `is_working` is the Yes/No flag
 * from that tab; `shift_type` lets 8-on/8-off rotations distinguish which
 * weeks are days vs nights.
 */
class RotationSchedule extends Model
{
    protected $table = 'rotation_schedule';

    protected $fillable = [
        'rotation_group_id',
        'week_ending_date',
        'is_working',
        'shift_type',
        'notes',
    ];

    protected $casts = [
        'week_ending_date' => 'date',
        'is_working' => 'boolean',
    ];

    public function rotationGroup(): BelongsTo
    {
        return $this->belongsTo(RotationGroup::class);
    }
}
