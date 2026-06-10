<?php

use App\Models\SaleItem;

$items = SaleItem::with('product.brandModel')->whereNull('part_no')->get();

foreach ($items as $item) {
    $product = $item->product;
    if (! $product) continue;

    $partNo = collect(explode("\n", $product->code ?? ''))
        ->map(fn ($l) => trim($l))
        ->filter()
        ->first();

    $item->update([
        'part_no' => $partNo ? "{$product->id}::{$partNo}" : null,
        'brand'   => $product->brandModel?->name,
    ]);
}

echo "Done. Updated " . $items->count() . " items.\n";
