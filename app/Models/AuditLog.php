<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false; // Append-only — only created_at (set via DB default).

    protected $fillable = [
        'user_id',
        'user_name',
        'auditable_type',
        'auditable_id',
        'event',
        'changes',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Short, human-friendly label for the audited entity type
     * (e.g. "App\Models\ChangeOrder" → "Change Order").
     */
    public function getEntityLabelAttribute(): string
    {
        $base = class_basename($this->auditable_type);
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $base));
    }
}
