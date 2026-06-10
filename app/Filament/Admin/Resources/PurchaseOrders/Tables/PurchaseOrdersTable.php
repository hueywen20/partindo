<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Tables;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderNumberService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Admin\Resources\Quotations\QuotationResource;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No.')
                    ->rowIndex(),

                TextColumn::make('po_no')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->currency()
                    ->prefix('Rp ')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open'      => 'info',
                        'partial'   => 'warning',
                        'fulfilled' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->sortable(),

                // WhatsApp sent indicator
                // IconColumn::make('sent_via_whatsapp_at')
                //     ->label('WA')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-chat-bubble-left-ellipsis')
                //     ->falseIcon('heroicon-o-minus')
                //     ->trueColor('success')
                //     ->falseColor('gray')
                //     ->tooltip(fn (PurchaseOrder $record) => $record->sent_via_whatsapp_at
                //         ? 'Sent on ' . $record->sent_via_whatsapp_at->format('d/m/Y H:i')
                //         : 'Not yet sent via WhatsApp'
                //     ),

                // Source quotation number
                TextColumn::make('quotation.quotation_no')
                    ->label('From Quotation')
                    ->placeholder('—')
                    ->searchable()
                    ->url(fn (PurchaseOrder $record) => $record->quotation
                        ? QuotationResource::getUrl('view', ['record' => $record->quotation])
                        : null
                    ),

                // Clickable invoice number
                TextColumn::make('sale.sale_inv_no')
                    ->label('→ Invoice')
                    ->placeholder('—')
                    ->color('success')
                    ->url(fn (PurchaseOrder $record) => $record->sale
                        ? SaleResource::getUrl('view', ['record' => $record->sale])
                        : null
                    ),



                TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => PurchaseOrderResource::getUrl('view', ['record' => $record]))
            ->recordActions([

                // ── Convert to Invoice ────────────────────────────────────────
                Action::make('convert_to_invoice')
                    ->label('Invoice')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->visible(fn (PurchaseOrder $record) =>
                        in_array($record->status, ['open', 'partial'])
                        && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $sale = $record->convertToSale();

                        Notification::make()
                            ->title('Converted to Sales Invoice ' . $sale->sale_inv_no)
                            ->success()
                            ->send();
                    }),

                // ── Send via WhatsApp ─────────────────────────────────────────
                // Action::make('send_whatsapp')
                //     ->label('WhatsApp')
                //     ->icon('heroicon-o-chat-bubble-left-ellipsis')
                //     ->color('info')
                //     ->visible(fn (PurchaseOrder $record) =>
                //         in_array($record->status, ['open', 'partial'])
                //     )
                //     ->requiresConfirmation()
                //     ->modalHeading('Send via WhatsApp')
                //     ->modalDescription(fn (PurchaseOrder $record) =>
                //         'Send ' . $record->po_no . ' via WhatsApp to '
                //         . ($record->customer?->company_name ?: $record->customer?->customer_name ?? 'this customer') . '?'
                //     )
                //     ->modalSubmitActionLabel('Send')
                //     ->url(fn (PurchaseOrder $record) => $record->whatsappUrl())
                //     ->openUrlInNewTab()
                //     ->after(function (PurchaseOrder $record) {
                //         $record->markSentViaWhatsapp();
                //     }),

                // ── Clone ─────────────────────────────────────────────────────
                Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
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

                        foreach ($record->items as $item) {
                            $newItem = $item->replicate(['purchase_order_id']);
                            $newItem->purchase_order_id = $newPO->id;
                            $newItem->save();
                        }

                        Notification::make()
                            ->title('Cloned as ' . $newPO->po_no)
                            ->success()
                            ->send();
                    }),

                // ── Edit ──────────────────────────────────────────────────────
                EditAction::make()
                    ->hidden(fn (PurchaseOrder $record) => in_array($record->status, ['fulfilled', 'cancelled'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}