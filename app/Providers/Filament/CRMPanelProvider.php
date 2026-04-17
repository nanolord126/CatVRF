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
 * CRM Panel — панель для CRM-операторов и менеджеров по продажам.
 * Доступна по адресу /crm.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
/**
 * Class CRMPanelProvider
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Providers\Filament
 */
final class CRMPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('crm')
            ->login()
            ->maxContentWidth('full')
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Teal,
            ])
            // ->discoverResources(in: app_path('Filament/CRM/Resources'), for: 'App\Filament\CRM\Resources')
            // ->discoverPages(in: app_path('Filament/CRM/Pages'), for: 'App\Filament\CRM\Pages')
            // ->discoverWidgets(in: app_path('Filament/CRM/Widgets'), for: 'App\Filament\CRM\Widgets')
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

