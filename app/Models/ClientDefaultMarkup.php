<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-client default markup percentages, broken out by line type.
 *
 * When an estimator adds a Labor / Material / Equipment / Subcontractor line
 * without a manual markup, the EstimateController reads this row to pre-fill
 * `markup_percent` on the new line. The user can still override per-line.
 *
 * Optional cost_type_id narrowing — most clients will have a single global
 * row (cost_type_id = NULL) but some setups want different markups per cost
 * type (e.g. labor-rich jobs get 15%, material-heavy jobs get 8%).
 */
class ClientDefaultMarkup extends Model
{
    protected $table = 'client_default_markups';

    protected $fillable = [
        'client_id',
        'cost_type_id',
        'labor_markup_percent',
        'material_markup_percent',
        'equipment_markup_percent',
        'subcontractor_markup_percent',
        'other_markup_percent',
    ];

    protected $casts = [
        'labor_markup_percent'         => 'decimal:4',
        'material_markup_percent'      => 'decimal:4',
        'equipment_markup_percent'     => 'decimal:4',
        'subcontractor_markup_percent' => 'decimal:4',
        'other_markup_percent'         => 'decimal:4',
    ];

    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function costType(): BelongsTo { return $this->belongsTo(CostType::class); }

    /**
     * Resolve the right markup % for a given line type on this row.
     */
    public function markupForType(string $lineType): float
    {
        return match ($lineType) {
            EstimateLine::TYPE_LABOR         => (float) $this->labor_markup_percent,
            EstimateLine::TYPE_MATERIAL      => (float) $this->material_markup_percent,
            EstimateLine::TYPE_EQUIPMENT     => (float) $this->equipment_markup_percent,
            EstimateLine::TYPE_SUBCONTRACTOR => (float) $this->subcontractor_markup_percent,
            default                          => (float) $this->other_markup_percent,
        };
    }
}
