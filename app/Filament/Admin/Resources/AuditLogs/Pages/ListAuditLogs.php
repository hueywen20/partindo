<?php

namespace App\Filament\Admin\Resources\AuditLogs\Pages;

use App\Filament\Admin\Resources\AuditLogs\AuditLogsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
