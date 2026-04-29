<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail for AI-OCR'd timesheet photos. See migration for context.
 */
class TimesheetScanLog extends Model
{
    protected $fillable = [
        'user_id',
        'image_path',
        'original_filename',
        'file_size_bytes',
        'extracted_payload',
        'raw_response',
        'created_timesheet_ids',
        'status',
        'error_message',
    ];

    protected $casts = [
        'extracted_payload'      => 'array',
        'raw_response'           => 'array',
        'created_timesheet_ids'  => 'array',
        'file_size_bytes'        => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
