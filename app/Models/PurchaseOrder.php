<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\SaleInvoiceNumberService;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_no',
        'date',
        'quotation_id',
        'customer_id',
        'supplier_id',
        'status',
        'tax',
        'discount',
        'grand_total',
        'final_total',
        'converted_to_sale_id',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    // ─── Convert PO → Sales Invoice ──────────────────────────────────────────

    public function convertToSale(): Sale
    {
        $sale = Sale::create([
            'sale_inv_no' => SaleInvoiceNumberService::generate(),
            'date'        => now(),
            'customer_id' => $this->customer_id,
            'tax'         => $this->tax,
            'discount'    => $this->discount,
            'grand_total' => $this->grand_total,
            'final_total' => $this->final_total,
        ]);

        foreach ($this->items as $item) {
            $sale->items()->create([
                'product_id' => $item->product_id,
                'qty'        => $item->qty,
                'price'      => $item->price,
                'total'      => $item->total,
            ]);
        }

        $this->update([
            'status'               => 'fulfilled',
            'converted_to_sale_id' => $sale->id,
        ]);

        // Also mark the originating quotation as accepted if linked
        if ($this->quotation_id) {
            $this->quotation?->update([
                'status'               => 'accepted',
                'converted_to_sale_id' => $sale->id,
            ]);
        }

        return $sale;
    }

    // ─── Auto-recalculate totals on save ─────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(function ($po) {
            $po->load('items');

            $subtotal = $po->items->sum(fn ($item) => $item->qty * $item->price);
            $taxRate  = $po->tax ?? 0;
            $tax      = $subtotal * ($taxRate / 100);
            $discount = $po->discount ?? 0;
            $final    = max(0, $subtotal + $tax - $discount);

            $po->updateQuietly([
                'grand_total' => $subtotal,
                'final_total' => $final,
            ]);
        });

        static::deleting(function ($po) {
            $po->items->each(fn ($item) => $item->delete());
        });
    }
}