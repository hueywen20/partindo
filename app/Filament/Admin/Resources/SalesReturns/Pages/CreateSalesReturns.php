<?php

namespace App\Filament\Admin\Resources\SalesReturns\Pages;

use App\Filament\Admin\Resources\SalesReturns\SalesReturnsResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Models\Sale;
use App\Services\SalesReturnNumberService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSalesReturns extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = SalesReturnsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['return_no'] = $this->generateNumber();
        $data['created_by'] = Auth::id();
        $data['customer_id'] = Sale::find($data['sale_id'])?->customer_id;

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'return_no';
    }

    protected function generateNumber(): string
    {
        return SalesReturnNumberService::generate();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}