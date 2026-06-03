<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row inside an estimate. Phase 1 added a `line_type` discriminator so a
 * single table represents labor, material, equipment, subcontractor, and
 * "other" lines without separate child tables.
 *
 * Auto-calc pipeline (handled by EstimateLineObserver):
 *   - Labor lines:    cost_amount = hours × hourly_cost_rate
 *   - Other lines:    cost_amount = quantity × unit_cost
 *   - Always:         markup_amount = cost_amount × markup_percent
 *                     price_amount  = cost_amount + markup_amount
 *
 * After save, the parent EstimateSection (if set) and Estimate are also
 * recomputed — the user never has to refresh a "totals" cell manually.
 */
class EstimateLine extends Model
{
    protected $table = 'estimate_lines';

    public const TYPE_LABOR         = 'labor';
    public const TYPE_MATERIAL      = 'material';
    public const TYPE_EQUIPMENT     = 'equipment';
    public const TYPE_SUBCONTRACTOR = 'subcontractor';
    public const TYPE_OTHER         = 'other';

    public const TYPES = [
        self::TYPE_LABOR         => 'Labor',
        self::TYPE_MATERIAL      => 'Material',
        self::TYPE_EQUIPMENT     => 'Equipment',
        self::TYPE_SUBCONTRACTOR => 'Subcontractor',
        self::TYPE_OTHER         => 'Other',
    ];

    protected $fillable = [
        // Legacy (kept for backwards compat)
        'estimate_id',
        'cost_code_id',
        'cost_type_id',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        // 2026-05-23 (KH WBS): separate quote / freight / tax columns so
        // the cost build-up matches her spreadsheet. Cost = sum of these.
        'quote_amount',
        'freight_amount',
        'tax_amount',
        'amount',
        'labor_hours',
        // 2026-05-23 (Brenda): OT companion to hours / hourly_cost_rate /
        // hourly_billable_rate so one labor row carries both ST + OT.
        'ot_hours',
        'ot_hourly_cost_rate',
        'ot_hourly_billable_rate',

        // Phase 1 additions
        'line_type',
        'section_id',
        'sort_order',

        // Labor-line specifics
        'craft_id',
        'hours',
        'hourly_cost_rate',
        'hourly_billable_rate',

        // Catalog references
        'material_id',
        'equipment_id',

        // Pricing pipeline — populated by observer
        'cost_amount',
        'markup_percent',
        'markup_amount',
        'price_amount',
        'is_billable',

        'notes',
    ];

    protected $casts = [
        'quantity'             => 'decimal:2',
        'unit_cost'            => 'decimal:2',
        'quote_amount'         => 'decimal:2',
        'freight_amount'       => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'amount'               => 'decimal:2',
        'labor_hours'          => 'decimal:2',
        'hours'                => 'decimal:2',
        'hourly_cost_rate'     => 'decimal:2',
        'hourly_billable_rate' => 'decimal:2',
        'ot_hours'                => 'decimal:2',
        'ot_hourly_cost_rate'     => 'decimal:2',
        'ot_hourly_billable_rate' => 'decimal:2',
        'cost_amount'          => 'decimal:2',
        'markup_percent'       => 'decimal:4',
        'markup_amount'        => 'decimal:2',
        'price_amount'         => 'decimal:2',
        'is_billable'          => 'boolean',
        'sort_order'           => 'integer',
    ];

