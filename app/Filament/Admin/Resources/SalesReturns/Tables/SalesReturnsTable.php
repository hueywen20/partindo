<?php

namespace App\Filament\Admin\Resources\SalesReturns\Tables;

use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalesReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('return_no')
                    ->label('Return No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale.sale_inv_no')
                    ->label('Original Invoice')
                    ->searchable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('final_total')
                    ->label('Amount')
                    ->prefix('Rp ')
                    ->currency()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('creator.name')
                    ->label('Requested By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approver.name')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    // ->visible(fn (SalesReturn $record) => $record->isPending() && (Auth::user()?->canApproveReturns() ?? false))
                     ->visible(fn (SalesReturn $record) => 
                        $record->isPending() && auth()->Auth::user()?->can('approve', $record)
                    )
                    ->action(function (SalesReturn $record) {
                        SalesReturnService::approve($record, Auth::user());
                        Notification::make()->title('Return approved')->success()->send();
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
                    }),

                EditAction::make()
                    ->visible(fn (SalesReturn $record) => $record->isPending()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
