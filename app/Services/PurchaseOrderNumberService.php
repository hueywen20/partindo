<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderNumberService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function generate(): string
    {
        // $prefix = 'PO-' . date('my') . '-';
        // $last   = PurchaseOrder::where('purchase_order_no', 'like', $prefix . '%')
        //     ->orderByDesc('purchase_order_no')
        //     ->value('purchase_order_no');

        // $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        // return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
         $prefix = 'PO-' . date('my') . '-';
 
        return DB::transaction(function () use ($prefix) {
            $last = PurchaseOrder::where('po_no', 'like', $prefix . '%')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($item) => (int) substr($item->po_no, -4))
                ->last();
 
            $number = $last ? ((int) substr($last->po_no, -4)) + 1 : 1;
 
            return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
}
