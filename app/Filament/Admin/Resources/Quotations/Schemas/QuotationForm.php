<?php

namespace App\Filament\Admin\Resources\Quotations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use App\Models\Product;
use App\Models\Customer;
use App\Services\QuotationNumberService;

class QuotationForm
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
                        "{$product->id}::{$line}" => "{$line}",
                    ]);
            })
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            DatePicker::make('date')
                ->default(now())
                ->required(),

            TextInput::make('quotation_no')
                ->label('Quotation No')
                ->default(fn () => QuotationNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            Select::make('customer_id')
                ->label('Customer')
                ->options(Customer::where('status', 1)->pluck('customer_name', 'id'))
                ->searchable()
                ->required(),

            DatePicker::make('valid_until')
                ->label('Valid Until')
                ->default(now())
                ->required(),

            Select::make('status')
                ->options([
                    'draft'    => 'Draft',
                    'sent'     => 'Sent',
                    'accepted' => 'Accepted',
                    'expired'  => 'Expired',
                ])
                ->default('draft')
                ->required(),

            // ── LINE ITEMS ────────────────────────────────────────────────────
            Repeater::make('items')
                ->relationship()
                ->schema([

                    Hidden::make('product_id'),

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
                            [$productId] = explode('::', $state, 2);
                            $product = Product::find($productId);
                            if (! $product) return;

                            $set('product_id',   (int) $productId);
                            $set('product_name', $product->name);
                            $set('price',        $product->price ?? 0);
                            $set('brand',        $product->brandModel?->name ?? null);

                            self::recalculate($set, $get);
                        })
                        ->afterStateHydrated(function ($state, $set) {
                            if (! $state) return;
                            if (str_contains($state, '::')) {
                                [$productId] = explode('::', $state, 2);
                                $product = Product::find($productId);
                                if ($product) $set('product_name', $product->name);
                                return;
                            }
                            $product = Product::whereNotNull('code')
                                ->get(['id', 'name', 'code'])
                                ->first(fn ($p) => collect(explode("\n", $p->code))
                                    ->map(fn ($l) => trim($l))
                                    ->contains($state)
                                );
                            if ($product) {
                                $set('part_no',      "{$product->id}::{$state}");
                                $set('product_name', $product->name);
                            }
                        }),

                    TextInput::make('product_name')
                        ->label('Description')
                        ->columnSpan(2)
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('brand')
                        ->label('Brand')
                        ->disabled()
                        ->dehydrated(true),

                    TextInput::make('qty')
                        ->label('Quantity')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->live(debounce: 300)
                        ->columnSpan(1)
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('price')
                        ->label('Unit Price')
                        ->currency()
                        ->live(debounce: 300)
                        ->columnSpan(1)
                        ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('total')
                        ->label('Total')
                        ->currency()
                        ->columnSpan(1)
                        ->disabled()
                        ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                        ->dehydrated(true),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Remarks for this item...')
                        ->rows(2)
                        ->columnSpanFull(),

                ])
                ->columns(9)
                ->columnSpanFull()
                ->addActionLabel('Add Item')
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                )
                ->afterStateHydrated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            // ── TOTALS ────────────────────────────────────────────────────────
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
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            TextInput::make('discount')
                ->label('Discount (Rp)')
                ->currency()
                ->default(0)
                ->live(debounce: 300)
                ->columnStart(2)
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

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