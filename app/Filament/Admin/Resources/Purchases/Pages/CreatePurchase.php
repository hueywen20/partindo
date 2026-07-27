<?php

namespace App\Filament\Admin\Resources\Purchases\Pages;

use App\Filament\Admin\Resources\Purchases\PurchaseResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Services\PurchaseInvoiceNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['purchase_inv_no'] = $this->generateNumber();

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'purchase_inv_no';
    }

    protected function generateNumber(): string
    {
        return PurchaseInvoiceNumberService::generate();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
