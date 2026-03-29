<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeOrder extends Model
{
    protected $table = 'change_orders';

    protected $fillable = [
        'project_id',
        'co_number',
        'date',
        'description',
        'scope_of_work',
        'status',
        'amount',
        'contract_time_change_days',
        'new_completion_date',
        'approved_by',
        'approved_date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'new_completion_date' => 'date',
        'approved_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class);
    }

    public function laborDetails(): HasMany
    {
        return $this->hasMany(ChangeOrderLabor::class);
    }
}
