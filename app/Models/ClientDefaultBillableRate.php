<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-client default billable rate template.
 *
 * Used by the estimating UI to pre-fill labor lines with the right hourly
 * cost + loaded billable rate before a Project even exists. When an estimate
 * is accepted and converted to a Project, these rows are copied into
 * ProjectBillableRate so timesheets immediately have rates to charge against.
 *
 * Mirrors ProjectBillableRate's column structure 1:1 (every ST + OT markup
 * pair) so the conversion is a column-for-column copy.
 */
class ClientDefaultBillableRate extends Model
{
    protected $table = 'client_default_billable_rates';

    protected $fillable = [
        'client_id', 'craft_id', 'employee_id',
        'base_hourly_rate', 'base_ot_hourly_rate',

        'payroll_tax_rate', 'burden_rate', 'insurance_rate', 'job_expenses_rate',
        'consumables_rate', 'overhead_rate', 'profit_rate',

        'payroll_tax_ot_rate', 'burden_ot_rate', 'insurance_ot_rate', 'job_expenses_ot_rate',
        'consumables_ot_rate', 'overhead_ot_rate', 'profit_ot_rate',

        'straight_time_rate', 'overtime_rate', 'double_time_rate',

        'effective_date', 'notes',
    ];

    protected $casts = [
        'base_hourly_rate'      => 'decimal:2',
        'base_ot_hourly_rate'   => 'decimal:4',
        'payroll_tax_rate'      => 'decimal:4',
        'burden_rate'           => 'decimal:4',
        'insurance_rate'        => 'decimal:4',
        'job_expenses_rate'     => 'decimal:4',
        'consumables_rate'      => 'decimal:4',
        'overhead_rate'         => 'decimal:4',
        'profit_rate'           => 'decimal:4',
        'payroll_tax_ot_rate'   => 'decimal:4',
        'burden_ot_rate'        => 'decimal:4',
        'insurance_ot_rate'     => 'decimal:4',
        'job_expenses_ot_rate'  => 'decimal:4',
        'consumables_ot_rate'   => 'decimal:4',
        'overhead_ot_rate'      => 'decimal:4',
        'profit_ot_rate'        => 'decimal:4',
        'straight_time_rate'    => 'decimal:2',
        'overtime_rate'         => 'decimal:2',
        'double_time_rate'      => 'decimal:2',
        'effective_date'        => 'date',
    ];

    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function craft(): BelongsTo    { return $this->belongsTo(Craft::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }

    /**
     * Same loaded-rate computation as ProjectBillableRate so the two tables
     * stay in lockstep.
     */
    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $base   = (float) ($row->base_hourly_rate ?? 0);
            $baseOt = (float) ($row->base_ot_hourly_rate ?? 0);

            $stMarkup = (float) $row->payroll_tax_rate
                      + (float) $row->burden_rate
                      + (float) $row->insurance_rate
                      + (float) $row->job_expenses_rate
                      + (float) $row->consumables_rate
                      + (float) $row->overhead_rate
                      + (float) $row->profit_rate;

            $otMarkup = (float) $row->payroll_tax_ot_rate
                      + (float) $row->burden_ot_rate
                      + (float) $row->insurance_ot_rate
                      + (float) $row->job_expenses_ot_rate
                      + (float) $row->consumables_ot_rate
                      + (float) $row->overhead_ot_rate
                      + (float) $row->profit_ot_rate;

            $row->straight_time_rate = round($base * (1 + $stMarkup), 2);
            $row->overtime_rate      = $baseOt > 0
                ? round($baseOt * (1 + $otMarkup), 2)
                : round(1.5 * $base * (1 + $otMarkup), 2);
            $row->double_time_rate   = round(2.0 * $base * (1 + $otMarkup), 2);
        });
    }
}
