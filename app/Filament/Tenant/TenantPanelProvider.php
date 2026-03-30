<?php declare(strict_types=1);

namespace App\Filament\Tenant;

use App\Http\Middleware\TenantCRMOnly;
use App\Http\Middleware\TenantScoping;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\PanelProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;

final class TenantPanelProvider extends PanelProvider
{
    /**
     * /tenant panel — Business users (Owner/Manager/Employee/Accountant)
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
            ])
            ->middleware([
                \Illuminate\Session\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                TenantScoping::class,
                TenantCRMOnly::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\Authenticate::class,
            ])
            ->authGuard('web')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Business')
                    ->items([
                        NavigationItem::make('Dashboard')
                            ->icon('heroicon-o-chart-pie')
                            ->url('/tenant'),
                        NavigationItem::make('Team')
                            ->icon('heroicon-o-users-plus')
                            ->url('/tenant/team'),
                        NavigationItem::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->url('/tenant/settings'),
                    ]),
                NavigationGroup::make()
                    ->label('Financial')
                    ->items([
                        NavigationItem::make('Wallet')
                            ->icon('heroicon-o-wallet')
                            ->url('/tenant/wallet'),
                        NavigationItem::make('Transactions')
                            ->icon('heroicon-o-arrow-trending-up')
                            ->url('/tenant/transactions'),
                        NavigationItem::make('Payouts')
                            ->icon('heroicon-o-banknotes')
                            ->url('/tenant/payouts'),
                    ]),
                NavigationGroup::make()
                    ->label('Operations')
                    ->items([
                        NavigationItem::make('Orders')
                            ->icon('heroicon-o-shopping-cart')
                            ->url('/tenant/orders'),
                        NavigationItem::make('Analytics')
                            ->icon('heroicon-o-chart-bar')
                            ->url('/tenant/analytics'),
                    ]),
            ]);
    }
}
