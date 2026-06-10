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
        'excavator_model',
        'status', 'sent_via_whatsapp_at',
        'tax', 'discount', 'grand_total', 'final_total',
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

    // ─── Status helpers ───────────────────────────────────────────────────────

    /**
     * Mark as sent via WhatsApp. Sets status → 'sent' and stamps the timestamp.
     */
    // public function markSentViaWhatsapp(): void
    // {
    //     $this->update([
    //         'status'                => 'sent',
    //         'sent_via_whatsapp_at'  => now(),
    //     ]);
    // }

    /**
     * Check whether the valid_until date has passed and auto-expire if needed.
     * Call this from a scheduled command or on read.
     */
    // public function checkAndExpire(): void
    // {
    //     if (
    //         ! in_array($this->status, ['accepted', 'expired'])
    //         && $this->valid_until
    //         && $this->valid_until->isPast()
    //     ) {
    //         $this->update(['status' => 'expired']);
    //     }
    // }

    /**
     * Build the WhatsApp share URL for this quotation.
     * Attach the print URL so the customer can open it directly.
     */
    // public function whatsappUrl(): string
    // {
    //     $customerPhone = $this->customer?->phone ?? '';
    //     // Strip non-digit chars; ensure international format
    //     $phone = preg_replace('/\D/', '', $customerPhone);

    //     $printUrl = route('quotations.print', $this);
    //     $message  = "Halo, berikut adalah penawaran kami *{$this->quotation_no}*.\n"
    //               . "Berlaku hingga: {$this->valid_until->format('d/m/Y')}\n\n"
    //               . "Detail penawaran: {$printUrl}";

    //     return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    // }

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

        foreach ($this->items()->with('components')->get() as $item) {
            $poItem = $purchaseOrder->items()->create([
                'product_id' => $item->product_id,
                'part_no'    => $item->part_no,
                'brand'      => $item->brand,
                'qty'        => $item->qty,
                'price'      => $item->price,
                'total'      => $item->total,
                'notes'      => $item->notes,
            ]);

            // Carry component substitution choices from quotation → PO
            foreach ($item->components as $comp) {
                $poItem->components()->create([
                    'slot_id'           => $comp->slot_id,
                    'chosen_product_id' => $comp->chosen_product_id,
                    'qty_used'          => $comp->qty_used,
                ]);
            }
        }

        $this->update([
            'status'             => 'accepted',
            'converted_to_po_id' => $purchaseOrder->id,
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

            // Carry component substitution choices from quotation → sale
            foreach ($item->components as $comp) {
                $saleItem->components()->create([
                    'slot_id'           => $comp->slot_id,
                    'chosen_product_id' => $comp->chosen_product_id,
                    'qty_used'          => $comp->qty_used,
                ]);
            }
        }

        $this->update([
            'status'               => 'accepted',
            'converted_to_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    protected function casts(): array
    {
        return [
            'date'                 => 'date',
            'valid_until'          => 'date',
            'sent_via_whatsapp_at' => 'datetime',
        ];
    }
}