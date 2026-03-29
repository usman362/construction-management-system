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
        'is_active',
    ];

    protected $casts = [
        'base_hourly_rate' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'billable_rate' => 'decimal:2',
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
