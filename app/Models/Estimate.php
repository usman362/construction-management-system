<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Phase 1 enrichment:
 *   - client_id (multi-client estimating before a Project exists)
 *   - sections relation + cost/price/margin totals
 *   - lifecycle dates (valid_until, start/end, sent/responded)
 *   - converted_to_project_id (link back when accepted estimate becomes a project)
 *
 * Total computation lives in EstimateSectionObserver / EstimateLineObserver —
 * setting any line's amount triggers a section + estimate roll-up automatically.
 */
class Estimate extends Model
{
    protected $fillable = [
        // Legacy
        'project_id',
        'estimate_number',
        'name',
        'description',
        'total_amount',
        'status',
        'created_by',

        // Phase 1
        'client_id',
        'estimate_type',
        // Phase 2 — link to a specific Change Order ("smaller estimating
        // module for change orders inside the project")
        'change_order_id',
        'total_cost',
        'total_price',
        'margin_percent',
        'valid_from',
        'valid_until',
        'start_date',
        'end_date',
        'duration_days',
        'sent_to_client_date',
        'client_response_date',
        'converted_to_project_id',
        'terms_and_conditions',
        'assumed_exclusions',
    ];

    protected $casts = [
        'total_amount'         => 'decimal:2',
        'total_cost'           => 'decimal:2',
        'total_price'          => 'decimal:2',
        'margin_percent'       => 'decimal:4',
        'duration_days'        => 'integer',
        'valid_from'           => 'date',
        'valid_until'          => 'date',
        'start_date'           => 'date',
        'end_date'             => 'date',
        'sent_to_client_date'  => 'datetime',
        'client_response_date' => 'datetime',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function lines(): HasMany      { return $this->hasMany(EstimateLine::class); }
    public function sections(): HasMany   { return $this->hasMany(EstimateSection::class)->orderBy('sort_order'); }

    public function convertedToProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_to_project_id');
    }

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    /**
     * Recompute total_cost / total_price / margin_percent from the line-level
     * data. Called by the observers after any section or line changes.
     */
    public function recalculateTotals(): void
    {
        $cost  = (float) $this->lines()->sum('cost_amount');
        $price = (float) $this->lines()->sum('price_amount');
        $margin = $price > 0 ? round((($price - $cost) / $price), 4) : 0;

        $this->total_cost     = round($cost, 2);
        $this->total_price    = round($price, 2);
        $this->margin_percent = $margin;
        // Legacy alias — still used by older reports/exports.
        $this->total_amount   = round($price, 2);

        $this->saveQuietly();
    }
}
