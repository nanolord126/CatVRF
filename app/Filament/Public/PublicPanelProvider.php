<?php

declare(strict_types=1);

namespace App\Filament\Public;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\PanelProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;
use App\Http\Middleware\Authenticate;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class PublicPanelProvider extends PanelProvider
{
    /**
     * / panel (app) — Customers only
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'success' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Public/Resources'), for: 'App\\Filament\\Public\\Resources')
            ->discoverPages(in: app_path('Filament/Public/Pages'), for: 'App\\Filament\\Public\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Public/Widgets'), for: 'App\\Filament\\Public\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Shopping')
                    ->items([
                        NavigationItem::make('Marketplace')
                            ->icon('heroicon-o-shopping-bag')
                            ->url('/app'),
                        NavigationItem::make('Wishlist')
                            ->icon('heroicon-o-heart')
                            ->url('/app/wishlist'),
                        NavigationItem::make('Orders')
                            ->icon('heroicon-o-receipt-refund')
                            ->url('/app/orders'),
                    ]),
                NavigationGroup::make()
                    ->label('Account')
                    ->items([
                        NavigationItem::make('Wallet')
                            ->icon('heroicon-o-wallet')
                            ->url('/app/wallet'),
                        NavigationItem::make('Profile')
                            ->icon('heroicon-o-user')
                            ->url('/app/profile'),
                        NavigationItem::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->url('/app/settings'),
                    ]),
            ]);
    }
}
