<?php

namespace App\Observers;

use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $item): void {
        $qty = (float) ($item->qty ?? 0);
        if ($qty <= 0) return;

        $item->updateQuietly(['cost_price' => $item->product->avg_cost ?? 0]);

        if ($item->product->is_composite) {
            // Deduct each chosen component, not the composite itself
            foreach ($item->components as $component) {
                $component->chosenProduct
                        ->decrement('stock', $component->qty_used);
            }
        } else {
            $item->product->decrement('stock', $qty);
        }

        $item->sale->recalculateTotals();
    }

public function updated(SaleItem $item): void
{
    $oldQty = (float) ($item->getOriginal('qty') ?? 0);
    $newQty = (float) ($item->qty ?? 0);
    $delta  = $newQty - $oldQty;

    if ($item->product->is_composite) {
        // Recalculate each component by ratio
        foreach ($item->components as $component) {
            $slot       = $component->slot;
            $oldUsed    = $slot->quantity * $oldQty;
            $newUsed    = $slot->quantity * $newQty;
            $diff       = $newUsed - $oldUsed;
            // Update the snapshot
            $component->update(['qty_used' => $newUsed]);
            if ($diff > 0) {
                $component->chosenProduct->decrement('stock', $diff);
            } elseif ($diff < 0) {
                $component->chosenProduct->increment('stock', abs($diff));
            }
        }
    } else {
        if ($delta > 0)      $item->product->decrement('stock', $delta);
        elseif ($delta < 0)  $item->product->increment('stock', abs($delta));
    }

    if ($item->wasChanged('product_id') || $item->wasChanged('qty')) {
        $item->updateQuietly(['cost_price' => $item->product->avg_cost ?? 0]);
    }

    $item->sale->recalculateTotals();
}

public function deleted(SaleItem $item): void
{
    $qty = (float) ($item->qty ?? 0);
    if ($qty <= 0) return;

    if ($item->product->is_composite) {
        foreach ($item->components as $component) {
            $component->chosenProduct
                      ->increment('stock', $component->qty_used);
        }
    } else {
        $item->product->increment('stock', $qty);
    }

    $item->sale->recalculateTotals();

}

}