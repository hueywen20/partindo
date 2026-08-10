<?php

namespace App\Filament\Admin\Resources\Purchases;

use App\Filament\Admin\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Admin\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Admin\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Admin\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Admin\Resources\Purchases\Schemas\PurchaseInfolist;
use App\Filament\Admin\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use App\Services\PurchaseInvoiceNumberService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup =
        'Purchasing';

    protected static ?string $recordTitleAttribute =
        'purchase_inv_no';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'purchase_inv_no',
            'reference_no',
            'supplier.supplier_name',
        ];
    }

    // ============================================================
    // FORM
    // ============================================================

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    // ============================================================
    // VIEW / INFOLIST
    // ============================================================

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseInfolist::configure($schema);
    }

    // ============================================================
    // TABLE
    // ============================================================

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // ============================================================
    // PAGES
    // ============================================================

    public static function getPages(): array
    {
        return [
            'index' => ListPurchases::route('/'),

            'create' => CreatePurchase::route('/create'),

            'view' => Pages\ViewPurchase::route('/{record}'),

            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }

    // ============================================================
    // CREATE
    // ============================================================

    public static function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $data['purchase_inv_no'] =
            PurchaseInvoiceNumberService::generate();

        return $data;
    }
}
