<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tool extends Model
{
    protected $fillable = [
        'name', 'asset_tag', 'category', 'location', 'serial_number',
        'qr_token', 'replacement_cost', 'purchase_date',
        'purchase_ticket_path', 'purchase_ticket_name',
        'status', 'notes',
    ];

    protected $casts = [
        'replacement_cost' => 'decimal:2',
        'purchase_date'    => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (empty($t->qr_token)) $t->qr_token = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ToolAssignment::class);
    }

    /** Currently-issued assignment (returned_date null), if any. */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(ToolAssignment::class)->whereNull('returned_date');
    }
}
