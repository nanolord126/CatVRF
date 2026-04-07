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
 * Emergency Panel — панель для служб экстренного реагирования.
 * Доступна по адресу /110 (как телефон экстренных служб).
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
/**
 * Class EmergencyPanelProvider
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Providers\Filament
 */
final class EmergencyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('110')
            ->path('110')
            ->login()
            ->maxContentWidth('full')
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Emergency/Resources'), for: 'App\\Filament\\Emergency\\Resources')
            ->discoverPages(in: app_path('Filament/Emergency/Pages'), for: 'App\\Filament\\Emergency\\Pages')
            ->discoverWidgets(in: app_path('Filament/Emergency/Widgets'), for: 'App\\Filament\\Emergency\\Widgets')
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

