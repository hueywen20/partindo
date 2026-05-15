<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceNumberService
{
    public static function generate(): string
    {
        $prefix = 'PI-' . date('my') . '-';

        return DB::transaction(function () use ($prefix) {

        $lastInvoice = Purchase::where('purchase_inv_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->get()
            ->sortBy(function ($item) {
                return (int) substr($item->purchase_inv_no, -4);
            })
            ->last();

        $number = 1;

        if ($lastInvoice) {
            $number = ((int) substr($lastInvoice->purchase_inv_no, -4)) + 1;
        }

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
