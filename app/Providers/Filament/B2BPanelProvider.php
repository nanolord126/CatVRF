declare(strict_types=1);

<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

final /**
 * B2BPanelProvider
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('b2b')
            ->path('b2b')
            ->login()
            ->maxContentWidth('full')
            ->middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesTo$this->response->class,
                \Illuminate\Session\Middleware\Start$this->session->class,
                \Illuminate\Session\Middleware\Authenticate$this->session->class,
                \Illuminate\View\Middleware\ShareErrorsFrom$this->session->class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \Filament\Http\Middleware\DisableBladeIconComponents::class,
                \Filament\Http\Middleware\DispatchServingFilament$this->event->class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ]);
    }
}
