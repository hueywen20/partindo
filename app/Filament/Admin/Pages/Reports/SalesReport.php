<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Filament\Admin\Pages\Reports\Concerns\ExportsCsv;
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
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;
    use ExportsCsv;

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
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $rows = $this->getFilteredTableQuery()
                        ->with('customer')
                        ->get()
                        ->map(fn (Sale $sale) => [
                            $sale->date,
                            $sale->sale_inv_no,
                            $sale->customer?->customer_name,
                            $sale->grand_total,
                            $sale->final_total,
                            $sale->payment_type,
                            $sale->balance,
                            $sale->payment_status,
                        ]);

                    return $this->streamCsv(
                        'sales-report-' . now()->format('Y-m-d') . '.csv',
                        ['Date', 'Invoice No', 'Customer', 'Subtotal', 'Total', 'Type', 'Balance', 'Status'],
                        $rows,
                    );
                }),
        ];
    }
}