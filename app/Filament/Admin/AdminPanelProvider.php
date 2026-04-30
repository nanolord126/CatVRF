<?php

declare(strict_types=1);

namespace App\Filament\Admin;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\PanelProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\FilamentAdminIpWhitelist;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    /**
     * /admin panel — SuperAdmin only
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Red,
                'danger' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                Authorize::class,
                FilamentAdminIpWhitelist::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Platform Management')
                    ->items([
                        NavigationItem::make('Users')
                            ->icon('heroicon-o-users')
                            ->url('/admin/users'),
                        NavigationItem::make('Tenants')
                            ->icon('heroicon-o-building-storefront')
                            ->url('/admin/tenants'),
                        NavigationItem::make('Fraud Attempts')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->url('/admin/fraud-attempts'),
                    ]),
                NavigationGroup::make()
                    ->label('Analytics')
                    ->items([
                        NavigationItem::make('Platform Stats')
                            ->icon('heroicon-o-chart-bar')
                            ->url('/admin/stats'),
                    ]),
            ]);
    }
}
