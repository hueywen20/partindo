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
use Illuminate\Support\Facades\Event; 
use Illuminate\Auth\Events\Login;
use App\Listeners\UpdateUserSessionToken;

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
        Event::listen(\Illuminate\Auth\Events\Login::class, \App\Listeners\UpdateUserSessionToken::class);

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
        Gate::policy(\App\Models\SalesReturn::class, \App\Policies\SalesReturnPolicy::class);
        Gate::policy(\App\Models\PurchaseReturn::class, \App\Policies\PurchaseReturnPolicy::class);

        TextColumn::macro('currency', function () {
            /** @var TextColumn $this */
            return $this->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.'));
        });

        // TextInput::macro('currency', function () {
        //     /** @var \Filament\Forms\Components\TextInput $this */
        //     $parse = function ($state): float {
        //         if (! filled($state)) {
        //             return 0.0;
        //         }

        //         $state = (string) $state;

        //         // Convert: 1.000.000,00 → 1000000.00
        //         $state = str_replace('.', '', $state);
        //         $state = str_replace(',', '.', $state);

        //         return (float) $state;
        //     };

        //     return $this
        //         ->prefix('Rp')
        //         ->numeric()

        //         // ✅ Filament v5 correct masking system
        //         ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\', 2)'))

        //         // ✅ display formatting
        //         ->formatStateUsing(function ($state) use ($parse) {
        //             if (! filled($state)) {
        //                 return '';
        //             }

        //             return number_format($parse($state), 2, ',', '.');
        //         })

        //         // ✅ store clean float
        //         ->dehydrateStateUsing(fn ($state) => $parse($state));
        // });

        TextInput::macro('currency', function () {
            /** @var TextInput $this */
            $parseCurrency = function ($state): float {
                if (! filled($state)) {
                    return 0.0;
                }

                $state = (string) $state;

                if (str_contains($state, ',')) {
                    return (float) str_replace(['.', ','], ['', '.'], $state);
                }

                return (float) $state;
            };

            return $this
                ->prefix('Rp')
                ->extraInputAttributes(['inputmode' => 'numeric'])
                ->extraAlpineAttributes([
                    'x-mask:dynamic' => '$money($input, \',\', \'.\', 2)',
                ])
                ->formatStateUsing(fn ($state) => filled($state) ? number_format($parseCurrency($state), 2, ',', '.') : '')
                ->dehydrateStateUsing(fn ($state) => $parseCurrency($state));
        });
        
    }
}
