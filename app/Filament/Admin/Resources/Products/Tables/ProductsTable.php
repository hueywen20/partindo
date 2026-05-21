<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use App\Models\Product;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\Products\Tables\Pages\ViewProduct;
use App\Filament\Admin\Resources\Products\ProductResource;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No')
                    ->rowIndex(),

                TextColumn::make('code')
                    ->label('Part No.')
                    ->getStateUsing(function ($record) {
                        if (blank($record->code)) return [];

                        return collect(explode("\n", $record->code))
                            ->map(fn ($line) => trim($line))
                            ->filter()
                            ->values()
                            // ->join(', ');
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->searchable(query: function ($query, string $search) {
                        $normalized = str_replace([' ', '-'], '', $search); // "P10", "0951577"

                        $query->where(function ($q) use ($search, $normalized) {
                            $q->where('code', 'like', "%{$search}%")
                            ->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"]);
                        });
                    })
                    ->sortable()
                    ->limitList(3)
                    ->badge()
                    ->expandableLimitedList(),

                
                TextColumn::make('name')
                    ->searchable(query: function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%");
                            // ->orWhereHas('codes', function ($q) use ($search) {
                                // $q->where('code', 'like', "%{$search}%");
                            // });
                    })
                    ->sortable(),
                TextColumn::make('brandModel.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(function ($record) {
                        if ($record->stock <= 0) return 'danger';
                        if ($record->track_low_stock && $record->stock < $record->min_stock_threshold) return 'warning';
                        return 'success';
                    })
                    ->icon(function ($record) {
                        if ($record->stock <= 0) return 'heroicon-m-x-circle';
                        if ($record->track_low_stock && $record->stock < $record->min_stock_threshold) return 'heroicon-m-exclamation-triangle';
                        return null;
                    }),
                TextColumn::make('uomModel.name')
                    ->label('UOM')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locationModel.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
               

                TextColumn::make('unit')
                    ->label('Unit')
                    ->getStateUsing(function ($record) {
                        if (blank($record->unit)) return [];

                        return collect(explode("\n", $record->unit))
                            ->map(fn ($line) => trim($line))
                            ->filter()
                            ->values()
                            // ->join(', ');
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->limitList(3)
                    ->expandableLimitedList(),


                // TextColumn::make('category')
                //     ->formatStateUsing(fn ($state) => Product::getCategoryOptions()[$state] ?? $state)
                //     ->searchable()
                //     ->sortable(),
              
                
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_by')
                    ->label('Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),



                // TextColumn::make('codes_display')
                //     ->label('Part No.')
                //     ->getStateUsing(fn (Product $record) =>
                //         $record->codes
                //             ->map(fn ($code) => "{$code->brand}: {$code->code}")
                //             ->toArray()
                //     )
                //     ->listWithLineBreaks()
                //     ->limitList(3)
                //     ->expandableLimitedList(),
                TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Text filter for name
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')->placeholder('Search name...'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['name'])) {
                            $query->where('name', 'like', "%{$data['name']}%");
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (filled($data['name'])) {
                            return 'Name: ' . $data['name'];
                        }
                    }),

                // Text filter for location
                Filter::make('location')
                    ->schema([
                        TextInput::make('location')->placeholder('Filter by location...'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['location'])) {
                            $query->where('location', 'like', "%{$data['location']}%");
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (filled($data['location'])) {
                            return 'Location: ' . $data['location'];
                        }
                    }),

                // Select filter for category
                SelectFilter::make('brand')
                    ->options(Product::getBrandOptions())
                    ->placeholder('All Brands'),
                
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query) => $query
                        ->where('track_low_stock', true)
                        ->whereColumn('stock', '<', 'min_stock_threshold')
                    )
                    ->toggle(),

                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', 0))
                    ->toggle(),
            ])
            ->filtersFormColumns(3) // Show all 3 filters side by side
            ->recordUrl(fn ($record) => ProductResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}