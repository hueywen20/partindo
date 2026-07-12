<?php

namespace App\Filament\Admin\Resources\Sales\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    // Payments are only ever meaningful for credit sales; skip Shield policy
    // checks here (same approach as StockLedgerRelationManager) and instead
    // gate visibility on the owning Sale being a credit sale, below.
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Payments';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isCredit();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->default(now())
                ->required(),

            TextInput::make('amount')
                ->label('Amount')
                ->currency()
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->helperText(fn () => 'Outstanding balance: ' . number_format($this->getOwnerRecord()->balance, 2)),

            Select::make('method')
                ->label('Payment Method')
                ->options([
                    'cash' => 'Cash',
                    'bank_transfer' => 'Bank Transfer',
                    'cheque' => 'Cheque',
                    'other' => 'Other',
                ])
                ->default('cash')
                ->required(),

            TextInput::make('reference_no')
                ->label('Reference No')
                ->placeholder('Cheque no. / transfer ref...'),

            Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_no')
            ->columns([
                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->prefix('Rp ')
                    ->currency()
                    ->sortable(),

                TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        default => ucfirst($state),
                    }),

                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->placeholder('—'),

                TextColumn::make('creator.name')
                    ->label('Recorded By')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Record Payment')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->getOwnerRecord()->customer_id;
                        $data['created_by'] = Auth::id();

                        return $data;
                    })
                    ->disabled(fn () => $this->getOwnerRecord()->balance <= 0),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
};
