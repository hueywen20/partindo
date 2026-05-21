<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
 
        return $schema
            ->components([
                // TextInput::make('code')
                //     ->required()
                //     ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required(),
                Select::make('location')
                    ->options(fn () => \App\Models\ProductLocation::where('status', 1)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('stock')
                    ->numeric()
                    ->default(0)
                    ->hidden()
                    ->disabled(),                 
                Select::make('brand')
                    ->options(fn () => \App\Models\Brand::where('status', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('uom')
                    ->options(fn () => \App\Models\Uom::where('status', 1)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                
                // Select::make('category')
                //     ->label('Category')
                //     ->options(Product::getCategoryOptions()),
                    // ->required(),
                // Textarea::make('unit')
                //     ->label('Unit Description')
                //     ->rows(5)
                //     ->placeholder('XZ110'),   
                // Textarea::make('code')
                //     ->label('Related Part Numbers')
                //     ->rows(10)
                //     ->placeholder('Enter related part numbers here...'),
                Textarea::make('unit')
                    ->label('Unit')
                    ->rows(10)
                    ->placeholder("sample: XZ110\nXZ120\nXZ130"),

                Textarea::make('code')
                    ->label('Part Numbers')
                    ->rows(10)
                    ->placeholder(
                        "Sample part numbers:\n" .
                        "VITON FKM 90: 07000-02010 VT RED 90\n" .
                        "VITON FKM 90: 07000-12010 VT RED 90\n" .
                        "KYB: 07000-22010"
                    )
                    ->helperText('Enter one part number per line. Format: BRAND: PART_NUMBER'),

               
                // Repeater::make('codes')
                //     ->relationship()
                //     ->label('Related Parts')
                //     ->schema([
                //         TextInput::make('code')
                //             ->label('Part No.')
                //             ->required(),

                //         Select::make('brand')
                //             ->label('Brand')
                //             ->options(Product::getBrandOptions()),
                //     ])
                //     ->columns(2)
                //     ->columnSpanFull()
                //     ->minItems(1)
                //     ->defaultItems(1)
                //     ->addActionLabel('Add Related Parts'),
                Toggle::make('track_low_stock')
                    ->label('Track Low Stock')
                    ->default(1)
                    ->required()
                    ->columnSpanFull()
                    ->reactive(), // makes it re-render the form on change

                TextInput::make('min_stock_threshold')
                    ->default(0)
                    ->visible(fn (callable $get) => (bool) $get('track_low_stock')),     
        ]);
    }
}
