<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoice extends Model
{
    use Auditable;

    protected $table = 'billing_invoices';

    protected $fillable = [
        'project_id',
        // 2026-04-30 (Brenda): vendor invoices link back to an internal PO
        // (purchase_order_id) and/or carry a free-text vendor PO number
        // (po_reference) for cross-reference at payment time.
        'purchase_order_id',
        'po_reference',
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
        'retainage_percent',
        'retainage_amount',
        'retainage_released',
        'retainage_released_date',
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
        'retainage_percent' => 'decimal:2',
        'retainage_amount' => 'decimal:2',
        'retainage_released' => 'boolean',
        'retainage_released_date' => 'date',
        'total_amount' => 'decimal:2',
        'sent_date' => 'date',
        'paid_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Net billable after retainage withholding — what the owner actually pays this period.
     */
    public function getNetBilledAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->retainage_amount;
    }
}
