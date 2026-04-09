<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $table = 'daily_logs';

    protected $fillable = [
        'project_id',
        'date',
        'weather',
        'temperature',
        'notes',
        'visitors',
        'safety_issues',
        'delays',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
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
