<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($prefix) {
            $last = Quotation::where('quotation_no', 'like', $prefix . '%')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($item) => (int) substr($item->quotation_no, -4))
                ->last();

            $next = $last ? ((int) substr($last->quotation_no, -4)) + 1 : 1;

            return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        });
    }
}