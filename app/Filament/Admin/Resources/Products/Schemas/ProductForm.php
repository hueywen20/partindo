<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\Brand;
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
                
                Select::make('category')
                    ->label('Category')
                    ->options(fn () => \App\Models\Category::where('status', true)->pluck('name', 'id'))
                    ->searchable(),

                Textarea::make('unit')
                    ->label('Unit')
                    ->rows(3)
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

                Toggle::make('track_low_stock')
                    ->label('Track Low Stock')
                    ->default(1)
                    ->required()
                    ->columnSpanFull()
                    ->reactive(), // makes it re-render the form on change

                TextInput::make('min_stock_threshold')
                    ->default(0)
                    ->visible(fn (callable $get) => (bool) $get('track_low_stock')),  
                    
                Toggle::make('is_composite')
                    ->label('Composite / Custom Product')
                    ->default(false)
                    ->columnSpanFull()
                    ->live(),

                Repeater::make('recipeSlots')
                    ->relationship('recipeSlots')
                    ->label('Recipe Components')
                    ->visible(fn (callable $get) => (bool) $get('is_composite'))
                    ->columnSpanFull()
                    ->schema([
                         Toggle::make('is_required')
                            ->label('Required')
                            ->columns(1)
                            ->default(true),

                        TextInput::make('slot_name')
                            ->label('Slot name')
                            ->placeholder('e.g. Oring, Filter Unit, Gasket')
                            ->columns(4)
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->columns(4)
                            ->minValue(1)
                            ->required(),

                        Repeater::make('substitutes')
                            ->relationship('substitutes')
                            ->label('Eligible products / components')
                            ->schema([
                                Toggle::make('is_default')
                                    ->label('Default')
                                    ->columnSpan(1),

                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn () => Product::where('is_composite', false)->pluck('code', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(4),

                                Select::make('brand_id')
                                    ->label('Brand')
                                    ->options(fn () => Brand::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(4),

                                
                            ])
                            ->addActionLabel('Add eligible product / component')
                            ->columns(10)
                            ->minItems(1)
                            ->helperText('Mark exactly one product as default.'),
                        ])
                        ->addActionLabel('Add component slot'),
                            
                    ]);
    }
}   

