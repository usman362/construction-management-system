<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One sheet (a single PDF) in a project's drawing log.
 *
 * Revisions of the same sheet_number share a "logical sheet" — when
 * a new revision is uploaded, the prior current one is marked
 * 'superseded' and linked via superseded_by_id.
 */
class Drawing extends Model
{
    use SoftDeletes;

    public const STATUS_CURRENT    = 'current';
    public const STATUS_SUPERSEDED = 'superseded';

    public const DISCIPLINES = [
        'A' => 'Architectural',
        'S' => 'Structural',
        'M' => 'Mechanical',
        'E' => 'Electrical',
        'P' => 'Plumbing',
        'C' => 'Civil',
        'L' => 'Landscape',
        'I' => 'Interiors',
        'F' => 'Fire Protection',
        'T' => 'Telecom / Low Voltage',
        'G' => 'General',
    ];

    protected $fillable = [
        'project_id',
        'sheet_number',
        'sheet_title',
        'discipline',
        'revision',
        'status',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'superseded_by_id',
        'superseded_at',
        'notes',
    ];

    protected $casts = [
        'superseded_at' => 'datetime',
        'file_size'     => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(Drawing::class, 'superseded_by_id');
    }

    /** Older revisions of the same sheet that this one (eventually) supersedes. */
    public function priorRevisions(): HasMany
    {
        return $this->hasMany(Drawing::class, 'superseded_by_id');
    }

    /** All revisions of this sheet (newest first). */
    public function allRevisions()
    {
        return Drawing::where('project_id', $this->project_id)
            ->where('sheet_number', $this->sheet_number)
            ->orderByDesc('created_at');
    }

    /** Pretty discipline label from the single-letter code. */
    public function getDisciplineLabelAttribute(): ?string
    {
        return $this->discipline ? (self::DISCIPLINES[$this->discipline] ?? $this->discipline) : null;
    }
}
