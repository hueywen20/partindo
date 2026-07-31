<?php

namespace App\Services;

use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

class SalesReturnNumberService
{
    public static function generate(): string
    {
        $prefix = 'CR-' . date('my') . '-';

        return DB::transaction(function () use ($prefix) {
            $last = SalesReturn::where('return_no', 'like', $prefix . '%')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($item) => (int) substr($item->return_no, -4))
                ->last();

            $next = $last ? ((int) substr($last->return_no, -4)) + 1 : 1;

            return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        });
    }
}