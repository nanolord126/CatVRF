<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FraudDetection\Interfaces\Http\Controllers\FraudController;

Route::group([
    'middleware' => ['api', 'throttle:60,1'], // Глобальный rate limit
    'prefix' => 'api/v1/fraud',
], function () {
    Route::post('check', [FraudController::class, 'check'])->name('fraud.check');
});
