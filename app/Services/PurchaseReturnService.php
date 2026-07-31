<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    public static function approve(PurchaseReturn $return, User $approver): void
    {
        if (! $return->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending return can be approved.',
            ]);
        }

        DB::transaction(function () use ($return, $approver) {
            $return->loadMissing('items.product');

            foreach ($return->items as $item) {
                self::removeFromStock($item);
            }

            $return->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });
    }

    public static function reject(PurchaseReturn $return, User $approver): void
    {
        if (! $return->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending return can be rejected.',
            ]);
        }

        $return->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Admin-only correction path: undo an approved return's stock/cost
     * effects and send it back to pending.
     */
    public static function revert(PurchaseReturn $return, User $approver): void
    {
        if (! $return->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Only an approved return can be reverted.',
            ]);
        }

        DB::transaction(function () use ($return) {
            $return->loadMissing('items.product');

            foreach ($return->items as $item) {
                self::addBackToStock($item);
            }

            $return->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });
    }

    /**
     * Goods physically leave us back to the supplier: stock goes down,
     * and the weighted-average cost is recalculated as if that stock
     * had never been part of it — same formula as
     * PurchaseItemObserver::deleted(), using this return item's own
     * tax-inclusive price snapshot rather than the (possibly since
     * changed) current purchase item price.
     */
    private static function removeFromStock(Product $returnItem): void
    {
        $product = $returnItem->product;
        $qty = (float) $returnItem->qty;
        $price = (float) $returnItem->price;

        if ($qty <= 0) {
            return;
        }

        $currentStock = (float) $product->stock;
        $currentAvg = (float) $product->avg_cost;

        $newStock = $currentStock - $qty;
        $newValue = ($currentAvg * $currentStock) - ($price * $qty);
        $newAvg = $newStock > 0 ? $newValue / $newStock : 0;

        $product->update([
            'avg_cost' => round(max(0, $newAvg), 2),
            'stock' => max(0, $newStock),
        ]);
    }

    /**
     * Reverses removeFromStock() — used when an approved return is
     * reverted back to pending.
     */
    private static function addBackToStock(Product $returnItem): void
    {
        $product = $returnItem->product;
        $qty = (float) $returnItem->qty;
        $price = (float) $returnItem->price;

        if ($qty <= 0) {
            return;
        }

        $currentStock = (float) $product->stock;
        $currentAvg = (float) $product->avg_cost;

        $newStock = $currentStock + $qty;
        $newValue = ($currentAvg * $currentStock) + ($price * $qty);
        $newAvg = $newStock > 0 ? $newValue / $newStock : $price;

        $product->update([
            'avg_cost' => round($newAvg, 2),
            'stock' => $newStock,
        ]);
    }
}