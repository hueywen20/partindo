<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItemComponent extends Model
{
    //
     protected $fillable = [
        'sale_item_id', 'slot_id', 'chosen_product_id', 'qty_used',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function slot()
    {
        return $this->belongsTo(ProductRecipeSlot::class, 'slot_id');
    }

    public function chosenProduct()
    {
        return $this->belongsTo(Product::class, 'chosen_product_id');
    }

}
