<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Cache;

final class DopplerService
{
    private static ?self $instance = null;

    public function boot(): void
    {
        // Initialize Doppler secrets if available
        // For now, use env() which reads .env
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return env($key, $default);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
