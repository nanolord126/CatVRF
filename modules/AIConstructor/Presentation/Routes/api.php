<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AIConstructor\Presentation\Http\Controllers\AIConstructorController;

/*
|--------------------------------------------------------------------------
| AIConstructor API Routes
|--------------------------------------------------------------------------
|
| Здесь категорически регистрируются защищенные REST API-маршруты модуля AI-сборки.
| Обязательно применяются middleware: авторизация ('auth:sanctum'),
| мульти-тенантность ('tenant') и строгий лимит запросов: не более 5 запусков генерации в минуту.
|
*/

Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:5,1'])
    ->prefix('api/ai-constructor')
    ->group(function () {
        
        // POST-эндпоинт для запуска тяжелого Vision-анализа и синтеза (Multipart form data)
        Route::post('/generate', [AIConstructorController::class, 'generate'])
            ->name('api.ai.constructor.generate');
            
    });
