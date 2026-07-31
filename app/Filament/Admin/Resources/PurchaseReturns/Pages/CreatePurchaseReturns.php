<?php

namespace App\Filament\Admin\Resources\PurchaseReturns\Pages;

use App\Filament\Admin\Resources\PurchaseReturns\PurchaseReturnsResource;
use App\Filament\Concerns\RetriesOnDuplicateNumber;
use App\Models\Purchase;
use App\Services\PurchaseReturnNumberService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePurchaseReturns extends CreateRecord
{
    use RetriesOnDuplicateNumber;

    protected static string $resource = PurchaseReturnsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['return_no'] = $this->generateNumber();
        $data['created_by'] = Auth::id();
        $data['supplier_id'] = Purchase::find($data['purchase_id'])?->supplier_id;

        return $data;
    }

    protected function numberColumn(): string
    {
        return 'return_no';
    }

    protected function generateNumber(): string
    {
        return PurchaseReturnNumberService::generate();
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