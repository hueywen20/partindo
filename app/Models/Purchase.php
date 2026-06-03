<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Purchase extends Model implements Auditable
{
    use AuditableTrait;

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
            $purchase->recalculateTotals();
        });

        static::deleting(function ($purchase) {
            $purchase->items->each(fn ($item) => $item->delete());
        });
    }

    public function recalculateTotals(): void
    {
        $this->load('items');

        $subtotal = $this->items->sum(function ($item) {
            return (float) $item->qty * (float) $item->price;
        });

        $tax = $subtotal * ((float) ($this->tax ?? 0) / 100);
        $discount = (float) ($this->discount ?? 0);
        $final = max(0, $subtotal + $tax - $discount);

        $this->updateQuietly([
            'grand_total' => round($subtotal, 2),
            'final_total' => round($final, 2),
        ]);
    }
    
}
