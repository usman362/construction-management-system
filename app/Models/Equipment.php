<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'description',
        'type',
        'model_number',
        'serial_number',
        'qr_token',         // Unique sticker token for QR check-in/out flow.
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        // 2026-06-04 (Brenda): "I do not see where to put the start and
        // stop rent date for the equipment. It is not in the set up, view,
        // or edit tabs."
        'rent_start_date',
        'rent_end_date',
        'vendor_id',
        'status',
    ];

    /**
     * Auto-generate a QR token for any equipment that doesn't have one
     * (covers the create flow; existing rows are backfilled by migration).
     */
    protected static function booted(): void
    {
        static::creating(function (self $eq) {
            if (empty($eq->qr_token)) {
                $eq->qr_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'rent_start_date' => 'date',
        'rent_end_date'   => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EquipmentAssignment::class)->whereNull('returned_date');
    }
}
