<?php declare(strict_types=1);

namespace App\Filament\Admin;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

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
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
            ])
            ->middleware([
                \Illuminate\Session\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesTo$this->response->class,
                \Illuminate\Session\Middleware\Start$this->session->class,
                \Illuminate\View\Middleware\ShareErrorsFrom$this->session->class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \Illuminate\Auth\Middleware\Authorize::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\Authenticate::class,
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
