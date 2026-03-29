<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name',
        'description',
        'unit_of_measure',
        'unit_cost',
        'vendor_id',
        'category',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }
}
