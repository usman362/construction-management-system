<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_duration',
        'hours_per_day',
        'multiplier',
        'is_active',
    ];

    protected $casts = [
        'break_duration' => 'integer',
        'hours_per_day' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function crews(): HasMany
    {
        return $this->hasMany(Crew::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }
}
