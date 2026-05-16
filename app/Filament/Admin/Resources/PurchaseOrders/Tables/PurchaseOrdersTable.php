<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_no')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open'      => 'info',
                        'partial'   => 'warning',
                        'fulfilled' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('Rp ')
                    ->sortable(),

                TextColumn::make('converted_to_sale_id')
                    ->label('Invoice')
                    ->formatStateUsing(fn ($state) => $state ? '✓ Invoiced' : '—')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('quotation.quotation_no')
                    ->label('From Quotation')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => PurchaseOrderResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('convert_to_invoice')
                    ->label('Convert to Invoice')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->visible(fn (PurchaseOrder $record) =>
                        in_array($record->status, ['open', 'partial']) && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
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