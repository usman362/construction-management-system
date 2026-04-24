<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeClockEntry extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'employee_id',
        'project_id',
        'cost_code_id',
        'clock_in_at',
        'clock_in_lat',
        'clock_in_lng',
        'clock_in_accuracy_m',
        'clock_out_at',
        'clock_out_lat',
        'clock_out_lng',
        'clock_out_accuracy_m',
        'within_geofence',
        'distance_m',
        'hours',
        'notes',
        'timesheet_id',
        'status',
    ];

    protected $casts = [
        'clock_in_at'     => 'datetime',
        'clock_out_at'    => 'datetime',
        'clock_in_lat'    => 'decimal:7',
        'clock_in_lng'    => 'decimal:7',
        'clock_out_lat'   => 'decimal:7',
        'clock_out_lng'   => 'decimal:7',
        'within_geofence' => 'boolean',
        'hours'           => 'decimal:2',
    ];

    public static array $statusLabels = [
        'open'      => 'Open',
        'closed'    => 'Closed',
        'converted' => 'Converted',
        'voided'    => 'Voided',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    /**
     * Close an open entry — stamps clock-out time, GPS, and computes hours.
     */
    public function closeOut(?float $lat, ?float $lng, ?int $accuracy = null, ?\DateTimeInterface $at = null): void
    {
        $at ??= now();
        $this->clock_out_at = $at;
        $this->clock_out_lat = $lat;
        $this->clock_out_lng = $lng;
        $this->clock_out_accuracy_m = $accuracy;

        // Hours computed from timestamps, rounded to 2 decimals.
        $this->hours = $this->clock_in_at
            ? round(($this->clock_out_at->getTimestamp() - $this->clock_in_at->getTimestamp()) / 3600, 2)
            : 0;

        $this->status = 'closed';
        $this->save();
    }
}
