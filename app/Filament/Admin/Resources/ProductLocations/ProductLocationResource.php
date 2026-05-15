<?php

namespace App\Filament\Admin\Resources\ProductLocations;

use App\Filament\Admin\Resources\ProductLocations\Pages\CreateProductLocation;
use App\Filament\Admin\Resources\ProductLocations\Pages\EditProductLocation;
use App\Filament\Admin\Resources\ProductLocations\Pages\ListProductLocations;
use App\Filament\Admin\Resources\ProductLocations\Schemas\ProductLocationForm;
use App\Filament\Admin\Resources\ProductLocations\Tables\ProductLocationsTable;
use App\Models\ProductLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductLocationResource extends Resource
{
    protected static ?string $model = ProductLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    public static function form(Schema $schema): Schema
    {
        return ProductLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductLocationsTable::configure($table);
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
            'index' => ListProductLocations::route('/'),
            'create' => CreateProductLocation::route('/create'),
            'edit' => EditProductLocation::route('/{record}/edit'),
        ];
    }
}
