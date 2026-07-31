<?php

namespace App\Services;

use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReturnService
{
    public static function approve(SalesReturn $return, User $approver): void
    {
        if (! $return->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending return can be approved.',
            ]);
        }

        DB::transaction(function () use ($return, $approver) {
            $return->loadMissing('items.saleItem.product', 'items.saleItem.components.slot');

            foreach ($return->items as $item) {
                self::restockItem($item);
            }

            $return->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $return->sale?->refreshPaymentStatus();
        });
    }

    public static function reject(SalesReturn $return, User $approver): void
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
     * Admin-only correction path: undo an approved return's stock/balance
     * effects and send it back to pending, in case it was approved by
     * mistake.
     */
    public static function revert(SalesReturn $return, User $approver): void
    {
        if (! $return->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Only an approved return can be reverted.',
            ]);
        }

        DB::transaction(function () use ($return) {
            $return->loadMissing('items.saleItem.product', 'items.saleItem.components.slot');

            foreach ($return->items as $item) {
                self::unstockItem($item);
            }

            $return->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $return->sale?->refreshPaymentStatus();
        });
    }

    private static function restockItem(SaleItem $returnItem): void
    {
        $saleItem = $returnItem->saleItem;
        $product = $returnItem->product;
        $qty = (float) $returnItem->qty;

        if ($qty <= 0) {
            return;
        }

        if ($saleItem && $product->is_composite) {
            self::adjustComposite($saleItem, $qty, increment: true);

            return;
        }

        $product->increment('stock', $qty);
    }

    private static function unstockItem(SaleItem$returnItem): void
    {
        $saleItem = $returnItem->saleItem;
        $product = $returnItem->product;
        $qty = (float) $returnItem->qty;

        if ($qty <= 0) {
            return;
        }

        if ($saleItem && $product->is_composite) {
            self::adjustComposite($saleItem, $qty, increment: false);

            return;
        }

        $product->decrement('stock', $qty);
    }

    /**
     * Composite products don't hold their own stock — the original sale
     * decremented each chosen component proportionally (see
     * SaleItemObserver). A return needs to reverse that same proportion,
     * scaled to how much of the original line is being returned.
     */
    private static function adjustComposite(SaleItem $saleItem, float $returnQty, bool $increment): void
    {
        $originalQty = (float) $saleItem->qty;

        if ($originalQty <= 0) {
            return;
        }

        $ratio = $returnQty / $originalQty;

        foreach ($saleItem->components as $component) {
            $amount = round((float) $component->qty_used * $ratio, 4);

            if ($amount <= 0) {
                continue;
            }

            if ($increment) {
                $component->chosenProduct?->increment('stock', $amount);
            } else {
                $component->chosenProduct?->decrement('stock', $amount);
            }
        }
    }
}