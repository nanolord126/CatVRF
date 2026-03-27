<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Ritual\RitualApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ritual Services API Routes — Production Ready 2026
|--------------------------------------------------------------------------
|
| Здесь регистрируются API-эндпоинты для вертикали Ritual.
| Все маршруты изолированы по tenant_id через middleware.
|
*/

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->group(function () {
    
    // Группа маршрутов ритуальных услуг (prefix logic v1/ritual)
    Route::prefix('ritual')->group(function () {
        
        // Заказы на похороны (CRUD + Процессинг)
        Route::get('/orders', [RitualApiController::class, 'index'])->name('api.ritual.orders.index');
        Route::post('/orders', [RitualApiController::class, 'store'])->name('api.ritual.orders.store');
        Route::get('/orders/{uuid}', [RitualApiController::class, 'show'])->name('api.ritual.orders.show');
        
        // Эндпоинты для AI-конструктора (MemorialConstructor)
        Route::post('/constructor/offer', [RitualApiController::class, 'getAiOffer'])->name('api.ritual.ai.offer');
        
    });

});
