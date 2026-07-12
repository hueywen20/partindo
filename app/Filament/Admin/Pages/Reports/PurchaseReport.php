<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Filament\Admin\Pages\Reports\Concerns\ExportsCsv;
use App\Models\Purchase;
use App\Models\Supplier;
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

class PurchaseReport extends Page implements HasTable
{
    use InteractsWithTable;
    use ExportsCsv;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Purchase Report';

    protected string $view = 'filament.admin.pages.reports.table-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Purchase::query()->with('supplier'))
            ->columns([
                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('purchase_inv_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('supplier.supplier_name')
                    ->label('Supplier')
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

                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::orderBy('supplier_name')->pluck('supplier_name', 'id'))
                    ->searchable(),
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
                        ->with('supplier')
                        ->get()
                        ->map(fn (Purchase $purchase) => [
                            $purchase->date,
                            $purchase->purchase_inv_no,
                            $purchase->reference_no,
                            $purchase->supplier?->supplier_name,
                            $purchase->grand_total,
                            $purchase->final_total,
                        ]);

                    return $this->streamCsv(
                        'purchase-report-' . now()->format('Y-m-d') . '.csv',
                        ['Date', 'Invoice No', 'Reference', 'Supplier', 'Subtotal', 'Total'],
                        $rows,
                    );
                }),
        ];
    }
}