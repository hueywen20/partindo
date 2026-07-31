<?php

namespace App\Filament\Admin\Resources\PurchaseReturns\Pages;

use App\Filament\Admin\Resources\PurchaseReturns\PurchaseReturnsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}