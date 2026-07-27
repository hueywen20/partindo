<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Pages;

use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Services\PurchaseOrderNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['po_no'] = $this->generateNumber();

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'po_no';
    }

    protected function generateNumber(): string
    {
        return PurchaseOrderNumberService::generate();
    }
}
