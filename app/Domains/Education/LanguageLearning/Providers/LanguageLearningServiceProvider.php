<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Провайдер для вертикали LanguageLearning.
 * Маршрутизация API и Web.
 */
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
}
