<?php

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Filament\Admin\Resources\AuditLogs\Pages\ListAuditLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use OwenIt\Auditing\Models\Audit;  // ← correct model
use UnitEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;


class AuditLogsResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    public static function canCreate(): bool
    {
        return false;
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('No.')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d-m-Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('System')
                    ->searchable(),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Module')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('Record ID'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),

                Tables\Filters\SelectFilter::make('auditable_type')
                    ->label('Module')
                    ->options([
                        'App\Models\Sale'          => 'Sale',
                        'App\Models\Purchase'      => 'Purchase',
                        'App\Models\Product'       => 'Product',
                        'App\Models\Customer'      => 'Customer',
                        'App\Models\Supplier'      => 'Supplier',
                        'App\Models\Quotation'     => 'Quotation',
                        'App\Models\PurchaseOrder' => 'Purchase Order',
                        'App\Models\User'          => 'User',
                    ]),
            ])
            ->recordActions([
                Action::make('view_changes')
                    ->label('Changes')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)

                    ->schema(function (Audit $record): array {
                        $rows = [];

                        foreach ($record->new_values as $field => $new) {
                            $old = $record->old_values[$field] ?? '—';
                            $rows[] = Grid::make(3)->schema([
                                TextEntry::make('field_' . $field)
                                    ->label('Field')
                                    ->state(str($field)->replace('_', ' ')->title()),
                                TextEntry::make('old_' . $field)
                                    ->label('Before')
                                    ->state($old)
                                    ->color('danger'),
                                TextEntry::make('new_' . $field)
                                    ->label('After')
                                    ->state((string) $new)
                                    ->color('success'),
                            ]);
                        }

                        return empty($rows)
                            ? [TextEntry::make('no_changes')->label('')->state('No changes recorded.')]
                            : $rows;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),    
        ];
    }
}
