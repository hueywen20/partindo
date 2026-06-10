<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Pages;

use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderNumberService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Back ─────────────────────────────────────────────────────────
            Action::make('back')
                ->label('Back')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),

            // ── Edit ─────────────────────────────────────────────────────────
            EditAction::make()
                ->hidden(fn (PurchaseOrder $record) => in_array($record->status, ['fulfilled', 'cancelled'])),

            // ── Clone ─────────────────────────────────────────────────────────
            Action::make('clone')
                ->label('Clone')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Clone this Purchase Order?')
                ->modalDescription('A new open PO will be created with the same customer, items, and pricing.')
                ->modalSubmitActionLabel('Clone')
                ->action(function (PurchaseOrder $record) {
                    $newPO = $record->replicate([
                        'po_no',
                        'status',
                        'converted_to_sale_id',
                        'sent_via_whatsapp_at',
                        'quotation_id',
                    ]);

                    $newPO->po_no                = PurchaseOrderNumberService::generate();
                    $newPO->status               = 'open';
                    $newPO->date                 = now();
                    $newPO->converted_to_sale_id = null;
                    $newPO->sent_via_whatsapp_at = null;
                    $newPO->quotation_id         = null;
                    $newPO->save();

                    // Deep-clone each line item
                    foreach ($record->items as $item) {
                        $newItem = $item->replicate(['purchase_order_id']);
                        $newItem->purchase_order_id = $newPO->id;
                        $newItem->save();
                    }

                    Notification::make()
                        ->title('Purchase Order cloned as ' . $newPO->po_no)
                        ->success()
                        ->send();

                    $this->redirect(
                        PurchaseOrderResource::getUrl('view', ['record' => $newPO])
                    );
                }),

            // ── Send via WhatsApp ─────────────────────────────────────────────
            // Action::make('send_whatsapp')
            //     ->label(fn (PurchaseOrder $record) => $record->sent_via_whatsapp_at
            //         ? 'Resend via WhatsApp'
            //         : 'Send via WhatsApp'
            //     )
            //     ->icon('heroicon-o-chat-bubble-left-ellipsis')
            //     ->color('success')
            //     ->hidden(fn (PurchaseOrder $record) => in_array($record->status, ['fulfilled', 'cancelled']))
            //     ->requiresConfirmation()
            //     ->modalHeading('Send via WhatsApp')
            //     ->modalDescription(fn (PurchaseOrder $record) =>
            //         'This will send PO ' . $record->po_no . ' via WhatsApp'
            //         . ($record->customer?->phone
            //             ? ' to ' . ($record->customer->company_name ?: $record->customer->customer_name) . '.'
            //             : '. Note: no phone number found for this customer.')
            //     )
            //     ->modalSubmitActionLabel('Open WhatsApp')
            //     ->action(function (PurchaseOrder $record) {
            //         $record->markSentViaWhatsapp();

            //         Notification::make()
            //             ->title('Opening WhatsApp for ' . $record->po_no)
            //             ->success()
            //             ->send();

            //         $this->redirect($record->whatsappUrl(), navigate: false);
            //     }),

            // ── Convert to Invoice ────────────────────────────────────────────
            Action::make('convert_to_invoice')
                ->label('Convert to Invoice')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->hidden(fn (PurchaseOrder $record) =>
                    in_array($record->status, ['fulfilled', 'cancelled'])
                    || $record->converted_to_sale_id
                )
                ->requiresConfirmation()
                ->modalHeading('Convert to Sales Invoice?')
                ->modalDescription('This will create a new Sales Invoice and mark this PO as fulfilled.')
                ->modalSubmitActionLabel('Convert')
                ->action(function (PurchaseOrder $record) {
                    $sale = $record->convertToSale();

                    Notification::make()
                        ->title('Converted to Sales Invoice ' . $sale->sale_inv_no)
                        ->success()
                        ->send();

                    $this->redirect(
                        PurchaseOrderResource::getUrl('view', ['record' => $record])
                    );
                }),
        ];
    }
}