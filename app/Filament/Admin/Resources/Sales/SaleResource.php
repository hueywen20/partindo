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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'sale_inv_no';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'sale_inv_no',
            'reference_no',
            'customer.customer_name',
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sale_inv_no'] = \App\Services\SaleInvoiceNumberService::generate();
        return $data;
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }

    // public static function canViewAny(): bool
    // {
    //     return auth()->user()?->can('view_sales');
    // }

    // public static function canCreate(): bool
    // {
    //     return auth()->user()?->can('create_sales');
    // }

    // public static function canEdit($record): bool
    // {
    //     return auth()->user()?->can('edit_sales');
    // }

    // public static function canDelete($record): bool
    // {
    //     return auth()->user()?->can('delete_sales');
    // }

    //  public static function canView($record): bool
    // {
    //     return auth()->user()?->can('view_sales');
    // }
}
