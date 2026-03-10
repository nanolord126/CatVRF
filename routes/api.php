<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Payment Routes
    Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class);
    Route::post('payments/{payment}/refund', [\App\Http\Controllers\Api\PaymentController::class, 'refund']);

    // Wallet Routes
    Route::apiResource('wallets', \App\Http\Controllers\Api\WalletController::class);
    Route::post('wallets/{wallet}/deposit', [\App\Http\Controllers\Api\WalletController::class, 'deposit']);
    Route::post('wallets/{wallet}/withdraw', [\App\Http\Controllers\Api\WalletController::class, 'withdraw']);
});
