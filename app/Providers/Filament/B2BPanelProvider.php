<?php declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Class B2BPanelProvider
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Providers\Filament
 */
final class B2BPanelProvider extends PanelProvider
{
    /**
     * Handle panel operation.
     *
     * @throws \DomainException
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('b2b')
            ->path('b2b')
            ->login()
            ->maxContentWidth('full')
            // ->discoverResources(in: app_path('Domains'), for: 'App\Domains')
            // ->discoverPages(in: app_path('Filament/B2B/Pages'), for: 'App\Filament\B2B\Pages')
            // ->discoverWidgets(in: app_path('Filament/B2B/Widgets'), for: 'App\Filament\B2B\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
