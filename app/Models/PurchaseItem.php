<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'category',
        'part_no',
        'brand',
        'qty',
        'price',
        'grand_total',
    ];

    protected static function booted(): void
    {
        // Strip the "productId::codeLine" encoding before saving —
        // only the raw code line should be stored in the DB.
        static::saving(function ($item) {
            if ($item->part_no && str_contains($item->part_no, '::')) {
                [, $codeLine] = explode('::', $item->part_no, 2);
                $item->part_no = $codeLine;
            }
        });
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}