<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewMember extends Model
{
    protected $table = 'crew_members';

    protected $fillable = [
        'crew_id',
        'employee_id',
        'assigned_date',
        'removed_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'removed_date' => 'date',
    ];

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
