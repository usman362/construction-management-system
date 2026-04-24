<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DailyLog extends Model
{
    protected $table = 'daily_logs';

    protected $fillable = [
        'project_id',
        'date',
        'weather',
        'temperature',
        'temperature_high',
        'temperature_low',
        'precipitation',
        'wind_speed',
        'notes',
        'visitors',
        'safety_issues',
        'incidents_count',
        'near_misses_count',
        'delays',
        'created_by',
    ];

    protected $casts = [
        'date'              => 'date',
        'temperature_high'  => 'decimal:1',
        'temperature_low'   => 'decimal:1',
        'incidents_count'   => 'integer',
        'near_misses_count' => 'integer',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Photo attachments — subset of documents filtered to the "photo" category.
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->where('category', 'photo');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
