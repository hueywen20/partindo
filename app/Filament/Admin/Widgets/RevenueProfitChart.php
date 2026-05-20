<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class RevenueProfitChart extends ChartWidget
{
    protected ?string $heading = 'Revenue, Cost & Profit';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->roles()->where('name', 'super_admin')->orWhere('name', 'Admin')->exists() ?? false;
    }

    protected function getData(): array
    {
        $labels = [];
        $revenueData = [];
        $costData = [];
        $profitData = [];

        foreach (range(5, 0) as $monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $revenue = Sale::query()
                ->whereBetween('date', [$start, $end])
                ->sum('final_total');

            $cost = SaleItem::query()
                ->whereHas('sale', fn ($query) => $query->whereBetween('date', [$start, $end]))
                ->selectRaw('COALESCE(SUM(qty * cost_price), 0) as total')
                ->value('total');

            $labels[] = $month->format('M Y');
            $revenueData[] = round($revenue, 2);
            $costData[] = round($cost, 2);
            $profitData[] = round($revenue - $cost, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenueData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Cost',
                    'data' => $costData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'Profit',
                    'data' => $profitData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
