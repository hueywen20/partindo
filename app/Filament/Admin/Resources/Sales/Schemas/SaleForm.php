<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextArea;

use App\Models\Product;
use App\Models\ProductRecipeSlot;
use App\Models\SlotSubstitute;
use App\Models\Customer;
use App\Services\SaleInvoiceNumberService;

class SaleForm
{
    /**
     * SAFE PRODUCT LOOKUP
     */
    private static function getProduct($productId): ?Product
    {
        return $productId ? Product::find($productId) : null;
    }

    /**
     * CENTRAL CALCULATION ENGINE (NO COLLECTION BUGS)
     */
    public static function recalculate(callable $set, callable $get): void
    {
        $items = collect($get('items') ?? []);

        $subtotal = 0;

        foreach ($items as $index => $item) {

            $qty   = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $lineTotal = $qty * $price;

            $set("items.$index.total", $lineTotal);

            $subtotal += $lineTotal;
        }

        $taxRate  = (float) ($get('tax') ?? 0);
        $discount = (float) ($get('discount') ?? 0);

        $tax = $subtotal * ($taxRate / 100);

        $final = max(0, $subtotal + $tax - $discount);

        $set('grand_total', $subtotal);
        $set('final_total', $final);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /**
             * INVOICE NUMBER
             */
            TextInput::make('sale_inv_no')
                ->label('Invoice No')
                ->default(fn () => SaleInvoiceNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            /**
             * DATE
             */
            DatePicker::make('date')
                ->default(now())
                ->required(),

            /**
             * CUSTOMER
             */
            Select::make('customer_id')
                ->label('Customer')
                ->options(Customer::where('status', 1)->pluck('customer_name', 'id'))
                ->searchable()
                ->required(),

            /**
             * ITEMS REPEATER (CORE)
             */
            Repeater::make('items')
                ->schema([

                    /**
                     * PRODUCT SELECT
                     */
                    Select::make('product_id')
                        ->label('Product')
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {

                            $product = self::getProduct($state);

                            $set('price', $product?->price ?? 0);

                            // AUTO COMPONENTS (COMPOSITE PRODUCT)
                            if ($product?->is_composite) {

                                $slots = $product->recipeSlots()
                                    ->with('defaultSubstitute')
                                    ->get();

                                $components = $slots->map(fn ($slot) => [
                                    'slot_id'           => $slot->id,
                                    'chosen_product_id' => $slot->defaultSubstitute?->product_id,
                                    'qty_used'          => $slot->quantity,
                                ])->toArray();

                                $set('components', $components);

                            } else {
                                $set('components', []);
                            }

                            self::recalculate($set, $get);
                        })
                        ->required(),

                    /**
                     * QUANTITY
                     */
                    TextInput::make('qty')
                        ->numeric()
                        ->default(1)
                        ->live()
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),
                    /**
                     * PRICE (SNAPSHOT)
                     */
                    TextInput::make('price')
                        ->prefix('Rp')
                        ->numeric()
                        ->live()
                        ->afterStateUpdated(fn ($state, $set, $get) =>
                            self::recalculate($set, $get)
                        ),

                    /**
                     * LINE TOTAL
                     */
                    TextInput::make('total')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(true),

                    TextArea::make('notes')
                        ->label('Notes')
                        ->placeholder('Remarks for this item...')
                        ->rows(2)
                        ->columnSpanFull(),

                    /**
                     * COMPONENTS (COMPOSITE PRODUCTS ONLY)
                     */
                    Repeater::make('components')
                        ->schema([

                            Select::make('slot_id')
                                ->label('Slot')
                                ->options(function ($get) {
                                    return ProductRecipeSlot::where('composite_product_id', $get('../../product_id'))
                                        ->pluck('slot_name', 'id');
                                })
                                ->disabled()
                                ->dehydrated(true),

                            Select::make('chosen_product_id')
                                ->label('Use Product')
                                ->options(function ($get) {
                                    return SlotSubstitute::where('slot_id', $get('slot_id'))
                                        ->with('product')
                                        ->get()
                                        ->pluck('product.name', 'product_id');
                                })
                                ->searchable()
                                ->required(),

                            TextInput::make('qty_used')
                                ->label('Qty used')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->deletable(false)
                        ->addable(false),

                ])
                ->columns(4)
                ->columnSpanFull()
                ->addActionLabel('Add Product')
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                )
                ->afterStateHydrated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            /**
             * GRAND TOTAL (SUBTOTAL)
             */
            TextInput::make('grand_total')
                ->label('Subtotal')
                ->prefix('Rp')
                ->disabled()
                ->dehydrated(true),

            /**
             * TAX
             */
            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            /**
             * DISCOUNT
             */
            TextInput::make('discount')
                ->numeric()
                ->default(0)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::recalculate($set, $get)
                ),

            /**
             * FINAL TOTAL
             */
            TextInput::make('final_total')
                ->label('Final Total')
                ->prefix('Rp')
                ->disabled()
                ->dehydrated(true),
        ]);
    }
}