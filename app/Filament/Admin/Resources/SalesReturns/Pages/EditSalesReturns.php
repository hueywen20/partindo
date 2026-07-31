<?php

namespace App\Filament\Admin\Resources\SalesReturns\Pages;

use App\Filament\Admin\Resources\SalesReturns\SalesReturnsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesReturns extends EditRecord
{
    protected static string $resource = SalesReturnsResource::class;

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

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }
}