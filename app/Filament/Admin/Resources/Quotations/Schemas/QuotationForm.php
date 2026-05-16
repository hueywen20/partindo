<?php

namespace App\Filament\Admin\Resources\Quotations\Schemas;

use App\Models\Customer;
use App\Models\Product;
use App\Services\QuotationNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuotationForm
{
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

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('quotation_no')
                ->label('Quotation No')
                ->default(fn () => QuotationNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            DatePicker::make('valid_until')
                ->label('Valid Until')
                ->default(now())
                ->required(),

            Select::make('customer_id')
                ->label('Customer')
                ->options(Customer::where('status', 1)->pluck('customer_name', 'id'))
                ->searchable()
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

            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $product = Product::find($state);
                            if ($product) {
                                // Default 35% markup over avg_cost
                                $set('price', round($product->avg_cost * 1.35, 2));
                            }
                            self::recalculate($set, $get);
                        }),

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
                        ->prefix('Rp ')
                        ->disabled()
                        ->dehydrated(true),
                ])
                ->columns(4)
                ->columnSpanFull()
                ->addActionLabel('Add Item')
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                )
                ->afterStateHydrated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            TextInput::make('grand_total')
                ->label('Subtotal')
                ->numeric()
                ->prefix('Rp ')
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
                ->prefix('Rp ')
                ->columnStart(2)
                ->disabled()
                ->dehydrated(true),
        ]);
    }
}