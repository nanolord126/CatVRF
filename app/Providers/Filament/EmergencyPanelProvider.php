<?php declare(strict_types=1);

namespace App\Providers\Filament;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EmergencyPanelProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function panel(Panel $panel): Panel
        {
            return $panel
                ->id('110')
                ->path('110')
                ->login()
                ->maxContentWidth('full')
                ->middleware([
                    \Illuminate\Cookie\Middleware\EncryptCookies::class,
                    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\Session\Middleware\AuthenticateSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                    \Illuminate\Routing\Middleware\SubstituteBindings::class,
                    \Filament\Http\Middleware\DisableBladeIconComponents::class,
                    \Filament\Http\Middleware\DispatchServingFilamentEvent::class,
                ])
                ->authMiddleware([
                    \Filament\Http\Middleware\Authenticate::class,
                ]);
        }
}
