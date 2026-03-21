<?php

declare(strict_types=1);

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;

final class SanctumServiceProvider extends ServiceProvider
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
