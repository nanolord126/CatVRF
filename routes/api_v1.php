<?php

use App\Http\Controllers\Api\V1\AI\AIConstructorController;

Route::post('/ai-constructor/run', [AIConstructorController::class, 'run'])
    ->middleware('auth:sanctum')
    ->name('api.v1.ai.constructor.run');