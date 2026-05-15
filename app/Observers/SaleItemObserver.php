<?php

namespace App\Observers;

use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $item): void
    {
        // $item->product()->decrement('stock', $item->qty);
        $qty = (float) ($item->qty ?? 0);
        if ($qty <= 0) return;

        // 1. Snapshot avg_cost at time of sale
        $item->updateQuietly([
            'cost_price' => $item->product->avg_cost ?? 0,
        ]);

        // 2. Decrement stock
        $item->product->decrement('stock', $qty);

        // 3. Recalculate sale totals now that items exist
        $item->sale->recalculateTotals();
    }

    public function updated(SaleItem $item): void
    {
        // Apply only the difference when qty changes
        // if ($item->wasChanged('qty')) {
        //     $old = $item->getOriginal('qty');
        //     $diff = $item->qty - $old;

        //     if ($diff > 0) {
        //         $item->product()->decrement('stock', $diff);
        //     } elseif ($diff < 0) {
        //         $item->product()->increment('stock', abs($diff));
        //     }
        // }

        // $item->updateQuietly([
        //     'cost_price' => $item->product->avg_cost,
        // ]);

        // $item->product->decrement('stock', $item->qty);

        $oldQty = (float) ($item->getOriginal('qty') ?? 0);
        $newQty = (float) ($item->qty ?? 0);
        $delta  = $newQty - $oldQty;

        if ($delta > 0) {
            $item->product->decrement('stock', $delta);
        } elseif ($delta < 0) {
            $item->product->increment('stock', abs($delta));
        }

        $item->sale->recalculateTotals();
    }

    public function deleted(SaleItem $item): void
    {
        // $item->product()->increment('stock', $item->qty);

        $qty = (float) ($saleItem->qty ?? 0);
        if ($qty > 0) {
            $item->product->increment('stock', $qty);
        }

        $item->sale->recalculateTotals();
    }
}