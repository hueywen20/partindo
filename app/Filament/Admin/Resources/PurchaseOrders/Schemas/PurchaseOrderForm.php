<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Schemas;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseOrderNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    // ─── Recalculate row totals + header totals ───────────────────────────────

    public static function recalculate(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item, $index) use ($set) {
            $qty   = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $total = $qty * $price;
            $set("items.$index.total", $total);
            return $total;
        });

        $taxRate  = (float) ($get('tax') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $tax      = $subtotal * ($taxRate / 100);
        $final    = max(0, $subtotal + $tax - $discount);

        $set('grand_total', $subtotal);
        $set('final_total', $final);
    }

    // ─── Form layout ─────────────────────────────────────────────────────────

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Header ──────────────────────────────────────────────────────

            TextInput::make('po_no')
                ->label('PO Number')
                ->default(fn () => PurchaseOrderNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('customer_id')
                ->label('Customer')
                ->options(
                    Customer::where('status', 1)->pluck('customer_name', 'id')
                )
                ->searchable()
                ->required(),

            // Select::make('supplier_id')
            //     ->label('Supplier')
            //     ->options(
            //         Supplier::where('status', 1)->pluck('supplier_name', 'id')
            //     )
            //     ->searchable(),

            Select::make('status')
                ->options([
                    'open'      => 'Open',
                    'partial'   => 'Partial',
                    'fulfilled' => 'Fulfilled',
                    'cancelled' => 'Cancelled',
                ])
                ->default('open')
                ->required(),

            // ── Line items ──────────────────────────────────────────────────

            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $product = Product::find($state);
                            $set('category', $product?->category);
                            // pre-fill with avg_cost so buyer sees supplier cost
                            $set('price', $product?->avg_cost ?? 0);
                            self::recalculate($set, $get);
                        })
                        ->required(),

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
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('price')
                        ->label('Unit Price')
                        ->numeric()
                        ->prefix('Rp  ')
                        ->live()
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('total')
                        ->numeric()
                        ->prefix('Rp  ')
                        ->disabled()
                        ->dehydrated(true),
                ])
                ->columns(5)
                ->columnSpanFull()
                ->addActionLabel('Add Product')
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                )
                ->afterStateHydrated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            // ── Totals ──────────────────────────────────────────────────────

            TextInput::make('grand_total')
                ->label('Subtotal')
                ->numeric()
                ->prefix('Rp  ')
                ->columnStart(2)
                ->disabled()
                ->dehydrated(true),

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
                ->label('Final Total')
                ->numeric()
                ->prefix('Rp  ')
                ->columnStart(2)
                ->disabled()
                ->dehydrated(true),
        ]);
    }
}