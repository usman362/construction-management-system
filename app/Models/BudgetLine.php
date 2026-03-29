<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $table = 'budget_lines';

    protected $fillable = [
        'project_id',
        'cost_code_id',
        'description',
        'budget_amount',
        'revised_amount',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'revised_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function getCurrentAmountAttribute()
    {
        return $this->revised_amount > 0 ? $this->revised_amount : $this->budget_amount;
    }
}
