<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipeSlot extends Model
{
    protected $fillable = [
        'composite_product_id', 'slot_name', 'quantity', 'is_required',
    ];

    public function composite()
    {
        return $this->belongsTo(Product::class, 'composite_product_id');
    }

    public function substitutes()
    {
        return $this->hasMany(SlotSubstitute::class, 'slot_id');
    }

    public function defaultSubstitute()
    {
        return $this->hasOne(SlotSubstitute::class, 'slot_id')
                    ->where('is_default', true);
    }

}
