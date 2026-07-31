<?php

namespace App\Filament\Admin\Resources\SalesReturns\Pages;

use App\Filament\Admin\Resources\SalesReturns\SalesReturnsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesReturns extends ListRecords
{
    protected static string $resource = SalesReturnsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}