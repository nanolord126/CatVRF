<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => App\Services\Infrastructure\DopplerService::get('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => App\Services\Infrastructure\DopplerService::get('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) App\Services\Infrastructure\DopplerService::get('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => App\Services\Infrastructure\DopplerService::get('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => App\Services\Infrastructure\DopplerService::get('APP_LOCALE', 'en'),

    'fallback_locale' => App\Services\Infrastructure\DopplerService::get('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => App\Services\Infrastructure\DopplerService::get('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => App\Services\Infrastructure\DopplerService::get('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) App\Services\Infrastructure\DopplerService::get('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => App\Services\Infrastructure\DopplerService::get('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => App\Services\Infrastructure\DopplerService::get('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Service Providers...
    |--------------------------------------------------------------------------
    |
    | The application's service providers are the components that are
    | responsible for the application's functionality. These providers
    | are registered in the "providers" array within the application
    | configuration file.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        /*
         * Custom Domain Service Providers...
         */
        Modules\FraudDetection\Interfaces\Providers\FraudDetectionServiceProvider::class,
        App\Domains\Advertising\Infrastructure\Providers\AdvertisingServiceProvider::class,
        App\Domains\Delivery\Providers\DeliveryServiceProvider::class,
        App\Domains\Staff\Providers\StaffServiceProvider::class,
        App\Providers\ModelBootServiceProvider::class,
        App\Providers\PaymentServiceProvider::class,

        /*
         * Taxi Service Providers...
         */
        App\Domains\Auto\Taxi\Application\Providers\TaxiServiceProvider::class,
        App\Domains\Beauty\Application\Providers\BeautyServiceProvider::class,
        App\Domains\RealEstate\Application\Providers\RealEstateServiceProvider::class,
        App\Domains\Hotels\Infrastructure\Providers\HotelsServiceProvider::class,

        /*
         * Food Service Providers...
         */
        // App\Domains\Food\Providers\FoodServiceProvider::class,

        /*
         * Fashion Service Provider...
         */
        Modules\Fashion\Providers\FashionServiceProvider::class,

    ],

];
