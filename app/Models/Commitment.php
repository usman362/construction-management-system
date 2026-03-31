<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commitment extends Model
{
    protected $fillable = [
        'project_id',
        'vendor_id',
        'cost_code_id',
        'commitment_number',
        'po_number',
        'description',
        'notes',
        'amount',
        'committed_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'committed_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
