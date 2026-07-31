<?php

namespace App\Filament\Admin\Resources\PurchaseReturns\Pages;

use App\Filament\Admin\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will remove the returned item(s) from stock and adjust the average cost.')
                ->visible(fn (PurchaseReturn $record) => $record->isPending() && (Auth::user()?->canApproveReturns() ?? false))
                ->action(function (PurchaseReturn $record) {
                    PurchaseReturnService::approve($record, Auth::user());
                    Notification::make()->title('Return approved')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (PurchaseReturn $record) => $record->isPending() && (Auth::user()?->canApproveReturns() ?? false))
                ->action(function (PurchaseReturn $record) {
                    PurchaseReturnService::reject($record, Auth::user());
                    Notification::make()->title('Return rejected')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('revert')
                ->label('Revert to Pending')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This undoes the stock/cost effects of this approval and sends it back to pending. Use only to correct a mistake.')
                ->visible(fn (PurchaseReturn $record) => $record->isApproved() && (Auth::user()?->canApproveReturns() ?? false))
                ->action(function (PurchaseReturn $record) {
                    PurchaseReturnService::revert($record, Auth::user());
                    Notification::make()->title('Return reverted to pending')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            EditAction::make()
                ->visible(fn (PurchaseReturn $record) => $record->isPending()),
        ];
    }
}