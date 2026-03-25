<?php declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Cache;

final class DopplerService
{
    /**
     * Get secret from Doppler with 1h cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('doppler:secret:' . $key, 3600, function() use ($key, $default) {
            return env($key, $default);
        });
    }

    /**
     * Alias for get() to maintain compatibility.
     */
    public static function getSecret(string $key, mixed $default = null): mixed
    {
        return self::get($key, $default);
    }
}
