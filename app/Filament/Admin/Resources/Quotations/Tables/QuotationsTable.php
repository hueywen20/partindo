<?php

namespace App\Filament\Admin\Resources\Quotations\Tables;

use App\Models\Quotation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\Quotations\QuotationResource;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_no')
                    ->label('Quotation No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('Rp ')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'    => 'gray',
                        'sent'     => 'info',
                        'accepted' => 'success',
                        'expired'  => 'danger',
                    })
                    ->sortable(),

                // Show what the quotation was converted to
                TextColumn::make('converted_to_po_id')
                    ->label('→ PO')
                    ->formatStateUsing(fn ($state) => $state ? '✓ PO' : '—')
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),

                TextColumn::make('converted_to_sale_id')
                    ->label('→ Invoice')
                    ->formatStateUsing(fn ($state) => $state ? '✓ Invoice' : '—')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => QuotationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('convert_to_po')
                    ->label('PO')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->visible(fn (Quotation $record) =>
                        in_array($record->status, ['draft', 'sent'])
                        && ! $record->converted_to_po_id
                        && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $po = $record->convertToPO();

                        \Filament\Notifications\Notification::make()
                            ->title('Converted to Purchase Order ' . $po->po_no)
                            ->success()
                            ->send();
                    }),

                Action::make('convert_to_invoice')
                    ->label('Invoice')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->color('success')
                    ->visible(fn (Quotation $record) =>
                        in_array($record->status, ['draft', 'sent'])
                        && ! $record->converted_to_po_id
                        && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $sale = $record->convertToSale();

                        \Filament\Notifications\Notification::make()
                            ->title('Converted to Sales Invoice ' . $sale->sale_inv_no)
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}