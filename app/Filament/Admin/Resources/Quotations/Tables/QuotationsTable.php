<?php

namespace App\Filament\Admin\Resources\Quotations\Tables;

use App\Models\Quotation;
use App\Services\QuotationNumberService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\Quotations\QuotationResource;
use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\Customer;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No.')
                    ->rowIndex(),

                TextColumn::make('quotation_no')
                    ->label('Quotation No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('customer_id')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->customer?->company_name ?: $record->customer?->customer_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('excavator_model')
                    ->label('Model')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->currency()
                    ->prefix('Rp ')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'    => 'gray',
                        'sent'     => 'info',
                        'accepted' => 'success',
                        'expired'  => 'danger',
                    })
                    ->sortable(),

                // // WhatsApp sent indicator
                // IconColumn::make('sent_via_whatsapp_at')
                //     ->label('WA')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-chat-bubble-left-ellipsis')
                //     ->falseIcon('heroicon-o-minus')
                //     ->trueColor('success')
                //     ->falseColor('gray')
                //     ->tooltip(fn (Quotation $record) => $record->sent_via_whatsapp_at
                //         ? 'Sent on ' . $record->sent_via_whatsapp_at->format('d/m/Y H:i')
                //         : 'Not yet sent via WhatsApp'
                //     ),

                // TextColumn::make('converted_to_po_id')
                //     ->label('→ PO')
                //     ->formatStateUsing(fn ($state) => $state ? '✓ PO' : '—')
                //     ->color(fn ($state) => $state ? 'warning' : 'gray'),

                // TextColumn::make('converted_to_sale_id')
                //     ->label('→ Invoice')
                //     ->formatStateUsing(fn ($state) => $state ? '✓ Invoice' : '—')
                //     ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('convertedPO.po_no')
                    ->label('→ PO')
                    ->placeholder('—')
                    ->color('warning')
                    ->url(fn (Quotation $record) => $record->convertedPO
                        ? PurchaseOrderResource::getUrl('view', ['record' => $record->convertedPO])
                        : null
                    ),
 
                TextColumn::make('convertedSale.sale_inv_no')
                    ->label('→ Invoice')
                    ->placeholder('—')
                    ->color('success')
                    ->url(fn (Quotation $record) => $record->convertedSale
                        ? SaleResource::getUrl('view', ['record' => $record->convertedSale])
                        : null
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => QuotationResource::getUrl('view', ['record' => $record]))
            ->recordActions([

                // ── Convert → PO ──────────────────────────────────────────────
                Action::make('convert_to_po')
                    ->label('PO')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->visible(fn (Quotation $record) =>
                        in_array($record->status, ['draft', 'sent'])
                        && ! $record->converted_to_po_id
                        && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $po = $record->convertToPO();
                        Notification::make()
                            ->title('Converted to Purchase Order ' . $po->po_no)
                            ->success()
                            ->send();
                    }),

                // ── Convert → Invoice ─────────────────────────────────────────
                Action::make('convert_to_invoice')
                    ->label('Invoice')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->color('success')
                    ->visible(fn (Quotation $record) =>
                        in_array($record->status, ['draft', 'sent'])
                        && ! $record->converted_to_po_id
                        && ! $record->converted_to_sale_id
                    )
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
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
                //     ->visible(fn (Quotation $record) =>
                //         in_array($record->status, ['draft', 'sent'])
                //     )
                //     ->requiresConfirmation()
                //     ->modalHeading('Send via WhatsApp')
                //     ->modalDescription(fn (Quotation $record) =>
                //         'Mark as "Sent" and open WhatsApp for '
                //         . ($record->customer?->company_name ?: $record->customer?->customer_name ?? 'this customer') . '?'
                //     )
                //     ->modalSubmitActionLabel('Send')
                //     ->url(fn (Quotation $record) => $record->whatsappUrl())
                //     ->openUrlInNewTab()
                //     ->after(function (Quotation $record) {
                //         $record->markSentViaWhatsapp();
                //     }),

                // ── Clone ─────────────────────────────────────────────────────
                Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $newQuotation = $record->replicate([
                            'quotation_no',
                            'status',
                            'converted_to_po_id',
                            'converted_to_sale_id',
                            // 'sent_via_whatsapp_at',
                        ]);

                        $newQuotation->quotation_no         = QuotationNumberService::generate();
                        $newQuotation->status               = 'draft';
                        $newQuotation->date                 = now();
                        $newQuotation->valid_until          = now();
                        $newQuotation->converted_to_po_id   = null;
                        $newQuotation->converted_to_sale_id = null;
                        // $newQuotation->sent_via_whatsapp_at = null;
                        $newQuotation->save();

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
                            ->title('Cloned as ' . $newQuotation->quotation_no)
                            ->success()
                            ->send();
                    }),

                // ── Edit ──────────────────────────────────────────────────────
                EditAction::make()
                    ->hidden(fn (Quotation $record) => in_array($record->status, ['accepted', 'expired'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}