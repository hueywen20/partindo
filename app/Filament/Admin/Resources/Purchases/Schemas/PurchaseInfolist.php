<?php

namespace App\Filament\Admin\Resources\Purchases\Schemas;

use App\Filament\Admin\Resources\Purchases\Schemas\PurchaseForm;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ====================================================
            // HEADER
            // ====================================================

            Section::make()
                ->schema([

                    TextEntry::make('date')
                        ->label('Date')
                        ->date('d/m/Y'),

                    TextEntry::make('purchase_inv_no')
                        ->label('Purchase inv no'),

                    TextEntry::make('supplier.company_name')
                        ->label('Supplier'),

                    TextEntry::make('reference_no')
                        ->label('Invoice No'),

                ])
                ->columns(2),

            // ====================================================
            // ITEMS
            // ====================================================

            Section::make('Items')
                ->schema([

                    RepeatableEntry::make('items')

                        ->table([
                            TableColumn::make('Part No')
                                ->width('260px'),

                            TableColumn::make('Description')
                                ->width('220px'),

                            TableColumn::make('Brand')
                                ->width('120px'),

                            TableColumn::make('Qty')
                                ->width('90px'),

                            TableColumn::make('Unit Price')
                                ->width('150px'),

                            TableColumn::make('Total')
                                ->width('150px'),

                            TableColumn::make('Notes')
                                ->width('180px'),
                        ])

                        ->schema([

                            // ====================================
                            // PART NO
                            // ====================================

                            TextEntry::make('part_no')
                                ->label('Part No')
                                ->placeholder('-'),

                            // ====================================
                            // DESCRIPTION
                            // ====================================

                            TextEntry::make('product_name')
                                ->label('Description')
                                ->placeholder('-'),

                            // ====================================
                            // BRAND
                            // ====================================

                            TextEntry::make('brand')
                                ->label('Brand')
                                ->placeholder('-'),

                            // ====================================
                            // QTY
                            // ====================================

                            TextEntry::make('qty')
                                ->label('Qty')
                                ->numeric(),

                            // ====================================
                            // UNIT PRICE
                            // ====================================

                            TextEntry::make('price')
                                ->label('Unit Price')
                                ->formatStateUsing(
                                    fn ($state): string =>
                                        'Rp ' .
                                        PurchaseForm::formatCurrency(
                                            $state
                                        )
                                ),

                            // ====================================
                            // TOTAL
                            // ====================================

                            TextEntry::make('grand_total')
                                ->label('Total')
                                ->formatStateUsing(
                                    fn ($state): string =>
                                        'Rp ' .
                                        PurchaseForm::formatCurrency(
                                            $state
                                        )
                                ),

                            // ====================================
                            // NOTES
                            // ====================================

                            TextEntry::make('notes')
                                ->label('Notes')
                                ->placeholder('-'),
                        ])

                        ->contained(false),

                ])
                ->columnSpanFull(),

            // ====================================================
            // TOTALS
            // ====================================================

            Section::make()
                ->schema([

                    TextEntry::make('grand_total')
                        ->label('Subtotal')
                        ->formatStateUsing(
                            fn ($state): string =>
                                'Rp ' .
                                PurchaseForm::formatCurrency(
                                    $state
                                )
                        ),

                    TextEntry::make('tax')
                        ->label('Tax (%)')
                        ->formatStateUsing(
                            fn ($state): string =>
                                number_format(
                                    (float) $state,
                                    2,
                                    ',',
                                    '.'
                                ) . '%'
                        ),

                    TextEntry::make('discount')
                        ->label('Discount (Rp)')
                        ->formatStateUsing(
                            fn ($state): string =>
                                'Rp ' .
                                PurchaseForm::formatCurrency(
                                    $state
                                )
                        ),

                    TextEntry::make('final_total')
                        ->label('Final Total')
                        ->formatStateUsing(
                            fn ($state): string =>
                                'Rp ' .
                                PurchaseForm::formatCurrency(
                                    $state
                                )
                        ),

                ])
                ->columns(1)
                ->columnStart(2),

        ]);
    }
}