    public function estimate(): BelongsTo  { return $this->belongsTo(Estimate::class); }
    public function section(): BelongsTo   { return $this->belongsTo(EstimateSection::class, 'section_id'); }
    public function costCode(): BelongsTo  { return $this->belongsTo(CostCode::class); }
    public function costType(): BelongsTo  { return $this->belongsTo(CostType::class); }
    public function craft(): BelongsTo     { return $this->belongsTo(Craft::class); }
    public function material(): BelongsTo  { return $this->belongsTo(Material::class); }
    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }

    /**
     * Compute cost / markup / price using the line-type's logic.
     * Called by EstimateLineObserver before save.
     */
    public function recalculate(): void
    {
        if ($this->line_type === self::TYPE_LABOR) {
            // 2026-05-23 (Brenda): single labor line now carries both ST
            // and OT. Cost = ST hrs × ST rate + OT hrs × OT rate (each side
            // independent so OT rate can be ≠ 1.5× ST when union /
            // prevailing wage rules differ). Billable computed the same.
            $stCost = (float) ($this->hours ?? 0)    * (float) ($this->hourly_cost_rate ?? 0);
            $otCost = (float) ($this->ot_hours ?? 0) * (float) ($this->ot_hourly_cost_rate ?? 0);
            $cost = $stCost + $otCost;

            // 2026-05-31 (Brenda: "we need this to pull the billable rate"):
            // when explicit ST / OT billable rates are stored on the line
            // (set by addLaborBundle from the project rate sheet, or the
            // Recompute Rates action), the BILLABLE total comes from
            // hours × billable rate directly — NOT cost × markup. The
            // displayed markup % / markup $ becomes a derived view of
            // the spread, so KH can still see it. If no billable rates
            // are stored we fall back to the legacy cost × markup path
            // below, which keeps non-labor lines and old data working.
            $stBill = (float) ($this->hourly_billable_rate ?? 0);
            $otBill = (float) ($this->ot_hourly_billable_rate ?? 0);
            if ($stBill > 0 || $otBill > 0) {
                $price  = ((float) ($this->hours ?? 0)    * $stBill)
                        + ((float) ($this->ot_hours ?? 0) * $otBill);
                $markup = max(0.0, $price - $cost);
                // Update the stored markup_percent so reports + the rate-
                // sheet view stay consistent with the displayed billable.
                $this->markup_percent = $cost > 0 ? round($markup / $cost, 4) : 0;
                $this->cost_amount = round($cost, 2);
                if ($this->is_billable === false) {
                    $this->markup_amount = 0;
                    $this->price_amount  = 0;
                } else {
                    $this->markup_amount = round($markup, 2);
                    $this->price_amount  = round($price, 2);
                }
                $this->amount = $this->cost_amount;
                return;
            }
        } else {
            // 2026-05-23 (KH WBS): if quote/freight/tax are populated they
            // win (cost = sum); otherwise fall back to legacy qty × unit_cost.
            $quote   = (float) ($this->quote_amount   ?? 0);
            $freight = (float) ($this->freight_amount ?? 0);
            $tax     = (float) ($this->tax_amount     ?? 0);
            if ($quote > 0 || $freight > 0 || $tax > 0) {
                $cost = $quote + $freight + $tax;
                // Mirror onto unit_cost so legacy reports stay sane.
                $this->quantity  = $this->quantity  ?: 1;
                $this->unit_cost = $cost / max(1, (float) $this->quantity);
            } else {
                $cost = (float) ($this->quantity ?? 0) * (float) ($this->unit_cost ?? 0);
            }
        }

        $markupPct = (float) ($this->markup_percent ?? 0);
        $markup    = round($cost * $markupPct, 2);
        $price     = round($cost + $markup, 2);

        $this->cost_amount   = round($cost, 2);

        // 2026-05-23 (KH): non-billable lines stay in the cost total but
        // contribute $0 to markup / price. The Estimate's billable total
        // therefore excludes them, while cost (what we spend) still
        // includes them.
        if ($this->is_billable === false) {
            $this->markup_amount = 0;
            $this->price_amount  = 0;
        } else {
            $this->markup_amount = $markup;
            $this->price_amount  = $price;
        }

        // Keep legacy `amount` aligned with `cost_amount` so reports/PDFs that
        // still reference the old column don't suddenly read 0 on enriched lines.
        $this->amount = $this->cost_amount;
    }
}
