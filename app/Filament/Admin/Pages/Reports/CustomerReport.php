<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Models\Customer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerReport extends Page implements HasTable
{

    public static function canAccess(): bool {
        return auth()->user()?->canAccessReports() ?? false;
    }
    
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Customer Report';

    protected string $view = 'filament.admin.pages.reports.table-report';

    /**
     * Lifetime, all-time figures per customer. For date-scoped credit/aging
     * analysis, see the Debt / AR Aging report instead.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->withCount('sales as invoice_count')
                    ->withSum('sales as total_sales_amount', 'final_total')
                    ->withSum(['sales as credit_sales_amount' => fn (Builder $q) => $q->where('payment_type', 'credit')], 'final_total')
                    ->withSum('payments as total_paid_amount', 'amount')
            )
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('phone_no')
                    ->label('Phone')
                    ->placeholder('—'),

                TextColumn::make('invoice_count')
                    ->label('Invoices')
                    ->sortable(),

                TextColumn::make('total_sales_amount')
                    ->label('Total Sales')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Customer $record) => number_format($record->total_sales_amount ?? 0, 2))
                    ->sortable(),

                TextColumn::make('total_paid_amount')
                    ->label('Total Paid (Credit)')
                    ->prefix('Rp ')
                    ->getStateUsing(fn (Customer $record) => number_format($record->total_paid_amount ?? 0, 2)),

                TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->prefix('Rp ')
                    ->getStateUsing(function (Customer $record) {
                        $outstanding = max(0, ($record->credit_sales_amount ?? 0) - ($record->total_paid_amount ?? 0));
                        return number_format($outstanding, 2);
                    })
                    ->color(function (Customer $record) {
                        $outstanding = max(0, ($record->credit_sales_amount ?? 0) - ($record->total_paid_amount ?? 0));
                        return $outstanding > 0 ? 'danger' : 'gray';
                    }),
            ])
            ->filters([
                Filter::make('has_outstanding')
                    ->label('Has outstanding balance')
                    ->query(fn (Builder $query) => $query->whereHas(
                        'sales',
                        fn (Builder $q) => $q->where('payment_type', 'credit')->where('payment_status', '!=', 'paid')
                    )),
            ])
            ->defaultSort('customer_name');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('reports.customer.print', array_filter([
                    'has_outstanding' => $this->tableFilters['has_outstanding']['isActive'] ?? null,
                ])))
                ->openUrlInNewTab(),
        ];
    }
}