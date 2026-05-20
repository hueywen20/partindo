<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class FinancialOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Financial Overview';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->roles()->where('name', 'super_admin')->orWhere('name', 'Admin')->exists() ?? false;
    }

    protected function getStats(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $revenue = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->sum('final_total');

        $cost = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->whereBetween('date', [$start, $end]))
            ->selectRaw('COALESCE(SUM(qty * cost_price), 0) as total')
            ->value('total');

        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        $salesCount = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->count();

        $lowStockCount = Product::query()
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->count();

        $outOfStockCount = Product::query()
            ->where('stock', '<=', 0)
            ->count();

        return [
            Stat::make('Sales This Month', $salesCount)
                ->description('Total sale records')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Revenue This Month', 'IDR ' . number_format($revenue, 2))
                ->description('From completed sales')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Cost This Month', 'IDR ' . number_format($cost, 2))
                ->description('Cost of goods sold')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Gross Profit', 'IDR ' . number_format($profit, 2))
                ->description(number_format($margin, 1) . '% margin')
                ->descriptionIcon($profit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profit >= 0 ? 'success' : 'danger'),

            Stat::make('Low Stock Products', $lowStockCount)
                ->description('5 units or fewer')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),

            Stat::make('Out of Stock', $outOfStockCount)
                ->description('Unavailable products')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
