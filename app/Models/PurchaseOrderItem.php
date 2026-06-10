<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'part_no',
        'brand',
        'category',
        'qty',
        'price',
        'total',
        'notes',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function components()
    {
        return $this->hasMany(PurchaseOrderItemComponent::class);
    }

    // ─── NO observer registered for this model ───────────────────────────────
    // Stock is only affected when a Sales Invoice (SaleItem) is created.
    // PurchaseOrderItems are a commitment to buy/fulfill, not a stock movement.
}