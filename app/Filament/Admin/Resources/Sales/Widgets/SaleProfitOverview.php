<?php

namespace App\Filament\Admin\Resources\Sales\Widgets;

use App\Models\Sale;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SaleProfitOverview extends StatsOverviewWidget
{
    public ?Sale $record = null;

    protected ?string $heading = 'Profitability (Admin Only)';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->canViewProfit() ?? false;
    }

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $revenue = (float) $this->record->final_total;
        $cost = $this->record->cost;
        $profit = $this->record->profit;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            Stat::make('Revenue', 'Rp ' . number_format($revenue, 2))
                ->color('success'),

            Stat::make('Cost', 'Rp ' . number_format($cost, 2))
                ->description('Cost of goods sold')
                ->color('warning'),

            Stat::make('Profit', 'Rp ' . number_format($profit, 2))
                ->description(number_format($margin, 1) . '% margin')
                ->descriptionIcon($profit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profit >= 0 ? 'success' : 'danger'),
        ];
    }
}