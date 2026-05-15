<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['date',
    'sale_inv_no',
    'customer_id',
    // 'reference_no',
    'tax',
    'discount',
    'grand_total',
    'final_total',];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // protected static function booted()
    // {
    //     static::saved(function ($sale) {

    //         $sale->load('items');

    //         $subtotal = $sale->items->sum(fn ($item) => $item->qty * $item->price);

    //         $taxRate = $sale->tax ?? 0;
    //         $tax = $subtotal * ($taxRate / 100);
    //         $discount = $sale->discount ?? 0;
    //         $final = max(0, $subtotal + $tax - $discount);

    //         $sale->updateQuietly([
    //             'grand_total' => $subtotal,
    //             'final_total' => $final,
    //         ]);
    //     });
    // }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        $subtotal = $this->items->sum(fn($item) => $item->qty * $item->price);
        $tax      = $subtotal * (($this->tax ?? 0) / 100);
        $final    = max(0, $subtotal + $tax - ($this->discount ?? 0));

        $this->updateQuietly([
            'grand_total' => $subtotal,
            'final_total' => $final,
        ]);
    }

}
