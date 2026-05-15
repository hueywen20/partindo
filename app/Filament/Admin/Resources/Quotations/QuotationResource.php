<?php

namespace App\Filament\Admin\Resources\Quotations;

use App\Filament\Admin\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Admin\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Admin\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Admin\Resources\Quotations\Schemas\QuotationForm;
use App\Filament\Admin\Resources\Quotations\Tables\QuotationsTable;
use App\Models\Quotation;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\QuotationNumberService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Actions\Action;
// use Filament\Tables\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use UnitEnum;


class QuotationResource extends Resource
{
    // protected static ?string $model = Quotation::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // protected static ?string $model = Quotation::class;
    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    // protected static ?string $navigationGroup = 'Sales';
    // protected static string|null $navigationGroup = 'Sales';
    protected static ?string $model = Quotation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('quotation_no')
                ->default(fn () => QuotationNumberService::generate())
                ->disabled()->dehydrated(true),

            DatePicker::make('date')->default(now())->required(),
            DatePicker::make('valid_until')
                ->default(now())->required(),

            Select::make('customer_id')
                ->label('Customer')
                ->options(\App\Models\Customer::where('status', 1)->pluck('customer_name', 'id'))
                ->searchable()->required(),

            Select::make('status')
                ->options(['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','expired'=>'Expired'])
                ->default('draft'),

            Repeater::make('items')->relationship()->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->options(Product::pluck('name', 'id'))
                    ->searchable()->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $product = Product::find($state);
                        if ($product) $set('price', $product->avg_cost * 1.35); // default 35% markup
                    }),
                TextInput::make('qty')->numeric()->default(1)->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $set('total', $get('qty') * $get('price'));

                        self::updateTotals($set, $get);
                    }),
                    // ->afterStateUpdated(fn ($state, $set, $get) => $set('total', $get('qty') * $get('price'))),
                TextInput::make('price')->numeric()->prefix('Rp ')->live()
                    // ->afterStateUpdated(fn ($state, $set, $get) => $set('total', $get('qty') * $get('price'))),
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $set('total', $get('qty') * $get('price'));

                        self::updateTotals($set, $get);
                    }),
                TextInput::make('total')->numeric()->disabled(),
            ])->columns(4)->columnSpanFull()->addActionLabel('Add Item'),

            // TextInput::make('grand_total')->numeric()->prefix('Rp ')->disabled(),
            // TextInput::make('tax')->label('Tax (%)')->numeric()->default(0),
            // TextInput::make('discount')->numeric()->default(0),
            // TextInput::make('final_total')->numeric()->prefix('Rp ')->disabled(),

            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::updateTotals($set, $get)
                ),

            TextInput::make('discount')
                ->numeric()
                ->default(0)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) =>
                    self::updateTotals($set, $get)
                ),

            TextInput::make('grand_total')
                ->numeric()
                ->prefix('Rp ')
                ->disabled()
                ->dehydrated(true),

            TextInput::make('final_total')
                ->numeric()
                ->prefix('Rp ')
                ->disabled()
                ->dehydrated(true),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_no')->searchable(),
                TextColumn::make('customer.customer_name')->label('Customer'),
                TextColumn::make('date')->date(),
                TextColumn::make('valid_until')->date(),
                TextColumn::make('final_total')->money('IDR'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'draft'    => 'gray',
                        'sent'     => 'info',
                        'accepted' => 'success',
                        'expired'  => 'danger',
                    }),
                TextColumn::make('converted_to_sale_id')
                    ->label('Converted')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : '—'),
            ])
            ->recordActions([
                Action::make('convert_to_po')
                    ->label('Convert to PO')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->color('warning')
                    ->visible(fn (Quotation $record) =>
                        in_array($record->status, ['sent', 'draft'])
                    )
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $purchaseOrder = $record->convertToPO();

                        \Filament\Notifications\Notification::make()
                            ->title('Converted to Purchase Order ' . $purchaseOrder->po_no)
                            ->success()
                            ->send();
                    }),
                Action::make('convert')
                    ->label('Convert to Invoice')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->color('success')
                    ->visible(fn (Quotation $record) => $record->status === 'sent' || $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $sale = $record->convertToSale();
                        \Filament\Notifications\Notification::make()
                            ->title('Converted to Sales Invoice ' . $sale->sale_inv_no)
                            ->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }

    public static function updateTotals($set, $get): void
    {
        $items = $get('items') ?? [];

        $grandTotal = collect($items)->sum(function ($item) {
            return (float) ($item['total'] ?? 0);
        });

        $tax = (float) ($get('tax') ?? 0);
        $discount = (float) ($get('discount') ?? 0);

        $finalTotal = $grandTotal;

        if ($tax > 0) {
            $finalTotal += ($grandTotal * $tax / 100);
        }

        $finalTotal -= $discount;

        $set('grand_total', round($grandTotal, 2));
        $set('final_total', round($finalTotal, 2));
    }


}
    