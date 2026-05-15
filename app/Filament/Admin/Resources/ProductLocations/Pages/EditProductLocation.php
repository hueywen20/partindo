<?php

namespace App\Filament\Admin\Resources\ProductLocations\Pages;

use App\Filament\Admin\Resources\ProductLocations\ProductLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductLocation extends EditRecord
{
    protected static string $resource = ProductLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
