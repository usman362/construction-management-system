<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 2026-05-12: Audit row per incoming Twilio webhook. See migration
 * 2026_05_12_000003_create_twilio_messages_table for column semantics.
 */
class TwilioMessage extends Model
{
    protected $fillable = [
        'message_sid', 'from_phone', 'to_phone', 'channel',
        'body', 'num_media', 'media',
        'employee_id', 'intent', 'status',
        'reply', 'error',
        'related_type', 'related_id',
        'raw_payload',
    ];

    protected $casts = [
        'media'       => 'array',
        'raw_payload' => 'array',
        'num_media'   => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
