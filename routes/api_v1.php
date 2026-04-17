<?php

use App\Http\Controllers\Api\V1\AI\AIConstructorController;

Route::post('/ai-constructor/run', [AIConstructorController::class, 'run'])
    ->middleware('auth:sanctum')
    ->middleware('throttle:10,1') // max 10 requests per minute
    ->name('api.v1.ai.constructor.run');
