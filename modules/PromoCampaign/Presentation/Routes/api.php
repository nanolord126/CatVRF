<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PromoCampaign\Presentation\Http\Controllers\PromoCampaignController;

/*
|--------------------------------------------------------------------------
| PromoCampaign API Routes
|--------------------------------------------------------------------------
|
| Здесь категорически регистрируются сверхзащищенные REST API-маршруты модуля.
| Обязательно применяются middleware: авторизация ('auth:sanctum'),
| мульти-тенантность ('tenant') и строгий лимит запросов ('rate-limit-promo').
|
*/

Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:50,1'])
    ->prefix('api/promo')
    ->group(function () {
        
        // POST-эндпоинт для строгого применения акционного промокода
        Route::post('/apply', [PromoCampaignController::class, 'apply'])
            ->name('api.promo.apply');
            
    });
