<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PurchaseItem;
use App\Observers\PurchaseItemObserver;
use App\Models\SaleItem;
use App\Observers\SaleItemObserver;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // register model observers to handle stock adjustments
        PurchaseItem::observe(PurchaseItemObserver::class);
        SaleItem::observe(SaleItemObserver::class);


        // admin bypasses ALL permission checks
        // This is required for FilamentShieldPlugin to work correctly
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Admin') ? true : null;
        });

        // register policies for authorization
        Gate::policy(\App\Models\Sale::class, \App\Policies\SalePolicy::class);
        Gate::policy(\App\Models\Purchase::class, \App\Policies\PurchasePolicy::class);
        Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Supplier::class, \App\Policies\SupplierPolicy::class);
        Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);
        Gate::policy(\App\Models\Quotation::class, \App\Policies\QuotationPolicy::class);
        Gate::policy(\App\Models\PurchaseOrder::class, \App\Policies\PurchaseOrderPolicy::class);
        Gate::policy(\App\Models\ProductLocation::class, \App\Policies\ProductLocationPolicy::class);
        Gate::policy(\App\Models\Brand::class, \App\Policies\BrandPolicy::class);
        Gate::policy(\App\Models\Uom::class, \App\Policies\UomPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\OwenIt\Auditing\Models\Audit::class, \App\Policies\AuditPolicy::class);
        Gate::policy(\App\Models\Category::class, \App\Policies\CategoryPolicy::class);

        TextColumn::macro('currency', function () {
            /** @var TextColumn $this */
            return $this->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.'));
        });

        // TextInput::macro('currency', function () {
        //     /** @var TextInput $this */
        //     return $this
        //         ->prefix('Rp')
        //         ->extraInputAttributes(['inputmode' => 'numeric'])
        //         ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : '')
        //         ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null);
        // });

        TextInput::macro('currency', function () {
            /** @var TextInput $this */
            return $this
                ->prefix('Rp')
                ->extraInputAttributes(['inputmode' => 'numeric'])
                ->extraAlpineAttributes([
                    'x-mask:dynamic' => '$money($input, \',\', \'.\', 2)',
                ])
                ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : '')
                ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null);
        });
        
    }
}
