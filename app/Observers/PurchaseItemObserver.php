<?php

namespace App\Observers;

use App\Models\PurchaseItem;

class PurchaseItemObserver
{
    /**
     * Handle the PurchaseItem "created" event.
     */
    public function created(PurchaseItem $purchaseItem): void
    {
        //
        // $product = $purchaseItem->product;

        // $product->increment('stock', $purchaseItem->quantity);

        
        // $this->recalculateAvgCost($purchaseItem->product, $purchaseItem->quantity, $purchaseItem->price);
        // $purchaseItem->product->increment('stock', $purchaseItem->quantity);

        $qty   = (float) ($purchaseItem->qty ?? 0);
        $price = (float) ($purchaseItem->price ?? 0);

        if ($qty <= 0) return;

        $this->recalculateAvgCost($purchaseItem->product, $qty, $price);
        $purchaseItem->product->increment('stock', $qty);
        $purchaseItem->purchase->recalculateTotals();
    }

    /**
     * Handle the PurchaseItem "updated" event.
     */
    public function updated(PurchaseItem $purchaseItem): void
    {
        $oldQty   = (float) ($purchaseItem->getOriginal('qty') ?? 0);
        $oldPrice = (float) ($purchaseItem->getOriginal('price') ?? 0);
        $newQty   = (float) ($purchaseItem->qty ?? 0);
        $newPrice = (float) ($purchaseItem->price ?? 0);

        if ($oldQty === $newQty && $oldPrice === $newPrice) return;

        $product        = $purchaseItem->product;
        $currentStock   = (float) $product->stock;
        $currentAvgCost = (float) $product->avg_cost;

        $stockWithoutOld = $currentStock - $oldQty;
        $valueWithoutOld = max(0, ($currentAvgCost * $currentStock) - ($oldPrice * $oldQty));

        $newStock = $stockWithoutOld + $newQty;
        $newValue = $valueWithoutOld + ($newPrice * $newQty);
        $newAvg   = $newStock > 0 ? $newValue / $newStock : 0;

        $product->update([
            'avg_cost' => round($newAvg, 2),
            'stock'    => max(0, $newStock),
        ]);

        $purchaseItem->purchase->recalculateTotals();
    }

    /**
     * Handle the PurchaseItem "deleted" event.
     */
    public function deleted(PurchaseItem $purchaseItem): void
    {
        //
        // $purchaseItem->product->decrement('stock', $purchaseItem->quantity);
    
        $qty   = (float) ($purchaseItem->qty ?? 0);
        $price = (float) ($purchaseItem->price ?? 0);

        if ($qty <= 0) return;

        $product      = $purchaseItem->product;
        $currentStock = (float) $product->stock;
        $currentAvg   = (float) $product->avg_cost;

        $newStock = $currentStock - $qty;
        $newValue = ($currentAvg * $currentStock) - ($price * $qty);
        $newAvg   = $newStock > 0 ? $newValue / $newStock : 0;

        $product->update([
            'avg_cost' => round(max(0, $newAvg), 2),
            'stock'    => max(0, $newStock),
        ]);

        $purchaseItem->purchase->recalculateTotals();

    }

    /**
     * Handle the PurchaseItem "restored" event.
     */
    public function restored(PurchaseItem $purchaseItem): void
    {
        //
    }

    /**
     * Handle the PurchaseItem "force deleted" event.
     */
    public function forceDeleted(PurchaseItem $purchaseItem): void
    {
        //
    }

    private function recalculateAvgCost($product, float $incomingQty, float $incomingPrice): void
    {
        $currentStock = (float) $product->stock;
        $currentAvg   = (float) $product->avg_cost;

        $newStock = $currentStock + $incomingQty;
        $newValue = ($currentAvg * $currentStock) + ($incomingPrice * $incomingQty);
        $newAvg   = $newStock > 0 ? $newValue / $newStock : $incomingPrice;

        $product->update(['avg_cost' => round($newAvg, 2)]);
    }
}
