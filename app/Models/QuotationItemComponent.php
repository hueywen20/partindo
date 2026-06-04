<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItemComponent extends Model
{
    //
    protected $fillable = ['quotation_item_id', 'slot_id', 'chosen_product_id', 'qty_used'];

    public function chosenProduct() { return $this->belongsTo(Product::class, 'chosen_product_id'); }
    public function slot() { return $this->belongsTo(ProductRecipeSlot::class, 'slot_id'); }
    
}
