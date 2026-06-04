<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\PurchaseOrderNumberService;
use App\Services\SaleInvoiceNumberService;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Quotation extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'quotation_no', 'date', 'valid_until', 'customer_id',
        'status', 'tax', 'discount', 'grand_total', 'final_total',
        'converted_to_sale_id',
        'converted_to_po_id',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedSale()
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    public function convertedPO()
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_to_po_id');
    }

    // ─── Convert Quotation → Purchase Order ──────────────────────────────────

    public function convertToPO(): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::create([
            'po_no'        => PurchaseOrderNumberService::generate(),
            'date'         => now(),
            'quotation_id' => $this->id,
            'customer_id'  => $this->customer_id,
            'status'       => 'open',
            'tax'          => $this->tax,
            'discount'     => $this->discount,
            'grand_total'  => $this->grand_total,
            'final_total'  => $this->final_total,
        ]);

        foreach ($this->items as $item) {
            $purchaseOrder->items()->create([
                'product_id' => $item->product_id,
                'qty'        => $item->qty,
                'price'      => $item->price,
                'total'      => $item->total,
            ]);
        }

        $this->update([
            'status'             => 'accepted',
            'converted_to_po_id' => $purchaseOrder->id,  // correct FK → purchase_orders
        ]);

        return $purchaseOrder;
    }

    // ─── Convert Quotation → Sales Invoice ───────────────────────────────────

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
            'status'               => 'accepted',
            'converted_to_sale_id' => $sale->id,  // correct FK → sales
        ]);

        return $sale;
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'valid_until' => 'date',
        ];
    }
}