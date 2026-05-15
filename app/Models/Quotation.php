<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Customer;
use App\Models\QuotationItem;
use App\Models\PurchaseOrder;
use App\Models\Sale;

class Quotation extends Model
{
    //
    protected $fillable = [
        'quotation_no', 'date', 'valid_until', 'customer_id',
        'status', 'tax', 'discount', 'grand_total', 'final_total',
        'converted_to_sale_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function convertToPO(): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::create([
            'date'        => now(),
            'customer_id' => $this->customer_id,
            'tax'         => $this->tax,
            'discount'    => $this->discount,
            'grand_total' => $this->grand_total,
            'final_total' => $this->final_total,
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
            'status'               => 'accepted',
            'converted_to_sale_id' => $purchaseOrder->id,
        ]);

        return $purchaseOrder;
    }

    public function convertToSale(): Sale
    {
        $sale = Sale::create([
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
            'converted_to_sale_id' => $sale->id,
        ]);

        return $sale;
    }
}
