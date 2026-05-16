<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Services\SaleInvoiceNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['sale_inv_no'])) {
            $data['sale_inv_no'] = SaleInvoiceNumberService::generate();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}