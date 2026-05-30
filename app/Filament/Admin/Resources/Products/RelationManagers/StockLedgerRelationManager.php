<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockLedgerRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseItems';

    protected static ?string $title = 'Stock Ledger';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $productId = $this->getOwnerRecord()->id;

                $purchases = DB::table('purchase_items')
                    ->where('product_id', $productId)
                    ->select([
                        DB::raw('id'),
                        DB::raw('product_id'),
                        DB::raw('created_at'),
                        DB::raw('qty'),
                        DB::raw('price'),
                        DB::raw('"purchase" as type'),
                        DB::raw('purchase_id as ref_id'),
                    ]);

                $sales = DB::table('sale_items')
                    ->where('product_id', $productId)
                    ->select([
                        DB::raw('id'),
                        DB::raw('product_id'),
                        DB::raw('created_at'),
                        DB::raw('qty'),
                        DB::raw('price'),
                        DB::raw('"sale" as type'),
                        DB::raw('sale_id as ref_id'),
                    ]);

                // $union = $purchases->union($sales);
                $union = $purchases->unionAll($sales);


                return \App\Models\PurchaseItem::from(
                    DB::raw('(' . $union->toSql() . ') as purchase_items')
                )
                ->mergeBindings($union)
                ->orderBy('created_at', 'asc');
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'purchase' => '🟢 Purchase',
                        'sale'     => '🔴 Sell',
                        default    => $state,
                    }),

                TextColumn::make('supplier_buyer')
                    ->label('Supplier / Buyer')
                    ->getStateUsing(function (Model $record) {
                        if ($record->type === 'purchase') {
                            return \App\Models\Purchase::with('supplier')
                                ->find($record->ref_id)?->supplier?->company_name ?? '-';
                        } else {
                            return \App\Models\Sale::with('customer')
                                ->find($record->ref_id)?->customer?->name ?? '-';
                        }
                    }),

                TextColumn::make('in')
                    ->label('In')
                    ->getStateUsing(fn (Model $record) => $record->type === 'purchase'
                        ? $record->qty
                        : '-'
                    ),

                TextColumn::make('out')
                    ->label('Out')
                    ->getStateUsing(fn (Model $record) => $record->type === 'sale'
                        ? $record->qty
                        : '-'
                    ),
                
                TextColumn::make('purchase_price')
                    ->label('Purchase Price')
                    ->getStateUsing(function (Model $record) {
                        if ($record->type === 'purchase') {
                            if (!$record->price) {
                                return '-';
                            }

                            // cost = Price per pcs × (1 + Tax Rate)
                            $tax = \App\Models\Purchase::with('supplier')->find($record->ref_id)?->tax ?? '-' ;
                            $priceWithTax = $record->price * (1 + $tax / 100);

                            return 'Rp ' . number_format($priceWithTax, 0, ',', '.');
                        }
                        return '-';
                    }),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->getStateUsing(function (Model $record) {
                        if ($record->type === 'sale') {
                            return $record->price
                                ? 'Rp ' . number_format($record->price, 0, ',', '.')
                                : '-';
                        }
                        return '-';
                    }),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->getStateUsing(function (Model $record) {
                        if ($record->type === 'purchase') {
                            return \App\Models\Purchase::find($record->ref_id)?->purchase_inv_no ?? '-';
                        } else {
                            return \App\Models\Sale::find($record->ref_id)?->sale_inv_no ?? '-';
                        }
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}