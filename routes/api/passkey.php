<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PasskeyAuthController;

/*
|--------------------------------------------------------------------------
| Passkey Authentication API Routes
|--------------------------------------------------------------------------
|
| Enterprise passwordless authentication using WebAuthn/Passkeys.
| Supports registration, authentication, and credential management.
|
| PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
|
*/

// Public endpoints (no auth required)
Route::prefix('v1/auth/passkey')->group(function () {
    // Generate authentication options (public - for login)
    Route::post('login-options', [PasskeyAuthController::class, 'loginOptions']);

    // Complete authentication (public - for login)
    Route::post('login', [PasskeyAuthController::class, 'login']);
});

// Protected endpoints (require authentication)
Route::middleware('auth:sanctum')
    ->prefix('v1/auth/passkey')
    ->group(function () {
        // Generate registration options
        Route::post('register-options', [PasskeyAuthController::class, 'registerOptions']);

        // Complete registration
        Route::post('register', [PasskeyAuthController::class, 'register']);

        // Credential management
        Route::prefix('credentials')->group(function () {
            Route::get('/', [PasskeyAuthController::class, 'listCredentials']);
            Route::get('{id}', [PasskeyAuthController::class, 'getCredential']);
            Route::put('{id}', [PasskeyAuthController::class, 'renameCredential']);
            Route::delete('{id}', [PasskeyAuthController::class, 'deleteCredential']);
        });
    });
