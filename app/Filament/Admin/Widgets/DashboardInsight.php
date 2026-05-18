<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Brand;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Supplier;

class DashboardInsight extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Dashboard Insights';

    protected function getStats(): array
    {
        $lowStockCount = Product::whereColumn('stock', '<=', 'minimum_stock')->count();
        $outOfStockCount = Product::where('stock', '<=', 0)->count();

        return [
            Stat::make('Total Products', Product::count())
                ->description('Registered products')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Total Suppliers', Supplier::count())
                ->description('Registered suppliers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

             Stat::make('Total Brands Supported', Brand::count())
                ->description('Registered brands')
                ->descriptionIcon('heroicon-m-tag')
                ->color('primary'),

            Stat::make('Low Stock Products', $lowStockCount)
                ->description($lowStockCount > 0 ? 'Needs restocking' : 'All products healthy')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),

            Stat::make('Out of Stock', $outOfStockCount)
                ->description('Products unavailable')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Sales This Month', Sale::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->description('Completed sales records')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Purchases This Month', Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->description('Incoming stock records')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),
        ];
    }
}
