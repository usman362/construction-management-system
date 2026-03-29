<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $table = 'payroll_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }
}
