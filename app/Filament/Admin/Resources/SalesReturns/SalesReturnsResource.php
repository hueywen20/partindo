<?php

namespace App\Filament\Admin\Resources\SalesReturns;

use App\Filament\Admin\Resources\SalesReturns\Pages\CreateSalesReturns;
use App\Filament\Admin\Resources\SalesReturns\Pages\EditSalesReturns;
use App\Filament\Admin\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Filament\Admin\Resources\SalesReturns\Pages\ViewSalesReturns;
use App\Filament\Admin\Resources\SalesReturns\Schemas\SalesReturnsForm;
use App\Filament\Admin\Resources\SalesReturns\Tables\SalesReturnsTable;
use App\Models\SalesReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SalesReturnsResource extends Resource
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
        return SalesReturnsForm::configure($schema);    
    }

    public static function table(Table $table): Table
    {
        return SalesReturnsTable::configure($table);
    }

    /**
     * A return is a financial record once it's been acted on — only
     * pending (untouched) returns can still be edited or deleted, and
     * only by someone with the matching permission.
     */
    public static function canEdit(Model $record): bool
    {
        return $record->status === 'pending' && (auth()->user()?->can('edit_sales_returns') ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status === 'pending' && (auth()->user()?->can('delete_sales_returns') ?? false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesReturns::route('/'),
            'create' => CreateSalesReturns::route('/create'),
            'view' => ViewSalesReturns::route('/{record}'),
            'edit' => EditSalesReturns::route('/{record}/edit'),
        ];
    }
}