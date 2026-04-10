<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Craft extends Model
{
    protected $table = 'crafts';

    protected $fillable = [
        'code',
        'name',
        'description',
        'base_hourly_rate',
        'overtime_multiplier',
        'billable_rate',
        'ot_billable_rate',
        'wc_rate',
        'fica_rate',
        'suta_rate',
        'benefits_rate',
        'overhead_rate',
        'is_active',
    ];

    protected $casts = [
        'base_hourly_rate' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'billable_rate' => 'decimal:2',
        'ot_billable_rate' => 'decimal:2',
        'wc_rate' => 'decimal:4',
        'fica_rate' => 'decimal:4',
        'suta_rate' => 'decimal:4',
        'benefits_rate' => 'decimal:2',
        'overhead_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function changeOrderLabor(): HasMany
    {
        return $this->hasMany(ChangeOrderLabor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
