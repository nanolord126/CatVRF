declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;

final /**
 * SanctumServiceProvider
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SanctumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
    }

    public function boot(): void
    {
        Sanctum::defaultApiTokenExpiration(config('sanctum.expiration'));
        
        Sanctum::authenticateSessionsWith('sanctum');
    }
}
