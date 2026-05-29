<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockLedgerRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseItems';

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Transaction Records';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getLedgerQuery())
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('part_no')
                    ->label('Part No.')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('partner_name')
                    ->label('Supplier / Customer')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('in_qty')
                    ->label('In')
                    ->getStateUsing(fn (Model $record): string => (float) $record->in_qty > 0
                        ? number_format((float) $record->in_qty, 2, ',', '.')
                        : '-')
                    ->color('success'),

                TextColumn::make('out_qty')
                    ->label('Out')
                    ->getStateUsing(fn (Model $record): string => (float) $record->out_qty > 0
                        ? number_format((float) $record->out_qty, 2, ',', '.')
                        : '-')
                    ->color('danger'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(fn (Model $record): string => number_format((float) $record->balance, 2, ',', '.'))
                    ->color('info'),

                TextColumn::make('buying_price')
                    ->label('Buying Price (Cost)')
                    ->getStateUsing(fn (Model $record): string => filled($record->buying_price)
                        ? 'Rp ' . number_format((float) $record->buying_price, 2, ',', '.')
                        : '-'),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->getStateUsing(fn (Model $record): string => filled($record->selling_price)
                        ? 'Rp ' . number_format((float) $record->selling_price, 2, ',', '.')
                        : '-'),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->badge()
                    ->color(fn (Model $record): string => $record->type === 'purchase' ? 'success' : 'danger'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->paginated([10, 25, 50]);
    }

    private function getLedgerQuery()
    {
        $productId = $this->getOwnerRecord()->id;

        $purchases = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchase_items.product_id', $productId)
            ->select([
                DB::raw('purchase_items.id as id'),
                DB::raw('purchase_items.product_id as product_id'),
                DB::raw("'purchase' as type"),
                DB::raw('purchases.date as transaction_date'),
                DB::raw('1 as sort_order'),
                DB::raw('purchase_items.id as source_id'),
                DB::raw('purchase_items.part_no as part_no'),
                DB::raw('products.name as product_name'),
                DB::raw('COALESCE(purchase_items.brand, brands.name) as brand'),
                DB::raw('suppliers.supplier_name as partner_name'),
                DB::raw('purchase_items.qty as in_qty'),
                DB::raw('0 as out_qty'),
                DB::raw('purchase_items.price as buying_price'),
                DB::raw('NULL as selling_price'),
                DB::raw('COALESCE(purchases.purchase_inv_no, purchases.reference_no) as reference'),
            ]);

        $sales = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sale_items.product_id', $productId)
            ->select([
                DB::raw('(1000000000000 + sale_items.id) as id'),
                DB::raw('sale_items.product_id as product_id'),
                DB::raw("'sale' as type"),
                DB::raw('sales.date as transaction_date'),
                DB::raw('2 as sort_order'),
                DB::raw('sale_items.id as source_id'),
                DB::raw('products.code as part_no'),
                DB::raw('products.name as product_name'),
                DB::raw('brands.name as brand'),
                DB::raw('customers.customer_name as partner_name'),
                DB::raw('0 as in_qty'),
                DB::raw('sale_items.qty as out_qty'),
                DB::raw('sale_items.cost_price as buying_price'),
                DB::raw('sale_items.price as selling_price'),
                DB::raw('sales.sale_inv_no as reference'),
            ]);

        $ledger = $purchases->unionAll($sales);

        return \App\Models\PurchaseItem::query()
            ->from(DB::raw("(
                SELECT
                    ledger.*,
                    SUM(ledger.in_qty - ledger.out_qty) OVER (
                        PARTITION BY ledger.product_id
                        ORDER BY ledger.transaction_date, ledger.sort_order, ledger.source_id
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    ) as balance
                FROM ({$ledger->toSql()}) as ledger
            ) as purchase_items"))
            ->mergeBindings($ledger);
    }
}