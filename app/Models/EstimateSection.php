<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A logical grouping of estimate lines (e.g. "Sitework", "Concrete",
 * "Mechanical"). Sections drive the bid-PDF section subtotals + readability
 * and provide the "Add Line" target on the estimate UI sidebar.
 *
 * cost_amount / price_amount are denormalized rollups — recalculated by the
 * EstimateLineObserver each time a child line is saved or removed.
 */
class EstimateSection extends Model
{
    protected $table = 'estimate_sections';

    protected $fillable = [
        'estimate_id',
        'name',
        'description',
        'cost_amount',
        'price_amount',
        'sort_order',
    ];

    protected $casts = [
        'cost_amount'  => 'decimal:2',
        'price_amount' => 'decimal:2',
        'sort_order'   => 'integer',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EstimateLine::class, 'section_id')->orderBy('sort_order');
    }

    /**
     * Recompute totals from the section's lines. Called by the EstimateLineObserver.
     */
    public function recalculateTotals(): void
    {
        $this->cost_amount  = (float) $this->lines()->sum('cost_amount');
        $this->price_amount = (float) $this->lines()->sum('price_amount');
        $this->saveQuietly();
    }
}
