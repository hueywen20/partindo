<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Models\Customer;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class DebtReport extends Page implements HasTable
{
    public static function canAccess(): bool {
        return auth()->user()?->canAccessReports() ?? false;
    }

    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Debt / AR Aging';

    protected static ?string $title = 'Debt / AR Aging Report';

    protected string $view = 'filament.admin.pages.reports.table-report';

    public const BUCKETS = [
        'current' => 'Current',
        '1_30' => '1–30 Days',
        '31_60' => '31–60 Days',
        '61_90' => '61–90 Days',
        '90_plus' => '90+ Days',
    ];

    /**
     * Days overdue: positive if past due, negative/zero if not yet due.
     */
    public static function daysOverdue(?Carbon $dueDate): ?int
    {
        if (! $dueDate) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $dueDate->copy()->startOfDay();

        return $due->isPast() ? $today->diffInDays($due) : -$today->diffInDays($due);
    }

    public static function agingBucket(?Carbon $dueDate): string
    {
        $days = self::daysOverdue($dueDate);

        return match (true) {
            $days === null || $days <= 0 => 'current',
            $days <= 30 => '1_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => '90_plus',
        };
    }

    public static function bucketColor(string $bucket): string
    {
        return match ($bucket) {
            'current' => 'gray',
            '1_30' => 'warning',
            default => 'danger',
        };
    }

    protected function baseQuery(): Builder
    {
        return Sale::query()
            ->where('payment_type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->select('sales.*')
            ->selectRaw(
                'sales.final_total - COALESCE((select sum(amount) from payments where payments.sale_id = sales.id), 0) as balance'
            );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery()->with('customer'))
            ->columns([
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale_inv_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->getStateUsing(fn (Sale $record) => $record->due_date?->format('d-m-Y') ?? '—'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Sale $record) => number_format($record->balance, 2))
                    ->summarize(Sum::make()->label('Total Outstanding')),

                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function (Sale $record) {
                        $days = self::daysOverdue($record->due_date);
                        if ($days === null) return '—';
                        return $days > 0 ? $days : 'Not due';
                    }),

                TextColumn::make('aging_bucket')
                    ->label('Aging')
                    ->badge()
                    ->getStateUsing(fn (Sale $record) => self::BUCKETS[self::agingBucket($record->due_date)])
                    ->color(fn (Sale $record) => self::bucketColor(self::agingBucket($record->due_date))),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::orderBy('customer_name')->pluck('customer_name', 'id'))
                    ->searchable(),

                Filter::make('aging_bucket')
                    ->schema([
                        Select::make('bucket')
                            ->label('Aging Bucket')
                            ->options(self::BUCKETS),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['bucket'])) {
                            return $query;
                        }

                        $matchingIds = $this->baseQuery()
                            ->get()
                            ->filter(fn (Sale $sale) => self::agingBucket($sale->due_date) === $data['bucket'])
                            ->pluck('id');

                        return $query->whereIn('id', $matchingIds);
                    }),
            ])
            ->defaultSort('date', 'asc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('reports.debt.print', array_filter([
                    'customer_id' => $this->tableFilters['customer_id']['value'] ?? null,
                    'bucket' => $this->tableFilters['aging_bucket']['bucket'] ?? null,
                ])))
                ->openUrlInNewTab(),
            
        ];
    }
}