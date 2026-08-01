<?php

namespace App\Filament\Admin\Resources\SalesReturns\Schemas;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SalesReturnNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalesReturnsForm
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
     * How much of this sale item is still eligible to be returned:
     * originally sold qty, minus qty already tied up in any return that
     * isn't rejected (pending returns reserve the qty too, so two
     * concurrent returns can't both claim the same units).
     */
    public static function returnableQty(SaleItem $item, ?int $excludingReturnItemId = null): float
    {
        $alreadyReserved = $item->returns()
            ->when($excludingReturnItemId, fn ($q) => $q->where('id', '!=', $excludingReturnItemId))
            ->whereHas('salesReturn', fn ($q) => $q->where('status', '!=', 'rejected'))
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
                ->default(fn () => SalesReturnNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('sale_id')
                ->label('Original Invoice')
                ->options(fn () => Sale::with('customer')
                    ->latest('date')
                    ->get()
                    ->mapWithKeys(fn (Sale $sale) => [
                        $sale->id => $sale->sale_inv_no . ' — ' . ($sale->customer?->customer_name ?? 'Unknown'),
                    ]))
                ->searchable()
                ->live()
                ->required()
                ->disabledOn('edit')
                ->afterStateUpdated(fn ($set) => $set('items', [])),

            Select::make('reason')
                ->options([
                    'damaged' => 'Damaged / Defective',
                    'wrong_item' => 'Wrong Item Delivered',
                    'customer_changed_mind' => 'Customer Changed Mind',
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
                    Hidden::make('cost_price'),
                    Hidden::make('max_returnable'),

                    Select::make('sale_item_id')
                        ->label('Item')
                        ->columnSpan(3)
                        ->options(function ($get) {
                            $saleId = $get('../../sale_id');
                            if (! $saleId) return [];

                            return SaleItem::where('sale_id', $saleId)
                                ->with('product')
                                ->get()
                                ->mapWithKeys(function (SaleItem $item) {
                                    $returnable = self::returnableQty($item);
                                    $label = ($item->part_no ? "{$item->part_no} — " : '')
                                        . ($item->product?->name ?? 'Unknown')
                                        . " (sold {$item->qty}, returnable {$returnable})";

                                    return [$item->id => $label];
                                });
                        })
                        ->searchable()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function ($state, $set) {
                            $item = SaleItem::find($state);
                            if (! $item) return;

                            $returnable = self::returnableQty($item);

                            $set('product_id', $item->product_id);
                            $set('part_no', $item->part_no);
                            $set('brand', $item->brand);
                            $set('price', self::formatCurrency($item->price));
                            $set('cost_price', $item->cost_price);
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
                        ->label('Unit Price')
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