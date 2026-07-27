<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Models\Customer;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Sales Report';

    protected string $view = 'filament.admin.pages.reports.table-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->with('customer'))
            ->columns([
                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('sale_inv_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Subtotal')
                    ->prefix('Rp ')
                    ->currency()
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->prefix('Rp ')
                    ->currency()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')),

                TextColumn::make('cost')
                    ->label('Cost')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Sale $record) => number_format($record->cost, 2))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->canViewProfit() ?? false),

                TextColumn::make('profit')
                    ->label('Profit')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Sale $record) => number_format($record->profit, 2))
                    ->color(fn (Sale $record) => $record->profit >= 0 ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->canViewProfit() ?? false),

                TextColumn::make('payment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => $state === 'credit' ? 'warning' : 'success'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Sale $record) => number_format($record->balance, 2))
                    ->color(fn (Sale $record) => $record->balance > 0 ? 'danger' : 'gray'),

                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date));
                    }),

                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::orderBy('customer_name')->pluck('customer_name', 'id'))
                    ->searchable(),

                SelectFilter::make('payment_type')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->defaultSort('date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('reports.sales.print', array_filter([
                    'from' => $this->tableFilters['date_range']['from'] ?? null,
                    'until' => $this->tableFilters['date_range']['until'] ?? null,
                    'customer_id' => $this->tableFilters['customer_id']['value'] ?? null,
                    'payment_type' => $this->tableFilters['payment_type']['value'] ?? null,
                    'payment_status' => $this->tableFilters['payment_status']['value'] ?? null,
                ])))
                ->openUrlInNewTab(),
        ];
    }
}