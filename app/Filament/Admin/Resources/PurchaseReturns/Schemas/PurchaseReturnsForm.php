<?php

namespace App\Filament\Admin\Resources\PurchaseReturns\Schemas;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\PurchaseReturnNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseReturnsForm
{
    public static function parseNumber(mixed $value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        $cleaned = str_replace(['.', ','], ['', '.'], (string) $value);
        return (float) $cleaned;
    }

    public static function formatCurrency(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    /**
     * How much of this purchase item is still eligible to be returned to
     * the supplier: originally purchased qty, minus qty already tied up
     * in any return that isn't rejected.
     */
    public static function returnableQty(PurchaseItem $item, ?int $excludingReturnItemId = null): float
    {
        $alreadyReserved = $item->returns()
            ->when($excludingReturnItemId, fn ($q) => $q->where('id', '!=', $excludingReturnItemId))
            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->sum('qty');

        return max(0, (float) $item->qty - (float) $alreadyReserved);
    }

    public static function recalculate(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item, $index) use ($set) {
            $qty   = self::parseNumber($item['qty']   ?? 0);
            $price = self::parseNumber($item['price'] ?? 0);
            $total = $qty * $price;
            $set("items.$index.total", self::formatCurrency($total));
            return $total;
        });

        $taxRate  = self::parseNumber($get('tax')      ?? 0);
        $discount = self::parseNumber($get('discount') ?? 0);
        $tax      = $subtotal * ($taxRate / 100);
        $final    = max(0, $subtotal + $tax - $discount);

        $set('grand_total', self::formatCurrency($subtotal));
        $set('final_total', self::formatCurrency($final));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('return_no')
                ->label('Return No')
                ->default(fn () => PurchaseReturnNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('purchase_id')
                ->label('Original Purchase')
                ->options(fn () => Purchase::with('supplier')
                    ->latest('date')
                    ->get()
                    ->mapWithKeys(fn (Purchase $purchase) => [
                        $purchase->id => $purchase->purchase_inv_no . ' — ' . ($purchase->supplier?->supplier_name ?? 'Unknown'),
                    ]))
                ->searchable()
                ->live()
                ->required()
                ->disabledOn('edit')
                ->afterStateUpdated(fn ($set) => $set('items', [])),

            Select::make('reason')
                ->options([
                    'damaged' => 'Damaged / Defective',
                    'wrong_item' => 'Wrong Item Received',
                    'overstock' => 'Overstock / Excess',
                    'other' => 'Other',
                ])
                ->native(false),

            Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),

            // ── Line items ────────────────────────────────────────────────────

            Repeater::make('items')
                ->relationship()
                ->schema([

                    Hidden::make('product_id'),
                    Hidden::make('part_no'),
                    Hidden::make('brand'),
                    Hidden::make('max_returnable'),

                    Select::make('purchase_item_id')
                        ->label('Item')
                        ->columnSpan(3)
                        ->options(function ($get) {
                            $purchaseId = $get('../../purchase_id');
                            if (! $purchaseId) return [];

                            return PurchaseItem::where('purchase_id', $purchaseId)
                                ->with('product')
                                ->get()
                                ->mapWithKeys(function (PurchaseItem $item) {
                                    $returnable = self::returnableQty($item);
                                    $label = ($item->part_no ? "{$item->part_no} — " : '')
                                        . ($item->product?->name ?? 'Unknown')
                                        . " (purchased {$item->qty}, returnable {$returnable})";

                                    return [$item->id => $label];
                                });
                        })
                        ->searchable()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function ($state, $set) {
                            $item = PurchaseItem::find($state);
                            if (! $item) return;

                            $returnable = self::returnableQty($item);

                            $set('product_id', $item->product_id);
                            $set('part_no', $item->part_no);
                            $set('brand', $item->brand);
                            // Tax-inclusive unit cost, matching how avg_cost is maintained
                            $taxRate = (float) ($item->purchase->tax ?? 0);
                            $taxInclusivePrice = round((float) $item->price * (1 + $taxRate / 100), 2);
                            $set('price', self::formatCurrency($taxInclusivePrice));
                            $set('max_returnable', $returnable);
                            $set('qty', $returnable > 0 ? min(1, $returnable) : 0);
                        }),

                    TextInput::make('qty')
                        ->label('Return Qty')
                        ->numeric()
                        ->minValue(0.01)
                        ->live(debounce: 300)
                        ->rule(fn ($get) => 'max:' . ($get('max_returnable') ?: 0))
                        ->helperText(fn ($get) => 'Max: ' . ($get('max_returnable') ?? '—'))
                        ->afterStateUpdated(fn ($set, $get) => self::recalculate($set, $get)),

                    TextInput::make('price')
                        ->label('Unit Cost')
                        ->currency()
                        ->disabled()
                        ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                        ->dehydrated(true),

                    TextInput::make('total')
                        ->label('Total')
                        ->currency()
                        ->disabled()
                        ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                        ->dehydrated(true),
                ])
                ->columns(6)
                ->columnSpanFull()
                ->addActionLabel('Add Item')
                ->afterStateUpdated(fn ($set, $get) => self::recalculate($set, $get))
                ->afterStateHydrated(fn ($set, $get) => self::recalculate($set, $get)),

            // ── Totals ────────────────────────────────────────────────────────

            TextInput::make('grand_total')
                ->label('Subtotal')
                ->currency()
                ->columnStart(2)
                ->disabled()
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->dehydrated(true),

            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)
                ->live(debounce: 300)
                ->columnStart(2)
                ->afterStateUpdated(fn ($set, $get) => self::recalculate($set, $get)),

            TextInput::make('discount')
                ->label('Discount (Rp)')
                ->currency()
                ->default(0)
                ->live(debounce: 300)
                ->columnStart(2)
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->afterStateUpdated(fn ($set, $get) => self::recalculate($set, $get)),

            TextInput::make('final_total')
                ->label('Final Total')
                ->currency()
                ->columnStart(2)
                ->disabled()
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->dehydrated(true),

        ]);
    }
}