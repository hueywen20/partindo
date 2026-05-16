<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Sale;
use App\Filament\Admin\Resources\Sales\SaleResource;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('sale_inv_no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('final_total')
                    ->label('Amount')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
                 TextColumn::make('gross_profit')
                    ->label('Gross Profit')
                    ->getStateUsing(function (Sale $record): string {
                        $profit = $record->items->sum(
                            fn($item) => ((float)$item->price - (float)$item->cost_price) * (float)$item->qty
                        );
                        return 'Rp ' . number_format($profit, 0, '.', ',');
                    })
                    ->color(fn (Sale $record) =>
                        $record->items->sum(fn($i) => ((float)$i->price - (float)$i->cost_price) * (float)$i->qty) >= 0
                            ? 'success' : 'danger'
                    ),

                TextColumn::make('margin_pct')
                    ->label('Margin %')
                    ->getStateUsing(function (Sale $record): string {
                        $revenue = (float) $record->final_total;
                        $cogs    = $record->items->sum(fn($i) => (float)$i->cost_price * (float)$i->qty);
                        if ($revenue <= 0) return '—';
                        return number_format((($revenue - $cogs) / $revenue) * 100, 1) . '%';
                    }),
                // TextColumn::make('profit')
                //     ->label('Gross Profit')
                //     ->getStateUsing(function ($record) {
                //         $profit = $record->items->sum(function ($item) {
                //             return ($item->price - $item->cost_price) * $item->qty;
                //         });
                //         return 'Rp ' . number_format($profit, 0, '.', ',');
                //     })
                //     ->color(fn ($record) => $record->items->sum(fn($i) => ($i->price - $i->cost_price) * $i->qty) > 0 ? 'success' : 'danger'),

                // TextColumn::make('margin')
                //     ->label('Margin %')
                //     ->getStateUsing(function ($record) {
                //         $revenue = $record->grand_total;
                //         $cogs    = $record->items->sum(fn($i) => $i->cost_price * $i->qty);
                //         if ($revenue == 0) return '—';
                //         return number_format((($revenue - $cogs) / $revenue) * 100, 1) . '%';
                //     }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
