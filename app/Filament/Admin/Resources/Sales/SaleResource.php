<?php

namespace App\Filament\Admin\Resources\Sales;

use App\Filament\Admin\Resources\Sales\Pages\CreateSale;
use App\Filament\Admin\Resources\Sales\Pages\EditSale;
use App\Filament\Admin\Resources\Sales\Pages\ListSales;
use App\Filament\Admin\Resources\Sales\Schemas\SaleForm;
use App\Filament\Admin\Resources\Sales\Tables\SalesTable;
use App\Models\Sale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'sale_inv_no';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'sale_inv_no',
            'customer.customer_name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\Sales\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit'   => EditSale::route('/{record}/edit'),
        ];
    }
}