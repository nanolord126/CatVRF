<?php

declare(strict_types=1);

use App\Domains\RealEstate\Presentation\Http\Controllers\B2C\PropertyDetailsController;
use App\Domains\RealEstate\Presentation\Http\Controllers\B2C\PropertySearchController;
use App\Domains\RealEstate\Presentation\Http\Controllers\B2B\ContractController;
use App\Domains\RealEstate\Presentation\Http\Controllers\B2B\PropertyController;
use App\Domains\RealEstate\Presentation\Http\Controllers\B2B\ViewingController;
use App\Http\Controllers\Api\V1\RealEstate\PropertyTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B2C — Публичные маршруты для покупателей / арендаторов
|--------------------------------------------------------------------------
*/
Route::middleware(['api', 'throttle:120,1'])->prefix('api/v1/real-estate')->group(function (): void {

    // Поиск объектов недвижимости (гео-фильтр, цена, тип и т.д.)
    Route::get('/properties', PropertySearchController::class)
        ->name('realestate.b2c.properties.search');

    // Детали объекта
    Route::get('/properties/{id}', [PropertyDetailsController::class, 'show'])
        ->name('realestate.b2c.properties.show');

    // Запрос на просмотр (требует авторизации клиента)
    Route::post('/properties/{id}/viewing', [PropertyDetailsController::class, 'requestViewing'])
        ->middleware('auth:sanctum')
        ->name('realestate.b2c.properties.request_viewing');
});

/*
|--------------------------------------------------------------------------
| B2B — Маршруты для агентств / Filament дашборд (tenant-aware)
|--------------------------------------------------------------------------
*/
Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
    ->prefix('api/v1/b2b/real-estate')
    ->group(function (): void {

        // ── Объекты ────────────────────────────────────────────────────
        Route::post('/properties', [PropertyController::class, 'store'])
            ->name('realestate.b2b.properties.store');

        Route::post('/properties/{id}/publish', [PropertyController::class, 'publish'])
            ->name('realestate.b2b.properties.publish');

        // ── Просмотры ──────────────────────────────────────────────────
        Route::post('/viewings/{id}/confirm', [ViewingController::class, 'confirm'])
            ->name('realestate.b2b.viewings.confirm');

        // ── Контракты ──────────────────────────────────────────────────
        Route::post('/contracts', [ContractController::class, 'store'])
            ->name('realestate.b2b.contracts.store');

        Route::post('/contracts/{id}/sign', [ContractController::class, 'sign'])
            ->name('realestate.b2b.contracts.sign');
    });

/*
|--------------------------------------------------------------------------
| TRANSACTION ENDPOINTS — AI-powered real estate transactions
|--------------------------------------------------------------------------
*/
Route::middleware(['api', 'auth:sanctum', 'throttle:100,1'])
    ->prefix('api/v1/real-estate/transactions')
    ->group(function (): void {

        // ── Property Creation with AI ───────────────────────────────────
        Route::post('/properties', [PropertyTransactionController::class, 'createProperty'])
            ->name('realestate.transactions.properties.create');

        // ── Viewing Booking with Hold Slots ─────────────────────────────
        Route::post('/viewings/book', [PropertyTransactionController::class, 'bookViewing'])
            ->name('realestate.transactions.viewings.book');

        // ── Predictive Scoring ─────────────────────────────────────────
        Route::get('/properties/{propertyId}/scoring', [PropertyTransactionController::class, 'calculatePredictiveScoring'])
            ->name('realestate.transactions.properties.scoring');

        // ── Dynamic Pricing ────────────────────────────────────────────
        Route::get('/properties/{propertyId}/pricing', [PropertyTransactionController::class, 'calculateDynamicPrice'])
            ->name('realestate.transactions.properties.pricing');

        // ── Blockchain Verification ────────────────────────────────────
        Route::post('/properties/{propertyId}/verify-blockchain', [PropertyTransactionController::class, 'verifyDocumentsOnBlockchain'])
            ->name('realestate.transactions.properties.verify-blockchain');

        // ── Escrow Payments ────────────────────────────────────────────
        Route::post('/properties/{propertyId}/escrow/initiate', [PropertyTransactionController::class, 'initiateEscrowPayment'])
            ->name('realestate.transactions.properties.escrow.initiate');

        Route::post('/properties/{propertyId}/escrow/release', [PropertyTransactionController::class, 'releaseEscrowPayment'])
            ->name('realestate.transactions.properties.escrow.release');
    });
