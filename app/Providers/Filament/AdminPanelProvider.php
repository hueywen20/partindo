<?php

namespace App\Providers\Filament;

use Filament\Support\Enums\Width;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wezlo\FilamentWorkspaceTabs\WorkspaceTabsPlugin;
use App\Http\Middleware\EnsureSingleSession; 
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\Auth\Login;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            // ->sidebarCollapsibleOnDesktop()
            ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make()->label('Dashboard'),
                NavigationGroup::make()->label('Master'),
                NavigationGroup::make()->label('Inventory'),
                NavigationGroup::make()->label('Purchasing'),
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Returns'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureSingleSession::class, 
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                WorkspaceTabsPlugin::make(),

            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}