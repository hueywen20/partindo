<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\SaleInvoiceNumberService;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class PurchaseOrder extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'po_no',
        'date',
        'quotation_id',
        'customer_id',
        'supplier_id',
        'status',
        // 'sent_via_whatsapp_at',
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

    // ─── Status helpers ───────────────────────────────────────────────────────

    /**
     * Mark as sent via WhatsApp. Sets status → 'open' (if still draft) and stamps timestamp.
     */
    // public function markSentViaWhatsapp(): void
    // {
    //     $this->update([
    //         'sent_via_whatsapp_at' => now(),
    //     ]);
    // }

    /**
     * Build the WhatsApp share URL for this PO.
     */
    // public function whatsappUrl(): string
    // {
    //     $customerPhone = $this->customer?->phone ?? '';
    //     $phone = preg_replace('/\D/', '', $customerPhone);

    //     $message = "Halo, berikut adalah Purchase Order kami *{$this->po_no}*.\n"
    //              . "Tanggal: {$this->date->format('d/m/Y')}\n\n"
    //              . "Mohon konfirmasi penerimaan PO ini.";

    //     return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    // }

    // ─── Convert PO → Sales Invoice ──────────────────────────────────────────

    public function convertToSale(): Sale
    {
        $sale = Sale::create([
            'sale_inv_no'       => SaleInvoiceNumberService::generate(),
            'date'              => now(),
            'customer_id'       => $this->customer_id,
            'purchase_order_id' => $this->id,
            'quotation_id'      => $this->quotation_id,
            'tax'               => $this->tax,
            'discount'          => $this->discount,
            'grand_total'       => $this->grand_total,
            'final_total'       => $this->final_total,
        ]);

        foreach ($this->items()->with('components')->get() as $item) {
            $saleItem = $sale->items()->create([
                'product_id' => $item->product_id,
                'part_no'    => $item->part_no,
                'brand'      => $item->brand,
                'qty'        => $item->qty,
                'price'      => $item->price,
                'total'      => $item->total,
                'notes'      => $item->notes,
            ]);

            // Carry component substitution choices from PO → sale
            foreach ($item->components as $comp) {
                $saleItem->components()->create([
                    'slot_id'           => $comp->slot_id,
                    'chosen_product_id' => $comp->chosen_product_id,
                    'qty_used'          => $comp->qty_used,
                ]);
            }
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

    protected function casts(): array
    {
        return [
            'date'                 => 'date',
            'sent_via_whatsapp_at' => 'datetime',
        ];
    }
}