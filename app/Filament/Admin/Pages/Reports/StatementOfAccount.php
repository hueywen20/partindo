<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Models\Customer;
use App\Services\StatementOfAccountService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class StatementOfAccount extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Statement of Account';

    protected string $view = 'filament.admin.pages.reports.statement-of-account';

    public ?array $data = [];

    public ?Customer $customer = null;

    public array $entries = [];

    public float $openingBalance = 0;

    public float $closingBalance = 0;

    public bool $generated = false;

    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => request()->query('customer_id'),
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'until' => now()->format('Y-m-d'),
        ]);

        if (request()->query('customer_id')) {
            $this->generate();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::orderBy('customer_name')->pluck('customer_name', 'id'))
                    ->searchable()
                    ->required(),

                DatePicker::make('from')
                    ->label('From'),

                DatePicker::make('until')
                    ->label('Until'),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function generate(): void
    {
        $state = $this->form->getState();

        $this->customer = Customer::find($state['customer_id']);

        if (! $this->customer) {
            $this->generated = false;

            return;
        }

        $result = StatementOfAccountService::build(
            $this->customer,
            $state['from'] ?? null,
            $state['until'] ?? null,
        );

        $this->entries = $result['entries']->toArray();
        $this->openingBalance = $result['openingBalance'];
        $this->closingBalance = $result['closingBalance'];
        $this->generated = true;
    }

    public function getPrintUrl(): ?string
    {
        $customerId = $this->data['customer_id'] ?? null;

        if (! $customerId) {
            return null;
        }

        return route('reports.statement.print', array_filter([
            'customer_id' => $customerId,
            'from' => $this->data['from'] ?? null,
            'until' => $this->data['until'] ?? null,
        ]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Statement')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => $this->getPrintUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->generated),
        ];
    }
}