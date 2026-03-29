<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'craft_id',
        'role',
        'hourly_rate',
        'overtime_rate',
        'billable_rate',
        'hire_date',
        'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'billable_rate' => 'decimal:2',
        'hire_date' => 'date',
    ];

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function crewMemberships(): HasMany
    {
        return $this->hasMany(CrewMember::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
