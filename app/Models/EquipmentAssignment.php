<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAssignment extends Model
{
    protected $table = 'equipment_assignments';

    protected $fillable = [
        'equipment_id',
        'project_id',
        'assigned_date',
        // 2026-04-28: when the rental is due back. Used by the rental
        // calendar Gantt and the expiry-alert scheduled command.
        'expected_return_date',
        'returned_date',
        'daily_cost',
    ];

    protected $casts = [
        'assigned_date'        => 'date',
        'expected_return_date' => 'date',
        'returned_date'        => 'date',
        'daily_cost'           => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
