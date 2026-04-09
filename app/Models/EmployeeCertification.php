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
     */
    public function getStatusAttribute(): string
    {
        if (!$this->expiry_date) {
            return 'valid';
        }
        if ($this->expiry_date->isPast()) {
            return 'expired';
        }
        if ($this->expiry_date->diffInDays(now()) <= 30) {
            return 'expiring_soon';
        }
        return 'valid';
    }
}
