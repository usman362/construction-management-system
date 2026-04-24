<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LienWaiver extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'project_id',
        'vendor_id',
        'commitment_id',
        'type',
        'amount',
        'through_date',
        'received_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'through_date'  => 'date',
        'received_date' => 'date',
    ];

    /**
     * Human-friendly labels for the 4 standard waiver types.
     */
    public static array $typeLabels = [
        'conditional_progress'   => 'Conditional — Progress',
        'unconditional_progress' => 'Unconditional — Progress',
        'conditional_final'      => 'Conditional — Final',
        'unconditional_final'    => 'Unconditional — Final',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Signed waiver document attachments (PDF scans of the executed waiver).
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
