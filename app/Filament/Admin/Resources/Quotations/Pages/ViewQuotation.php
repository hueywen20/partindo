<?php

namespace App\Filament\Admin\Resources\Quotations\Pages;

use App\Filament\Admin\Resources\Quotations\QuotationResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Quotation;

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
