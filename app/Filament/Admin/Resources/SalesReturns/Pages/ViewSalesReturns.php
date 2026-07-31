<?php

namespace App\Filament\Admin\Resources\SalesReturns\Pages;

use App\Filament\Admin\Resources\SalesReturns\SalesReturnsResource;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSalesReturns extends ViewRecord
{
    protected static string $resource = SalesReturnsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will restock the returned item(s) and, for a credit sale, reduce the customer\'s outstanding balance.')
                // ->visible(fn (SalesReturn $record) => $record->isPending() && (Auth::user()?->canApproveReturns() ?? false))
                 ->visible(fn (SalesReturn $record) => 
                        $record->isPending() && auth()->Auth::user()?->can('approve', $record)
                    )
                ->action(function (SalesReturn $record) {
                    SalesReturnService::approve($record, Auth::user());
                    Notification::make()->title('Return approved')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                // ->visible(fn (SalesReturn $record) => $record->isPending() && (Auth::user()?->canApproveReturns() ?? false))
                ->visible(fn (SalesReturn $record) => 
                        $record->isPending() && auth()->Auth::user()?->can('approve', $record)
                    )
                ->action(function (SalesReturn $record) {
                    SalesReturnService::reject($record, Auth::user());
                    Notification::make()->title('Return rejected')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('revert')
                ->label('Revert to Pending')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This undoes the stock/balance effects of this approval and sends it back to pending. Use only to correct a mistake.')
                // ->visible(fn (SalesReturn $record) => $record->isApproved() && (Auth::user()?->canApproveReturns() ?? false))
                ->visible(fn (SalesReturn $record) => 
                        $record->isApproved() && auth()->Auth::user()?->can('approve', $record)
                    )
                ->action(function (SalesReturn $record) {
                    SalesReturnService::revert($record, Auth::user());
                    Notification::make()->title('Return reverted to pending')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            EditAction::make()
                ->visible(fn (SalesReturn $record) => $record->isPending()),
        ];
    }
}