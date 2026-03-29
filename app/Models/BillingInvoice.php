<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoice extends Model
{
    protected $table = 'billing_invoices';

    protected $fillable = [
        'project_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'description',
        'notes',
        'billing_period_start',
        'billing_period_end',
        'labor_amount',
        'material_amount',
        'equipment_amount',
        'subcontractor_amount',
        'other_amount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'status',
        'sent_date',
        'paid_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'labor_amount' => 'decimal:2',
        'material_amount' => 'decimal:2',
        'equipment_amount' => 'decimal:2',
        'subcontractor_amount' => 'decimal:2',
        'other_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_date' => 'date',
        'paid_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
