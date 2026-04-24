<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfi extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'rfis';

    protected $fillable = [
        'project_id',
        'rfi_number',
        'subject',
        'question',
        'response',
        'cost_schedule_impact',
        'status',
        'priority',
        'category',
        'submitted_by',
        'assigned_to',
        'responded_by',
        'submitted_date',
        'needed_by',
        'responded_date',
        'closed_date',
        'cost_impact',
        'schedule_impact',
    ];

    protected $casts = [
        'submitted_date'   => 'date',
        'needed_by'        => 'date',
        'responded_date'   => 'date',
        'closed_date'      => 'date',
        'cost_impact'      => 'boolean',
        'schedule_impact'  => 'boolean',
    ];

    public static array $statusLabels = [
        'draft'     => 'Draft',
        'submitted' => 'Submitted',
        'in_review' => 'In Review',
        'answered'  => 'Answered',
        'closed'    => 'Closed',
    ];

    public static array $priorityLabels = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ];

    public static array $categoryLabels = [
        'drawings'        => 'Drawings',
        'specifications'  => 'Specifications',
        'scope'           => 'Scope',
        'schedule'        => 'Schedule',
        'field_condition' => 'Field Condition',
        'submittal'       => 'Submittal',
        'other'           => 'Other',
    ];

    /**
     * Auto-assign sequential RFI numbers per project on create.
     */
    protected static function booted(): void
    {
        static::creating(function (self $rfi) {
            if (empty($rfi->rfi_number) && $rfi->project_id) {
                $rfi->rfi_number = self::nextRfiNumberFor($rfi->project_id);
            }
        });
    }

    public static function nextRfiNumberFor(int $projectId): string
    {
        $last = self::withTrashed()
            ->where('project_id', $projectId)
            ->where('rfi_number', 'like', 'RFI-%')
            ->orderByDesc('id')
            ->value('rfi_number');

        $n = 1;
        if ($last && preg_match('/RFI-(\d+)/', $last, $m)) {
            $n = (int) $m[1] + 1;
        }

        return sprintf('RFI-%04d', $n);
    }

    /**
     * True when the answer is past the "needed by" date and not yet responded.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->needed_by || in_array($this->status, ['answered', 'closed'], true)) {
            return false;
        }
        return $this->needed_by->lt(now()->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::$priorityLabels[$this->priority] ?? $this->priority;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::$categoryLabels[$this->category] ?? $this->category;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * RFI document attachments — sketches, markups, referenced drawings, etc.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
