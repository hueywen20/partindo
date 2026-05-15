<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\PurchaseOrder;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'category',
        'qty',
        'price',
        'total',
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

    // ─── NO observer registered for this model ───────────────────────────────
    // Stock is only affected when a Sales Invoice (SaleItem) is created.
    // PurchaseOrderItems are a commitment to buy, not a stock movement.
}