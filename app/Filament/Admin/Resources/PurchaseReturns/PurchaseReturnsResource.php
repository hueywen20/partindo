<?php

namespace App\Filament\Admin\Resources\PurchaseReturns;

use App\Filament\Admin\Resources\PurchaseReturns\Pages\CreatePurchaseReturns;
use App\Filament\Admin\Resources\PurchaseReturns\Pages\EditPurchaseReturns;
use App\Filament\Admin\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Admin\Resources\PurchaseReturns\Pages\ViewPurchaseReturns;
use App\Filament\Admin\Resources\PurchaseReturns\Schemas\PurchaseReturnForm;
use App\Filament\Admin\Resources\PurchaseReturns\Schemas\PurchaseReturnsForm;
use App\Filament\Admin\Resources\PurchaseReturns\Tables\PurchaseReturnsTable;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseReturnsResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|UnitEnum|null $navigationGroup = 'Returns';

    protected static ?string $navigationLabel = 'Purchase Returns';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'return_no';

    public static function getGloballySearchableAttributes(): array
    {
        return ['return_no', 'purchase.purchase_inv_no', 'supplier.supplier_name'];
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseReturnsTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status === 'pending';
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status === 'pending';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturns::route('/create'),
            'view' => ViewPurchaseReturns::route('/{record}'),
            'edit' => EditPurchaseReturns::route('/{record}/edit'),
        ];
    }
}