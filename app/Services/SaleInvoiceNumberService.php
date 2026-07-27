<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleInvoiceNumberService
{
    public static function generate(): string
    {
        $prefix = 'INV-' . date('my') . '-';

        return DB::transaction(function () use ($prefix) {
            $lastInvoice = Sale::where('sale_inv_no', 'like', $prefix . '%')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($item) => (int) substr($item->sale_inv_no, -4))
                ->last();

            $next = $lastInvoice ? ((int) substr($lastInvoice->sale_inv_no, -4)) + 1 : 1;

            return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        });
    }

    public function __construct()
    {
        //
    }
}
