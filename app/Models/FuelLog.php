<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $fillable = [
        'equipment_id', 'project_id', 'cost_code_id',
        'fuel_date', 'fuel_type', 'gallons', 'price_per_gallon', 'total_cost',
        'odometer_reading', 'hour_meter_reading',
        'vendor_name', 'receipt_number', 'notes', 'logged_by',
    ];

    protected $casts = [
        'fuel_date'        => 'date',
        'gallons'          => 'decimal:3',
        'price_per_gallon' => 'decimal:4',
        'total_cost'       => 'decimal:2',
    ];

    /** Auto-calculate total_cost = gallons × price_per_gallon when missing. */
    protected static function booted(): void
    {
        static::saving(function (self $log) {
            if ($log->gallons && $log->price_per_gallon) {
                $log->total_cost = round((float) $log->gallons * (float) $log->price_per_gallon, 2);
            }
        });
    }

    public function equipment(): BelongsTo  { return $this->belongsTo(Equipment::class); }
    public function project(): BelongsTo    { return $this->belongsTo(Project::class); }
    public function costCode(): BelongsTo   { return $this->belongsTo(CostCode::class); }
    public function logger(): BelongsTo     { return $this->belongsTo(User::class, 'logged_by'); }
}
