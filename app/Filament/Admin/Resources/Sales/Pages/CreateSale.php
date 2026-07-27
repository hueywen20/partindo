<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Services\SaleInvoiceNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Regenerate right before insert rather than trusting the value
        // prefilled when the form first loaded, to shrink the window in
        // which two users could be handed the same number.
        $data['sale_inv_no'] = $this->generateNumber();

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'sale_inv_no';
    }

    protected function generateNumber(): string
    {
        return SaleInvoiceNumberService::generate();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->refreshPaymentStatus();
    }
}