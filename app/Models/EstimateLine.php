<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateLine extends Model
{
    protected $table = 'estimate_lines';

    protected $fillable = [
        'estimate_id',
        'cost_code_id',
        'cost_type_id',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'amount',
        'labor_hours',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'amount' => 'decimal:2',
        'labor_hours' => 'decimal:2',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }
}
