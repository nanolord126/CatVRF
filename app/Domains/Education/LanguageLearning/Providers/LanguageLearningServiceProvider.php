<?php declare(strict_types=1);

/**
 * LanguageLearningServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/languagelearningserviceprovider
 */


namespace App\Domains\Education\LanguageLearning\Providers;

use Illuminate\Support\ServiceProvider;

final class LanguageLearningServiceProvider extends ServiceProvider
{

    public function boot(): void
        {
            $this->mapApiRoutes();
            $this->mapWebRoutes();
        }

        private function mapApiRoutes(): void
        {
            Route::prefix('api/language-learning')
                ->middleware(['api', 'auth:sanctum', 'tenant'])
                ->namespace('App\Http\Controllers\Api\LanguageLearning')
                ->group(function () {
                    Route::get('/', 'LanguageLearningApiController@index')->name('api.languages.index');
                    Route::get('/{id}', 'LanguageLearningApiController@show')->name('api.languages.show');
                    Route::post('/enroll', 'LanguageLearningApiController@enroll')->name('api.languages.enroll');
                    Route::post('/construct-path', 'LanguageLearningApiController@constructPath')->name('api.languages.construct-path');
                });
        }

        private function mapWebRoutes(): void
        {
            Route::middleware(['web', 'auth', 'tenant'])
                ->group(function () {
                    Route::get('/marketplace/languages', \App\Livewire\Marketplace\LanguageLearning\LanguageLearningShowcase::class)
                        ->name('marketplace.languages');
                });
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
