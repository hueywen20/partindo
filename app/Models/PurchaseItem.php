<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    //
    protected $fillable = [
        'purchase_id',
        'product_id',
        'category', // ← add this
        'qty',
        'price',
        // add other fields you use
    ];

     public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
