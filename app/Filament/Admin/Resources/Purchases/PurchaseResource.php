<?php

namespace App\Filament\Admin\Resources\Purchases;

use App\Filament\Admin\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Admin\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Admin\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Admin\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Admin\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Services\PurchaseInvoiceNumberService;
use UnitEnum;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?string $recordTitleAttribute = 'purchase_inv_no';
    
    // protected static ?array $searchableColumns = [
    //     'reference_no',
    //     'supplier.supplier_name',
    //     'purchase_inv_no',
    // ];
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'purchase_inv_no',
            'reference_no',
            'supplier.supplier_name',
        ];
    }


    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
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
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }


    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['purchase_inv_no'] = PurchaseInvoiceNumberService::generate();

        return $data;
    }

    
}
