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
        'legacy_employee_id',
        'legacy_position',
        'legacy_craft',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'home_phone',
        'work_cell',
        'personal_cell',
        'craft_id',
        'role',
        'hourly_rate',
        'overtime_rate',
        'billable_rate',
        'pay_cycle',
        'pay_type',
        'union',
        'employee_type',
        'department',
        'classification',
        'is_supervisor',
        'certified_pay',
        'work_comp_code',
        'suta_state',
        'state_tax',
        'city_tax',
        'burden_rate',
        'hire_date',
        'start_date',
        'rehire_date',
        'term_date',
        'term_reason',
        'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'billable_rate' => 'decimal:2',
        'burden_rate' => 'decimal:2',
        'is_supervisor' => 'boolean',
        'certified_pay' => 'boolean',
        'hire_date' => 'date',
        'start_date' => 'date',
        'rehire_date' => 'date',
        'term_date' => 'date',
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
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return implode(' ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
