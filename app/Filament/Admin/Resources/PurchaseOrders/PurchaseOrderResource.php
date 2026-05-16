<?php

namespace App\Filament\Admin\Resources\PurchaseOrders;

use App\Filament\Admin\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Admin\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Admin\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Services\PurchaseOrderNumberService;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

     public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['purchase_order_no'] = PurchaseOrderNumberService::generate();

        return $data;
    }
}
