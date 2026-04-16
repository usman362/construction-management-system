<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCode extends Model
{
    protected $table = 'cost_codes';

    protected $fillable = [
        'code',
        'name',
        'cost_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function timesheetAllocations(): HasMany
    {
        return $this->hasMany(TimesheetCostAllocation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function changeOrderItems(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class);
    }

    public function estimateLines(): HasMany
    {
        return $this->hasMany(EstimateLine::class);
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function manhourBudgets(): HasMany
    {
        return $this->hasMany(ManhourBudget::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
