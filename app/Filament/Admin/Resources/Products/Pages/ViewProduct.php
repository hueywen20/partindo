<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Admin\Resources\Products\RelationManagers\StockLedgerRelationManager::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Details')
                    ->schema([
                        // Row 1 — identity
                        TextEntry::make('name')
                            ->label('Product Name')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),
                        TextEntry::make('locationModel.name')
                            ->label('Location'),
                        TextEntry::make('brandModel.name')
                            ->label('Brand'),
                        TextEntry::make('uomModel.name')
                            ->label('UOM'),
                        TextEntry::make('categoryModel.name')
                            ->label('Category'),

                        // Row 2 — stock info
                        TextEntry::make('stock')
                            ->label('Current Stock')
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                        TextEntry::make('unit')
                            ->label('Unit / Variants'),
                        IconEntry::make('track_low_stock')
                            ->label('Track Low Stock')
                            ->boolean(),
                        TextEntry::make('min_stock_threshold')
                            ->label('Min Stock Threshold'),

                        // Part Numbers full width
                        TextEntry::make('code')
                            ->label('Part Numbers')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : '-')
                            ->html(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
        ];
    }


}