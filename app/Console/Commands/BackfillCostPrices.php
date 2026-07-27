<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCostPrices extends Command
{
    protected $signature = 'costs:backfill';

    protected $description = 'Recompute product avg_cost (tax-inclusive) from purchase history, then refresh cost_price on all sale items.';

    public function handle(): int
    {
        $this->info('Recomputing avg_cost per product from purchase history (tax-inclusive)...');

        DB::transaction(function () {
            // Reset, then replay every purchase item in chronological order per product,
            // exactly like the observer would have, but with the tax-inclusive price.
            Product::query()->update(['avg_cost' => 0]);

            $runningStock = []; // product_id => stock used purely for this recompute
            $runningValue = []; // product_id => total value used purely for this recompute

            PurchaseItem::query()
                ->with('purchase')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->orderBy('purchases.date')
                ->orderBy('purchase_items.id')
                ->select('purchase_items.*')
                ->chunkById(200, function ($items) use (&$runningStock, &$runningValue) {
                    foreach ($items as $item) {
                        $qty = (float) ($item->qty ?? 0);
                        if ($qty <= 0) continue;

                        $taxRate = (float) ($item->purchase->tax ?? 0);
                        $price = round((float) ($item->price ?? 0) * (1 + ($taxRate / 100)), 2);

                        $productId = $item->product_id;
                        $prevStock = $runningStock[$productId] ?? 0;
                        $prevValue = $runningValue[$productId] ?? 0;

                        $newStock = $prevStock + $qty;
                        $newValue = $prevValue + ($price * $qty);

                        $runningStock[$productId] = $newStock;
                        $runningValue[$productId] = $newValue;
                    }
                }, 'purchase_items.id', 'id');

            foreach ($runningStock as $productId => $stock) {
                $avgCost = $stock > 0 ? $runningValue[$productId] / $stock : 0;
                Product::whereKey($productId)->update(['avg_cost' => round($avgCost, 2)]);
            }
        });

        $this->info('Refreshing cost_price on all existing sale items to match current avg_cost...');

        SaleItem::query()->with('product')->chunkById(200, function ($items) {
            foreach ($items as $item) {
                $item->updateQuietly(['cost_price' => $item->product->avg_cost ?? 0]);
            }
        });

        $this->info('Done. Cost/profit figures should now be accurate.');

        return self::SUCCESS;
    }
}