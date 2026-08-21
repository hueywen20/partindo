<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->searchable(query: function ($query, string $search) {
                        $searchLower = strtolower($search);
                        $normalized = str_replace([' ', '-'], '', $searchLower);

                        $query->where(function ($q) use ($searchLower, $normalized) {
                            $q->whereRaw("LOWER(code) LIKE ?", ["%{$searchLower}%"])
                                ->orWhereRaw("REPLACE(REPLACE(LOWER(code), ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"]);
                        });
                    })
                    ->sortable()
                    ->limitList(3)
                    ->badge()
                    ->copyable() // Add this: Users can now click the badge to copy the code
                    ->copyMessage('Part number copied!') //
                    ->expandableLimitedList(),

                TextColumn::make('name')
                    ->searchable(query: function ($query, string $search) {
                        $searchLower = strtolower($search);
                        $normalized = str_replace([' ', '-'], '', $searchLower);

                        $query->where(function ($q) use ($searchLower, $normalized) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"])
                                ->orWhereRaw("REPLACE(REPLACE(LOWER(name), ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"]);
                        });
                    })
                    ->sortable(),

                TextColumn::make('brandModel.name')
                    ->label('Brand')
                    ->badge()
                    ->color('secondary')
                    ->searchable(query: function (Builder $query, string $search) {
                        $searchLower = strtolower($search);
                        $query->whereHas('brandModel', function ($q) use ($searchLower) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"]);
                        });
                    })
                    ->sortable(),

                TextColumn::make('categoryModel.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search) {
                        $searchLower = strtolower($search);
                        $query->whereHas('categoryModel', function ($q) use ($searchLower) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"]);
                        });
                    })
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
                
                TextColumn::make('is_composite')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state ? 'Composite' : 'Standard')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                TextColumn::make('available_builds')
                    ->label('Can build')
                    ->getStateUsing(fn (Product $record) => $record->is_composite
                        ? $record->available_builds
                        : null
                    )
                    ->placeholder('—')
                    ->description(fn (Product $record) => $record->is_composite
                        ? 'based on default components'
                        : null
                    ),


                TextColumn::make('uomModel.name')
                    ->label('UOM')
                    ->searchable(query: function (Builder $query, string $search) {
                        $searchLower = strtolower($search);
                        $query->whereHas('uomModel', function ($q) use ($searchLower) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"]);
                        });
                    })
                    ->sortable(),

                TextColumn::make('locationModel.name')
                    ->label('Location')
                    ->searchable(query: function (Builder $query, string $search) {
                        $searchLower = strtolower($search);
                        $query->whereHas('locationModel', function ($q) use ($searchLower) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"]);
                        });
                    })
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->getStateUsing(function ($record) {
                        if (blank($record->unit)) return [];

                        return collect(explode("\n", $record->unit))
                            ->map(fn ($line) => trim($line))
                            ->filter()
                            ->values()
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->badge()
                    ->searchable(query: function ($query, string $search) {
                        $searchLower = strtolower($search);
                        $query->whereRaw("LOWER(unit) LIKE ?", ["%{$searchLower}%"]);
                    })
                    ->sortable()
                    ->limitList(3)
                    ->expandableLimitedList(),

                TextColumn::make('created_by')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_by')
                    ->label('Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')->placeholder('Search name...'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['name'])) {
                            $searchLower = strtolower($data['name']);
                            $query->whereRaw("LOWER(name) LIKE ?", ["%{$searchLower}%"]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (filled($data['name'])) {
                            return 'Name: ' . $data['name'];
                        }
                    }),

                Filter::make('location')
                    ->schema([
                        TextInput::make('location')->placeholder('Filter by location...'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['location'])) {
                            $searchLower = strtolower($data['location']);
                            $query->whereRaw("LOWER(location) LIKE ?", ["%{$searchLower}%"]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (filled($data['location'])) {
                            return 'Location: ' . $data['location'];
                        }
                    }),

                SelectFilter::make('category')
                    ->options(Product::getCategoryOptions())
                    ->placeholder('All Categories'),

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
            ->filtersFormColumns(3)
            ->defaultSort('code', 'asc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->recordUrl(fn ($record) => ProductResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ReplicateAction::make()
                    ->label('Clone')
                    ->modalSubmitActionLabel('Clone product')
                    ->successNotificationTitle('Product cloned')
                    ->beforeReplicaSaved(fn (Product $replica) => $replica->forceFill([
                        'stock' => 0,
                        'avg_cost' => 0,
                    ]))
                    ->successRedirectUrl(fn (Product $replica): string => ProductResource::getUrl('edit', ['record' => $replica])),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
