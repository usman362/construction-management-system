<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeOrderItem extends Model
{
    protected $table = 'change_order_items';

    protected $fillable = [
        'change_order_id',
        'cost_code_id',
        'description',
        'category',
        'quantity',
        'unit',
        'unit_cost',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }
}
