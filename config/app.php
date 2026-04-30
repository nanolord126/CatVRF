<?php

declare(strict_types=1);

use App\Domains\Advertising\Infrastructure\Providers\AdvertisingServiceProvider;
use App\Domains\Auto\Taxi\Application\Providers\TaxiServiceProvider;
use App\Domains\Beauty\Application\Providers\BeautyServiceProvider;
use App\Domains\Delivery\Providers\DeliveryServiceProvider;
// use App\Domains\Hotels\Infrastructure\Providers\HotelsServiceProvider; // Temporarily commented out
use App\Domains\RealEstate\Application\Providers\RealEstateServiceProvider;
use App\Domains\Staff\Providers\StaffServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\ModelBootServiceProvider;
use App\Providers\OnboardingServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Services\Infrastructure\DopplerService;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Pipeline\PipelineServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Modules\Fashion\Infrastructure\Providers\FashionServiceProvider;
use Modules\FraudDetection\Interfaces\Providers\FraudDetectionServiceProvider;
use Modules\Analytics\AnalyticsServiceProvider;
use Modules\BigData\BigDataServiceProvider;

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

    'name' => DopplerService::get('APP_NAME', 'Laravel'),

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

    'env' => DopplerService::get('APP_ENV', 'production'),

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

    'debug' => (bool) DopplerService::get('APP_DEBUG', false),

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

    'url' => DopplerService::get('APP_URL', 'http://localhost'),

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

    'locale' => DopplerService::get('APP_LOCALE', 'en'),

    'fallback_locale' => DopplerService::get('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => DopplerService::get('APP_FAKER_LOCALE', 'en_US'),

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

    'key' => DopplerService::get('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) DopplerService::get('APP_PREVIOUS_KEYS', ''))
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
        'driver' => DopplerService::get('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => DopplerService::get('APP_MAINTENANCE_STORE', 'database'),
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
        AuthServiceProvider::class,
        BroadcastServiceProvider::class,
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConsoleSupportServiceProvider::class,
        CookieServiceProvider::class,
        DatabaseServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        FoundationServiceProvider::class,
        HashServiceProvider::class,
        MailServiceProvider::class,
        NotificationServiceProvider::class,
        PaginationServiceProvider::class,
        PipelineServiceProvider::class,
        QueueServiceProvider::class,

        /*
         * Module Service Providers...
         */
        Modules\Media\Infrastructure\Providers\MediaServiceProvider::class,
        Modules\Video\Infrastructure\Providers\VideoServiceProvider::class,
        Modules\BigData\BigDataServiceProvider::class,
        RedisServiceProvider::class,
        PasswordResetServiceProvider::class,
        SessionServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,

        /*
         * Application Service Providers...
         */
        AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        EventServiceProvider::class,
        RouteServiceProvider::class,

        /*
         * WMS Compliance Service Providers...
         */
        App\Providers\WMSServiceProvider::class,

        /*
         * Custom Domain Service Providers...
         */
        FraudDetectionServiceProvider::class,
        AdvertisingServiceProvider::class,
        DeliveryServiceProvider::class,
        StaffServiceProvider::class,
        ModelBootServiceProvider::class,
        PaymentServiceProvider::class,
        App\Domains\Audit\Providers\AuditServiceProvider::class,

        /*
         * Taxi Service Providers...
         */
        TaxiServiceProvider::class,
        BeautyServiceProvider::class,
        RealEstateServiceProvider::class,
        // HotelsServiceProvider::class, // Temporarily commented out

        /*
         * Food Service Providers...
         */
        // App\Domains\Food\Providers\FoodServiceProvider::class,

        /*
         * Fashion Service Provider...
         */
        FashionServiceProvider::class,

        /*
         * Onboarding Service Provider...
         */
        OnboardingServiceProvider::class,

        /*
         * CRM Service Provider...
         */
        \App\Domains\CRM\Providers\CrmServiceProvider::class,
    /*
         * Analytics Service Provider...
         */
        AnalyticsServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'SellerAnalytics' => \Modules\Analytics\Application\Facades\SellerAnalytics::class,
    
    ],

];
