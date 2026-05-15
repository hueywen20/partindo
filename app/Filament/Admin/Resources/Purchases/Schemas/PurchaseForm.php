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
    public static function recalculate(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item, $index) use ($set) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $total = $qty * $price;

            $set("items.$index.total", $total);

            return $total;
        });

        $taxRate = (float) ($get('tax') ?? 0);
        $discount = (float) ($get('discount') ?? 0);

        $tax = $subtotal * ($taxRate / 100);

        $final = max(0, $subtotal + $tax - $discount);

        $set('grand_total', $subtotal);
        $set('final_total', $final);

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
                ->disabled() // optional (so user cannot edit)
                ->dehydrated(true), // ensures it still saves   
            // TextInput::make('reference_no')
            //     ->default(fn () => \App\Services\PurchaseInvoiceNumberService::generate())
            //     ->disabled()
            //     ->dehydrated(true),      

            Select::make('supplier_id')
                ->label('Supplier')
                ->options(
                    \App\Models\Supplier::where('status', 1)
                        ->pluck('supplier_name', 'id')
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
                    Select::make('product_id')
                        ->label('Product Name')
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $product = Product::find($state);

                            $set('category', $product?->category);
                            $set('price', $product?->price ?? 0);

                            self::recalculate($set, $get); // 🔥 IMPORTANT
                        })
                        ->required(),

                    Select::make('category')
                        ->options(Product::getCategoryOptions())
                        ->disabled()
                        ->dehydrated(true),

                    TextInput::make('qty')
                        ->numeric()
                        ->default(1)
                        ->live()
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('price')
                        ->numeric()
                        ->prefix('Rp ')
                        ->live()
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    TextInput::make('total')
                        ->numeric()
                        ->disabled(),
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
            

                // =====================
                // outside of repeater
                // =====================
            
            TextInput::make('grand_total')
                ->numeric()
                ->prefix('Rp ')
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
                ->numeric()
                ->prefix('Rp ')
                ->columnStart(2)
                ->disabled(),
        ]);
    }
}