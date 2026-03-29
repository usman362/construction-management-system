<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeOrderLabor extends Model
{
    protected $table = 'change_order_labor';

    protected $fillable = [
        'change_order_id',
        'craft_id',
        'skill_description',
        'num_workers',
        'rate_per_hour',
        'hours_per_day',
        'duration_days',
        'is_overtime',
        'cost',
    ];

    protected $casts = [
        'rate_per_hour' => 'decimal:2',
        'hours_per_day' => 'decimal:2',
        'duration_days' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_overtime' => 'boolean',
    ];

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class);
    }
}
