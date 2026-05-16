<?php

namespace App\Observers;

use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $item): void
    {
        $qty = (float) ($item->qty ?? 0);
        if ($qty <= 0) return;

        // 1. Snapshot avg_cost at time of sale
        $item->updateQuietly([
            'cost_price' => $item->product->avg_cost ?? 0,
        ]);

        // 2. Decrement stock
        $item->product->decrement('stock', $qty);

        // 3. Recalculate sale totals
        $item->sale->recalculateTotals();
    }

    public function updated(SaleItem $item): void
    {
        $oldQty = (float) ($item->getOriginal('qty') ?? 0);
        $newQty = (float) ($item->qty ?? 0);
        $delta  = $newQty - $oldQty;

        // Adjust stock by the difference
        if ($delta > 0) {
            $item->product->decrement('stock', $delta);
        } elseif ($delta < 0) {
            $item->product->increment('stock', abs($delta));
        }

        // Re-snapshot cost_price if product changed
        if ($item->wasChanged('product_id') || $item->wasChanged('qty')) {
            $item->updateQuietly([
                'cost_price' => $item->product->avg_cost ?? 0,
            ]);
        }

        $item->sale->recalculateTotals();
    }

    public function deleted(SaleItem $item): void
    {
        $qty = (float) ($item->qty ?? 0);
        if ($qty > 0) {
            $item->product->increment('stock', $qty);
        }

        $item->sale->recalculateTotals();
    }
}