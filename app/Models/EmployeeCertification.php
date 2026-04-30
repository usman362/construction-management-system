<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCertification extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'certification_number',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'file_size' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Returns 'valid', 'expiring_soon' (within 30 days), or 'expired'.
     *
     * 2026-05-01 BUG FIX (Brenda): "they are good for two years and the
     * system is telling me they are about to expire."
     *
     * Carbon 3's diffInDays() is *signed* — it returns a NEGATIVE number
     * for future dates (e.g. -730 for two years out). The old check
     *     $this->expiry_date->diffInDays(now()) <= 30
     * matched -730 <= 30 (TRUE), so every cert with any future expiry
     * was being flagged as "Soon". Use a directional diff from today
     * to expiry: positive = future, negative = past. Only the 0..30
     * window is "Soon".
     */
    public function getStatusAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'valid';
        }
        // Days FROM today TO expiry. Positive = future, negative = past.
        // startOfDay() on both sides so a cert expiring today reads as 0
        // (Soon) rather than being thrown into "expired" by an off-by-hours.
        $daysUntilExpiry = now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
        if ($daysUntilExpiry < 0) {
            return 'expired';
        }
        if ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }
        return 'valid';
    }
}
