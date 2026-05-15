<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    //
    protected $fillable = [
        'date',
        'supplier_id',
        'purchase_inv_no',
        'reference_no',
        'tax',
        'discount',
        'grand_total',
        'final_total',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    protected static function booted()
    {
        static::saved(function ($purchase) {
            // make sure items are loaded
            $purchase->load('items');

            $subtotal = $purchase->items->sum(function ($item) {
                return $item->qty * $item->price;
            });

            $taxRate = $purchase->tax ?? 0;
            $tax = $subtotal * ($taxRate / 100);

            $discount = $purchase->discount ?? 0;

            $final = max(0, $subtotal + $tax - $discount);

            $purchase->updateQuietly([
                'grand_total' => $subtotal,
                'final_total' => $final,
            ]);
        });

        static::deleting(function ($purchase) {
            $purchase->items->each(fn ($item) => $item->delete());
        });
    }
    
}

