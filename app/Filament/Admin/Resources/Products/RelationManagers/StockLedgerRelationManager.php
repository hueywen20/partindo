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
                        'sales_return' => 'Sales Return',
                        'purchase_return' => 'Purchase Return',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'danger',
                        'sales_return' => 'info',
                        'purchase_return' => 'warning',
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
                        ? number_format((float) $record->in_qty)
                        : '-')
                    ->color('success'),

                TextColumn::make('out_qty')
                    ->label('Out')
                    ->getStateUsing(fn (Model $record): string => (float) $record->out_qty > 0
                        ? number_format((float) $record->out_qty)
                        : '-')
                    ->color('danger'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(fn (Model $record): string => number_format((float) $record->balance))
                    ->color('info'),

               TextColumn::make('buying_price')
                    ->label('Buying Price (Cost)')
                    ->getStateUsing(function (Model $record) {
                        if ($record->type === 'purchase' && $record->buying_price) {
                            $tax = (float) ($record->tax ?? 0);
                            $priceWithTax = (float) $record->buying_price * (1 + $tax / 100);

                            return 'Rp ' . number_format($priceWithTax, 0, ',', '.');
                        }

                        // Purchase return prices are already tax-inclusive snapshots
                        // (see PurchaseReturnForm), so no further tax math needed.
                        if ($record->type === 'purchase_return' && $record->buying_price) {
                            return 'Rp ' . number_format((float) $record->buying_price, 0, ',', '.');
                        }

                        return '-';
                    }),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->getStateUsing(fn (Model $record): string => filled($record->selling_price)
                        ? 'Rp ' . number_format((float) $record->selling_price, 2, ',', '.')
                        : '-'),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->badge()
                    ->color(fn (Model $record): string => match ($record->type) {
                        'purchase' => 'success',
                        'sale' => 'danger',
                        'sales_return' => 'info',
                        'purchase_return' => 'warning',
                        default => 'gray',
                    }),
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
                DB::raw('suppliers.company_name as partner_name'),
                DB::raw('purchase_items.qty as in_qty'),
                DB::raw('0 as out_qty'),
                DB::raw('purchase_items.price as buying_price'),
                DB::raw('NULL as selling_price'),
                DB::raw('COALESCE(purchases.purchase_inv_no, purchases.reference_no) as reference'),
                DB::raw('purchases.id as ref_id'),
                DB::raw('purchases.tax as tax'),
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
                DB::raw('NULL as ref_id'),
                DB::raw('NULL as tax'),
            ]);

        // Customer returns goods to us — stock comes back IN. Only approved
        // returns have actually happened; pending/rejected ones are excluded.
        $salesReturns = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->join('products', 'products.id', '=', 'sales_return_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand')
            ->leftJoin('customers', 'customers.id', '=', 'sales_returns.customer_id')
            ->where('sales_return_items.product_id', $productId)
            ->where('sales_returns.status', 'approved')
            ->select([
                DB::raw('(2000000000000 + sales_return_items.id) as id'),
                DB::raw('sales_return_items.product_id as product_id'),
                DB::raw("'sales_return' as type"),
                DB::raw('sales_returns.date as transaction_date'),
                DB::raw('3 as sort_order'),
                DB::raw('sales_return_items.id as source_id'),
                DB::raw('sales_return_items.part_no as part_no'),
                DB::raw('products.name as product_name'),
                DB::raw('COALESCE(sales_return_items.brand, brands.name) as brand'),
                DB::raw('customers.customer_name as partner_name'),
                DB::raw('sales_return_items.qty as in_qty'),
                DB::raw('0 as out_qty'),
                DB::raw('sales_return_items.cost_price as buying_price'),
                DB::raw('sales_return_items.price as selling_price'),
                DB::raw('sales_returns.return_no as reference'),
                DB::raw('sales_returns.id as ref_id'),
                DB::raw('NULL as tax'),
            ]);

        // Goods physically leave us back to the supplier — stock goes OUT.
        // Only approved returns count.
        $purchaseReturns = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->join('products', 'products.id', '=', 'purchase_return_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->where('purchase_return_items.product_id', $productId)
            ->where('purchase_returns.status', 'approved')
            ->select([
                DB::raw('(3000000000000 + purchase_return_items.id) as id'),
                DB::raw('purchase_return_items.product_id as product_id'),
                DB::raw("'purchase_return' as type"),
                DB::raw('purchase_returns.date as transaction_date'),
                DB::raw('4 as sort_order'),
                DB::raw('purchase_return_items.id as source_id'),
                DB::raw('purchase_return_items.part_no as part_no'),
                DB::raw('products.name as product_name'),
                DB::raw('COALESCE(purchase_return_items.brand, brands.name) as brand'),
                DB::raw('suppliers.company_name as partner_name'),
                DB::raw('0 as in_qty'),
                DB::raw('purchase_return_items.qty as out_qty'),
                DB::raw('purchase_return_items.price as buying_price'),
                DB::raw('NULL as selling_price'),
                DB::raw('purchase_returns.return_no as reference'),
                DB::raw('purchase_returns.id as ref_id'),
                DB::raw('NULL as tax'),
            ]);

        $ledger = $purchases->unionAll($sales)->unionAll($salesReturns)->unionAll($purchaseReturns);

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