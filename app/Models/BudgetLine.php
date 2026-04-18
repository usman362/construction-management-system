<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    protected $table = 'budget_lines';

    protected $fillable = [
        'project_id',
        'cost_code_id',
        'cost_type_id',
        'description',
        'budget_amount',
        'revised_amount',
        'labor_hours',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'revised_amount' => 'decimal:2',
        'labor_hours' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }

    public function getCurrentAmountAttribute()
    {
        return $this->revised_amount > 0 ? $this->revised_amount : $this->budget_amount;
    }

    /** Alias for reports/controllers that expect `amount`. */
    public function getAmountAttribute(): mixed
    {
        return $this->current_amount;
    }

    /** Commitments on this project tagged with the same cost code (lazy-load only). */
    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class, 'cost_code_id', 'cost_code_id')
            ->where('commitments.project_id', $this->project_id);
    }

    /** Vendor invoices on this project for the same cost code (lazy-load only). */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'cost_code_id', 'cost_code_id')
            ->where('invoices.project_id', $this->project_id);
    }
}
