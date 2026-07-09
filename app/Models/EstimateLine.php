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

    public const LABOR_DIRECT          = 'direct_labor';
    public const LABOR_INDIRECT        = 'indirect_field_labor';
    public const LABOR_FIELD_STAFF     = 'field_staff';

    public const LABOR_CATEGORIES = [
        self::LABOR_DIRECT      => 'Direct Labor',
        self::LABOR_INDIRECT    => 'Indirect Field Labor',
        self::LABOR_FIELD_STAFF => 'Field Staff Labor',
    ];

    public const EQUIP_3RD_PARTY     = '3rd_party';
    public const EQUIP_COMPANY_OWNED = 'company_owned';

    public const EQUIPMENT_CATEGORIES = [
        self::EQUIP_3RD_PARTY     => '3rd Party Equipment',
        self::EQUIP_COMPANY_OWNED => 'Company Owned Equipment',
    ];

    protected $fillable = [
        'estimate_id',
        'cost_code_id',
        'cost_type_id',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'quote_amount',
        'freight_amount',
        'tax_amount',
        'amount',
        'labor_hours',
        'ot_hours',
        'ot_hourly_cost_rate',
        'ot_hourly_billable_rate',
        'premium_hours',
        'premium_hourly_cost_rate',
        'premium_hourly_billable_rate',

        'line_type',
        'labor_category',
        'section_id',
        'sort_order',

        // Labor crew-scheduling fields (T&M template)
        'work_schedule',
        'role',
        'crew_size',
        'weeks',
        'days_per_week',
        'hours_per_day',
        'ot_daily_threshold',

        'craft_id',
        'hours',
        'hourly_cost_rate',
        'hourly_billable_rate',

        'material_id',
        'vendor_name',
        'subcontractor_name',
        'discipline',
        'equipment_id',
        'equipment_category',
        'duration_uom',
        'equipment_duration',
        'fuel_cost',

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
        'premium_hours'                => 'decimal:2',
        'premium_hourly_cost_rate'     => 'decimal:4',
        'premium_hourly_billable_rate' => 'decimal:4',
        'crew_size'            => 'integer',
        'weeks'                => 'decimal:2',
        'days_per_week'        => 'integer',
        'hours_per_day'        => 'decimal:2',
        'ot_daily_threshold'   => 'decimal:2',
        'equipment_duration'   => 'decimal:2',
        'fuel_cost'            => 'decimal:2',
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
    /**
     * Auto-compute total hours from crew scheduling fields when present.
     * Total Hours = Crew Size × Weeks × Days/Week × Hours/Day.
     * Only overwrites `hours` if all four crew fields are populated.
     */
    public function computeCrewHours(): void
    {
        $crew = (int) ($this->crew_size ?? 0);
        $wks  = (float) ($this->weeks ?? 0);
        $dpw  = (int) ($this->days_per_week ?? 0);
        $hpd  = (float) ($this->hours_per_day ?? 0);

        if ($crew > 0 && $wks > 0 && $dpw > 0 && $hpd > 0) {
            // 2026-07-04 (Brenda EST-BM-5751): OT threshold is configurable.
            // Default = the scheduled hours_per_day (so a 5-10 schedule stays
            // all straight-time — no phantom OT). Set ot_daily_threshold lower
            // (e.g. 8) on jobs where OT kicks in before the scheduled day ends.
            $threshold = (float) ($this->ot_daily_threshold ?? 0);
            if ($threshold <= 0) $threshold = $hpd;

            $stPerDay = min($hpd, $threshold);
            $otPerDay = max(0, $hpd - $threshold);

            $this->hours    = round($crew * $wks * $dpw * $stPerDay, 2);
            $this->ot_hours = round($crew * $wks * $dpw * $otPerDay, 2);
        }
    }

    public function recalculate(): void
    {
        if ($this->line_type === self::TYPE_LABOR) {
            // 2026-07-04 (Brenda): only auto-split ST/OT from the crew fields
            // when hours haven't been set yet. Once ST/OT hold a value (filled
            // by the live calc or typed manually), we NEVER overwrite them —
            // this is what let Brenda hand-correct a line without it zeroing
            // out / snapping back.
            if ((float) ($this->hours ?? 0) <= 0 && (float) ($this->ot_hours ?? 0) <= 0) {
                $this->computeCrewHours();
            }

            $stHrs  = (float) ($this->hours ?? 0);
            $otHrs  = (float) ($this->ot_hours ?? 0);
            $pmHrs  = (float) ($this->premium_hours ?? 0);

            $stCost = $stHrs * (float) ($this->hourly_cost_rate ?? 0);
            $otCost = $otHrs * (float) ($this->ot_hourly_cost_rate ?? 0);
            $pmCost = $pmHrs * (float) ($this->premium_hourly_cost_rate ?? 0);
            $cost   = $stCost + $otCost + $pmCost;

            $stBill = (float) ($this->hourly_billable_rate ?? 0);
            $otBill = (float) ($this->ot_hourly_billable_rate ?? 0);
            $pmBill = (float) ($this->premium_hourly_billable_rate ?? 0);

            if ($stBill > 0 || $otBill > 0 || $pmBill > 0) {
                $price  = ($stHrs * $stBill) + ($otHrs * $otBill) + ($pmHrs * $pmBill);
                $markup = max(0.0, $price - $cost);

                if ($cost > 0) {
                    $this->markup_percent = round($markup / $cost, 4);
                } elseif ($price > 0) {
                    $this->markup_percent = 1.0;
                } else {
                    $this->markup_percent = 0;
                }
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
        } elseif ($this->line_type === self::TYPE_EQUIPMENT && $this->duration_uom && $this->equipment_duration) {
            // Equipment with duration UOM: cost = qty × unit_cost × duration + fuel + freight
            $qty       = max(1, (float) ($this->quantity ?? 1));
            $equipCost = $qty * (float) ($this->unit_cost ?? 0) * (float) ($this->equipment_duration ?? 0);
            $fuel      = (float) ($this->fuel_cost ?? 0);
            $freight   = (float) ($this->freight_amount ?? 0);
            $cost      = $equipCost + $fuel + $freight;
        } else {
            $quote   = (float) ($this->quote_amount   ?? 0);
            $freight = (float) ($this->freight_amount ?? 0);
            $tax     = (float) ($this->tax_amount     ?? 0);
            if ($quote > 0 || $freight > 0 || $tax > 0) {
                $cost = $quote + $freight + $tax;
                $this->quantity  = $this->quantity  ?: 1;
                $this->unit_cost = $cost / max(1, (float) $this->quantity);
            } else {
                $cost = (float) ($this->quantity ?? 0) * (float) ($this->unit_cost ?? 0);
            }
        }

        $markupPct = (float) ($this->markup_percent ?? 0);
        $markup    = round($cost * $markupPct, 2);
        $price     = round($cost + $markup, 2);

        $this->cost_amount = round($cost, 2);

        if ($this->is_billable === false) {
            $this->markup_amount = 0;
            $this->price_amount  = 0;
        } else {
            $this->markup_amount = $markup;
            $this->price_amount  = $price;
        }

        $this->amount = $this->cost_amount;
    }
}
