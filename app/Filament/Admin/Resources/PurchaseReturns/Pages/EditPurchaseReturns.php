<?php

namespace App\Filament\Admin\Resources\PurchaseReturns\Pages;

use App\Filament\Admin\Resources\PurchaseReturns\PurchaseReturnsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturns extends EditRecord
{
    protected static string $resource = PurchaseReturnsResource::class;

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