<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostType extends Model
{
    protected $table = 'cost_types';

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function costCodes(): HasMany
    {
        return $this->hasMany(CostCode::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
