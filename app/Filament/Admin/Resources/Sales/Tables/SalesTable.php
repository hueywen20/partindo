<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Admin\Resources\Quotations\QuotationResource;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No.')
                    ->rowIndex(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('sale_inv_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Amount')
                    ->prefix('Rp ')
                    ->currency()
                    ->sortable(),

                TextColumn::make('payment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => $state === 'credit' ? 'warning' : 'success'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Sale $record) => number_format($record->balance, 2))
                    ->color(fn (Sale $record) => $record->balance > 0 ? 'danger' : 'gray'),

                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),

                // Clickable quotation number
                TextColumn::make('quotation.quotation_no')
                    ->label('From Quotation')
                    ->placeholder('—')
                    ->color('warning')
                    ->url(fn (Sale $record) => $record->quotation
                        ? QuotationResource::getUrl('view', ['record' => $record->quotation])
                        : null
                    ),

                // Clickable PO number
                TextColumn::make('purchaseOrder.po_no')
                    ->label('From PO')
                    ->placeholder('—')
                    ->color('info')
                    ->url(fn (Sale $record) => $record->purchaseOrder
                        ? PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder])
                        : null
                    ),

                TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_type')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => SaleResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}