<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'project_id',
        'parent_po_id',
        'change_order_id',
        'vendor_id',
        'po_number',
        'description',
        'cost_code_id',
        'cost_type_id',
        'date',
        'delivery_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'status',
        'notes',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Get the project associated with this purchase order.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the vendor associated with this purchase order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the cost code associated with this purchase order.
     */
    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }

    /** The PO this one is a change order against, if any. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'parent_po_id');
    }

    /** Child / amendment POs hanging off this one (CO-style). */
    public function children(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'parent_po_id');
    }

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    /**
     * Get the line items for this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the user who issued this purchase order.
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the formatted total amount.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }
}
