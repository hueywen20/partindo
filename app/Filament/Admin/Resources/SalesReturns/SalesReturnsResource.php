<?php

namespace App\Filament\Admin\Resources\SalesReturns;

use App\Filament\Admin\Resources\SalesReturns\Pages\CreateSalesReturn;
use App\Filament\Admin\Resources\SalesReturns\Pages\EditSalesReturn;
use App\Filament\Admin\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Filament\Admin\Resources\SalesReturns\Pages\ViewSalesReturn;
use App\Filament\Admin\Resources\SalesReturns\Schemas\SalesReturnForm;
use App\Filament\Admin\Resources\SalesReturns\Tables\SalesReturnsTable;
use App\Models\SalesReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|UnitEnum|null $navigationGroup = 'Returns';

    protected static ?string $navigationLabel = 'Sales Returns';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'return_no';

    public static function getGloballySearchableAttributes(): array
    {
        return ['return_no', 'sale.sale_inv_no', 'customer.customer_name'];
    }

    public static function form(Schema $schema): Schema
    {
        return SalesReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesReturnsTable::configure($table);
    }

    /**
     * A return is a financial record once it's been acted on — only
     * pending (untouched) returns can still be edited or deleted.
     */
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
            'index' => ListSalesReturns::route('/'),
            'create' => CreateSalesReturn::route('/create'),
            'view' => ViewSalesReturn::route('/{record}'),
            'edit' => EditSalesReturn::route('/{record}/edit'),
        ];
    }
}