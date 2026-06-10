<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Sale extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'date',
        'sale_inv_no',
        'customer_id',
        'quotation_id',
        'purchase_order_id',
        'tax',
        'discount',
        'grand_total',
        'final_total',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // ─── Recalculate totals ───────────────────────────────────────────────────

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        $subtotal = $this->items->sum(fn ($item) => $item->qty * $item->price);
        $tax      = $subtotal * (($this->tax ?? 0) / 100);
        $final    = max(0, $subtotal + $tax - ($this->discount ?? 0));

        $this->updateQuietly([
            'grand_total' => $subtotal,
            'final_total' => $final,
        ]);
    }
}