<?php

namespace App\Filament\Admin\Resources\Uoms\Pages;

use App\Filament\Admin\Resources\Uoms\UomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUom extends CreateRecord
{
    protected static string $resource = UomResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
