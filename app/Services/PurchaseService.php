<?php

// namespace App\Services;

// class PurchaseService
// {
//     /**
//      * Create a new class instance.
//      */
//     public function __construct()
//     {
//         //
//     }
// }

namespace App\Services;

use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $purchase = Purchase::create([
                'date' => $data['date'],
                'reference_no' => $data['reference_no'] ?? null,
            ]);

            foreach ($data['items'] as $item) {

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'] ?? 0,
                ]);

                // 🔥 STOCK IN LOGIC
                Product::where('id', $item['product_id'])
                    ->increment('stock', $item['qty']);
            }

            return $purchase;
        });
    }
}