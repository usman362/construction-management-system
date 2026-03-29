<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'material_id',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_cost',
        'total_cost',
        'received_quantity',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    /**
     * Get the purchase order that this item belongs to.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the material associated with this item.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
