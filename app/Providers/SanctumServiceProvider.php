<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SanctumServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
