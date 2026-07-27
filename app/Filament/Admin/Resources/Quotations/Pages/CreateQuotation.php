<?php

namespace App\Filament\Admin\Resources\Quotations\Pages;

use App\Filament\Admin\Resources\Quotations\QuotationResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Services\QuotationNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['quotation_no'] = $this->generateNumber();

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'quotation_no';
    }

    protected function generateNumber(): string
    {
        return QuotationNumberService::generate();
    }
}
