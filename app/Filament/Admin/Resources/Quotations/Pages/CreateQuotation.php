<?php

namespace App\Filament\Admin\Resources\Quotations\Pages;

use App\Filament\Admin\Resources\Quotations\QuotationResource;
use App\Services\QuotationNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['quotation_no'])) {
            $data['quotation_no'] = QuotationNumberService::generate();
        }

        return $data;
    }
}