<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\DeviceController;
use App\Http\Controllers\Api\V1\Auth\InvitationController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\TenantController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Authentication Routes
|--------------------------------------------------------------------------
|
| Enterprise-grade authentication endpoints for CatVRF marketplace
|
*/

Route::prefix('v1/auth')->group(function () {
    // Registration (protected by brute-force middleware)
    Route::middleware('brute.force')->group(function () {
        Route::post('/register', [RegistrationController::class, 'register'])->name('auth.register');
        Route::post('/register/social', [RegistrationController::class, 'registerSocial'])->name('auth.register.social');
        Route::post('/verify-email', [RegistrationController::class, 'verifyEmail'])->name('auth.verify.email');
        Route::post('/verify-phone', [RegistrationController::class, 'verifyPhone'])->name('auth.verify.phone');
        Route::post('/send-phone-code', [RegistrationController::class, 'sendPhoneCode'])->name('auth.send.phone.code');
    });

    // Login (protected by brute-force middleware)
    Route::middleware('brute.force')->group(function () {
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
        Route::post('/login/2fa', [LoginController::class, 'verify2fa'])->name('auth.login.2fa');
        Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');
        Route::post('/logout-all', [LoginController::class, 'logoutAll'])->name('auth.logout.all');
        Route::post('/refresh', [LoginController::class, 'refresh'])->name('auth.refresh');
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [LoginController::class, 'me'])->name('auth.me');

        // 2FA
        Route::prefix('2fa')->group(function () {
            Route::post('/enable', [TwoFactorController::class, 'enable'])->name('auth.2fa.enable');
            Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('auth.2fa.confirm');
            Route::post('/disable', [TwoFactorController::class, 'disable'])->name('auth.2fa.disable');
            Route::post('/send-email-code', [TwoFactorController::class, 'sendEmailCode'])->name('auth.2fa.send.email.code');
            Route::post('/verify-email-code', [TwoFactorController::class, 'verifyEmailCode'])->name('auth.2fa.verify.email.code');
        });

        // Device management
        Route::prefix('devices')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])->name('auth.devices.index');
            Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('auth.devices.destroy');
            Route::post('/revoke-others', [DeviceController::class, 'revokeOthers'])->name('auth.devices.revoke.others');
        });
    });
});

// Tenant registration and management
Route::prefix('v1/tenants')->group(function () {
    Route::post('/register', [TenantController::class, 'register'])->name('tenants.register');

    // Tenant-specific routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{tenant}/verify-documents', [TenantController::class, 'verifyDocuments'])
            ->name('tenants.verify.documents');

        // Admin only routes
        Route::middleware(['role:super_admin,support_agent'])->group(function () {
            Route::post('/{tenant}/approve', [TenantController::class, 'approve'])->name('tenants.approve');
            Route::post('/{tenant}/reject', [TenantController::class, 'reject'])->name('tenants.reject');
        });

        // Invitations
        Route::prefix('/{tenant}/invitations')->group(function () {
            Route::get('/', [InvitationController::class, 'index'])->name('tenants.invitations.index');
            Route::post('/', [InvitationController::class, 'store'])->name('tenants.invitations.store');
            Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('tenants.invitations.destroy');
        });
    });

    // Public invitation acceptance
    Route::get('/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('tenants.invitations.accept');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('tenants.invitations.accept.post');
});
