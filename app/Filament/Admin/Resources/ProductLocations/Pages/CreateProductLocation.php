<?php

namespace App\Filament\Admin\Resources\ProductLocations\Pages;

use App\Filament\Admin\Resources\ProductLocations\ProductLocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductLocation extends CreateRecord
{
    protected static string $resource = ProductLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
