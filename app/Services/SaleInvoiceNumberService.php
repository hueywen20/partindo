<?php

namespace App\Services;

use App\Models\Sale;

class SaleInvoiceNumberService
{
    /**
     * Create a new class instance.
     */
    public static function generate(): string
    {
        // $prefix = 'INV-' . now()->format('Ymd') . '-';
        $prefix = 'INV-' . date('my') . '-';
        $last = Sale::where('sale_inv_no', 'like', $prefix . '%')
            ->orderByDesc('sale_inv_no')
            ->value('sale_inv_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function __construct()
    {
        //
    }
}
