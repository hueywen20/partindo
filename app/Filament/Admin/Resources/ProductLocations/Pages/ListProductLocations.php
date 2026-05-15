<?php

namespace App\Filament\Admin\Resources\ProductLocations\Pages;

use App\Filament\Admin\Resources\ProductLocations\ProductLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductLocations extends ListRecords
{
    protected static string $resource = ProductLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
