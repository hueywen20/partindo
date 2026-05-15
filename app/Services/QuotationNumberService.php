<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationNumberService
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
        $prefix = 'QT-' . date('my') . '-';
        $last   = Quotation::where('quotation_no', 'like', $prefix . '%')
            ->orderByDesc('quotation_no')
            ->value('quotation_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
