<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotSubstitute extends Model
{
    protected $fillable = ['slot_id', 'product_id', 'is_default'];

    public function slot()
    {
        return $this->belongsTo(ProductRecipeSlot::class, 'slot_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
