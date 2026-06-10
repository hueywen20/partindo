<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItemComponent extends Model
{
    protected $fillable = [
        'purchase_order_item_id',
        'slot_id',
        'chosen_product_id',
        'qty_used',
    ];

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function chosenProduct()
    {
        return $this->belongsTo(Product::class, 'chosen_product_id');
    }

    public function slot()
    {
        return $this->belongsTo(ProductRecipeSlot::class, 'slot_id');
    }
}