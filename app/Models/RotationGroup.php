<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rotation group represents a pool of employees who share a working
 * rotation pattern. Mirrors the "Group 1" / "Group 3" / "NF Group 3" columns
 * on legacy superintendent Excel templates.
 *
 * Patterns supported:
 *   - 4_on_4_off            (Rolling 4's: BM-5400 Mat'lHand)
 *   - 8_on_8_off_rotating   (Rolling 8's: 4 days, 4 off, 4 nights — BM-11367 Operators)
 *   - 4_on_3_off
 *   - custom                (use rotation_schedule rows for arbitrary weeks)
 */
class RotationGroup extends Model
{
    protected $table = 'rotation_groups';

    protected $fillable = [
        'project_id',
        'code',
        'name',
        'pattern',
        'current_shift',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(RotationSchedule::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check whether this group is scheduled to work on the given week-ending date.
     * Returns null if no schedule row exists for that week.
     */
    public function isWorkingWeek(\DateTimeInterface|string $weekEndingDate): ?bool
    {
        $date = $weekEndingDate instanceof \DateTimeInterface
            ? $weekEndingDate->format('Y-m-d')
            : $weekEndingDate;

        $row = $this->schedule()->where('week_ending_date', $date)->first();
        return $row?->is_working;
    }
}
