<?php

namespace App\Filament\Admin\Resources\Purchases\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;

use App\Models\Product;
use App\Services\PurchaseInvoiceNumberService;

class PurchaseForm
{
    public static function parseNumber(mixed $value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        // Remove thousands separator (.) and replace decimal separator (,) with (.)
        $cleaned = str_replace(['.', ','], ['', '.'], (string) $value);
        return (float) $cleaned;
    }

    public static function recalculate(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item, $index) use ($set) {
            $qty = self::parseNumber($item['qty'] ?? 0);
            $price = self::parseNumber($item['price'] ?? 0);

            $total = $qty * $price;

            $set("items.$index.grand_total", number_format($total, 2, ',', '.'));

            return $total;
        });

        $taxRate = self::parseNumber($get('tax') ?? 0);
        $discount = self::parseNumber($get('discount') ?? 0);

        $tax = $subtotal * ($taxRate / 100);

        $final = max(0, $subtotal + $tax - $discount);

        $set('grand_total', number_format($subtotal, 2, ',', '.'));
        $set('final_total', number_format($final, 2, ',', '.'));
    }

    /**
     * Build a flat lookup of every code line → product across all products.
     * Returns array of [ 'code_line' => 'code_line — Product Name' ]
     * and a reverse map of [ 'code_line' => product_id ]
     */
    public static function allPartNoOptions(): array
    {
        return Product::whereNotNull('code')
            ->where('code', '!=', '')
            ->get(['id', 'name', 'code'])
            ->flatMap(function ($product) {
                return collect(explode("\n", $product->code))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->mapWithKeys(fn ($line) => [
                        // key = "productId::codeLine" so we can decode it on select
                        "{$product->id}::{$line}" => "{$line}",
                    ]);
            })
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // =====================
            // HEADER
            // =====================
            DatePicker::make('date')
                ->default(now())
                ->required(),

            TextInput::make('purchase_inv_no')
                ->default(fn () => PurchaseInvoiceNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            Select::make('supplier_id')
                ->label('Supplier')
                ->options(
                    \App\Models\Supplier::where('status', 1)
                        ->pluck('company_name', 'id')
                )
                ->searchable()
                ->required(),

            TextInput::make('reference_no')
                ->label('Invoice No')
                ->required()
                ->unique(ignoreRecord: true),

            // =====================
            // ITEMS
            // =====================
            Repeater::make('items')
                ->relationship()
                ->schema([

                    // ── Step 1: search / paste a part number ──────────────────
                    Select::make('part_no')
                        ->label('Part No')
                        ->columnSpan(3)
                        ->options(fn () => self::allPartNoOptions())
                        ->getSearchResultsUsing(function (string $search) {
                            $normalized = str_replace([' ', '-'], '', $search);

                            return Product::whereNotNull('code')
                                ->where('code', '!=', '')
                                ->where(function ($q) use ($search, $normalized) {
                                    $q->where('code', 'like', "%{$search}%")
                                      ->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"]);
                                })
                                ->get(['id', 'name', 'code'])
                                ->flatMap(function ($product) use ($search, $normalized) {
                                    return collect(explode("\n", $product->code))
                                        ->map(fn ($line) => trim($line))
                                        ->filter()
                                        ->filter(function ($line) use ($search, $normalized) {
                                            $normalizedLine = str_replace([' ', '-'], '', $line);
                                            return str_contains(strtolower($line), strtolower($search))
                                                || str_contains(strtolower($normalizedLine), strtolower($normalized));
                                        })
                                        ->mapWithKeys(fn ($line) => [
                                            "{$product->id}::{$line}" => "{$line}",
                                        ]);
                                })
                                ->all();
                        })
                        ->searchable()
                        ->live()
                        ->dehydrated(true)
                        ->placeholder('Paste or search part number…')
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (! $state) return;

                            // state is "productId::codeLine"
                            [$productId, $codeLine] = explode('::', $state, 2);

                            $product = Product::find($productId);
                            if (! $product) return;

                            $set('product_id', (int) $productId);
                            // $set('category', $product->category);
                            $set('price', $product->price ?? 0);
                            $set('brand', $product->brandModel?->name ?? null);

                            self::recalculate($set, $get);
                        })
                        // When loading an existing record, the saved value is just the
                        // raw code line, so we need to re-encode it for the Select.
                        ->afterStateHydrated(function ($state, $set, $get) {
                            if (! $state) return;

                            // If it's already in "id::code" format, leave it alone
                            if (str_contains($state, '::')) return;

                            // Find which product owns this code line
                            $product = Product::whereNotNull('code')
                                ->get(['id', 'code'])
                                ->first(function ($p) use ($state) {
                                    return collect(explode("\n", $p->code))
                                        ->map(fn ($l) => trim($l))
                                        ->contains($state);
                                });

                            if ($product) {
                                $set('part_no', "{$product->id}::{$state}");
                            }
                        }),

                    // ── Step 2: product name — auto-filled, but still editable ─
                    Select::make('product_id')
                        ->label('Product Name')
                        ->columnSpan(2)
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $product = Product::find($state);
                            if (! $product) return;

                            // $set('category', $product->category);
                            $set('price', $product->price ?? 0);
                            $set('part_no', null); // clear part_no so user re-picks
                            $set('brand', $product->brandModel?->name ?? null);

                            self::recalculate($set, $get);
                        })
                        ->required(),

                    // ── Brand — auto-filled, read-only ────────────────────────
                    TextInput::make('brand')
                        ->label('Brand')
                        ->disabled()
                        ->dehydrated(true),

                    // Select::make('category')
                    //     ->options(Product::getCategoryOptions())
                    //     ->disabled()
                    //     ->dehydrated(true),

                    TextInput::make('qty')
                        ->label('Quantity')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->live()
                        ->columnSpan(1)
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('price')
                        ->currency()
                        ->live()
                        ->columnSpan(1)
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('grand_total')
                        ->label('Total')
                        ->currency()
                        ->columnSpan(1)
                        ->disabled(),
                ])
                ->columns(9)
                ->columnSpanFull()
                ->addActionLabel('Add Product')
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),


            // =====================
            // TOTALS
            // =====================
            TextInput::make('grand_total')
                ->currency()
                ->columnStart(2)
                ->disabled(),

            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)
                ->live()
                ->columnStart(2)
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            TextInput::make('discount')
                ->numeric()
                ->default(0)
                ->live()
                ->columnStart(2)
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            TextInput::make('final_total')
                ->currency()
                ->columnStart(2)
                ->disabled(),
        ]);
    }
}