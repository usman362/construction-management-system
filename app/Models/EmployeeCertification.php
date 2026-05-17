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
        // 2026-05-12: per-milestone notice tracking (Phase 1 / Cert Expiry Alerts)
        'notice_60_sent_at',
        'notice_30_sent_at',
        'notice_7_sent_at',
        'notice_expired_sent_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'file_size' => 'integer',
        'notice_60_sent_at'      => 'datetime',
        'notice_30_sent_at'      => 'datetime',
        'notice_7_sent_at'       => 'datetime',
        'notice_expired_sent_at' => 'datetime',
    ];

    /**
     * 2026-05-12 (Brenda — Phase 1): when a cert is renewed (its expiry_date
     * changes), wipe the per-milestone notice flags so the new expiry cycle
     * gets its own round of notifications. Without this, a cert renewed
     * from 2025 to 2027 would never trigger a 60d / 30d / 7d email again.
     */
    protected static function booted(): void
    {
        static::updating(function (EmployeeCertification $cert) {
            if ($cert->isDirty('expiry_date')) {
                $cert->notice_60_sent_at      = null;
                $cert->notice_30_sent_at      = null;
                $cert->notice_7_sent_at       = null;
                $cert->notice_expired_sent_at = null;
            }
        });
    }

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
