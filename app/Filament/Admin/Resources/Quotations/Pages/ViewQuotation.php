<?php

namespace App\Filament\Admin\Resources\Quotations\Pages;

use App\Filament\Admin\Resources\Quotations\QuotationResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Quotation;
use Filament\Notifications\Notification;
use App\Services\QuotationNumberService;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make()
                ->hidden(fn (Quotation $record) => in_array($record->status, ['accepted', 'expired'])),
            Action::make('clone')
                ->label('Clone')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Clone this Quotation?')
                ->modalDescription('A new draft quotation will be created with the same customer, items, and pricing. You can then edit it as needed.')
                ->modalSubmitActionLabel('Clone')
                ->action(function (Quotation $record) {
                    $newQuotation = $record->replicate([
                        'quotation_no',
                        'status',
                        'converted_to_po_id',
                        'converted_to_sale_id',
                        'sent_via_whatsapp_at',
                    ]);
 
                    $newQuotation->quotation_no          = QuotationNumberService::generate();
                    $newQuotation->status                = 'draft';
                    $newQuotation->date                  = now();
                    $newQuotation->valid_until           = now();
                    $newQuotation->converted_to_po_id    = null;
                    $newQuotation->converted_to_sale_id  = null;
                    $newQuotation->sent_via_whatsapp_at  = null;
                    $newQuotation->save();
 
                    // Deep-clone each line item
                    foreach ($record->items()->with('components')->get() as $item) {
                        $newItem = $item->replicate(['quotation_id']);
                        $newItem->quotation_id = $newQuotation->id;
                        $newItem->save();
 
                        foreach ($item->components as $component) {
                            $newComp = $component->replicate(['quotation_item_id']);
                            $newComp->quotation_item_id = $newItem->id;
                            $newComp->save();
                        }
                    }

                    Notification::make()
                        ->title('Quotation cloned as ' . $newQuotation->quotation_no)
                        ->success()
                        ->send();
 
                    $this->redirect(
                        QuotationResource::getUrl('view', ['record' => $newQuotation])
                    );
                }),

            
            Action::make('print')
                ->label('Print')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->url(fn (Quotation $record) => route('quotations.print', $record))
                ->openUrlInNewTab(), // Opens print view in a fresh tab

            Action::make('print-dot-matrix')
                ->label('Print Dot Matrix')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->url(fn (Quotation $record) => route('quotations.print-dot-matrix', $record))
                ->openUrlInNewTab(),
        ];
    }
}
