<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
// use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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

                // TextColumn::make('supplier.supplier_name')
                //     ->label('Supplier')
                //     ->searchable()
                //     ->sortable(),

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
                    ->prefix('RM ')
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

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
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