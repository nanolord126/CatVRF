<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConsentManagementController;

Route::middleware('auth:sanctum')->group(function () {
    // Consent Management endpoints
    Route::prefix('consent')->group(function () {
        Route::post('/grant', [ConsentManagementController::class, 'grant'])
            ->name('consent.grant');

        Route::post('/revoke', [ConsentManagementController::class, 'revoke'])
            ->name('consent.revoke');

        Route::get('/', [ConsentManagementController::class, 'index'])
            ->name('consent.index');

        Route::get('/check/{consentType}', [ConsentManagementController::class, 'check'])
            ->name('consent.check')
            ->where('consentType', '[a-z_]+');
    });
});
