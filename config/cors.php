<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    | Security Audit 2026 Recommendations:
    | - Restrict allowed_origins to specific domains
    | - Reduce max_age to prevent preflight cache poisoning
    | - Only enable supports_credentials for trusted origins
    | - Limit allowed_headers to minimum required
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')) ?: [
        // Production domains should be explicitly configured via env
        // Development defaults (remove in production)
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:8080' : null,
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:3000' : null,
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:8080' : null,
    ],

    'allowed_origins_patterns' => [
        // Pattern for subdomains (e.g., *.catvrf.ru)
        // Uncomment and configure for production:
        // '/^https:\/\/([a-z0-9-]+)\.catvrf\.ru$/',
    ],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Correlation-ID',
        'X-Request-ID',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-Request-ID',
    ],

    'max_age' => env('CORS_MAX_AGE', 3600), // Reduced from 86400 to 3600 (1 hour)

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
