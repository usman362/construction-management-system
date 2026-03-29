<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManhourBudget extends Model
{
    protected $table = 'manhour_budgets';

    protected $fillable = [
        'project_id',
        'cost_code_id',
        'category',
        'budget_hours',
        'earned_hours',
    ];

    protected $casts = [
        'budget_hours' => 'decimal:2',
        'earned_hours' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }
}
