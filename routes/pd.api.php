<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonalDevelopment\PersonalDevelopmentApiController;

/**
 * PersonalDevelopment Category — Production 2026
 * Appends to API v1 middleware group (auth, tenant, rate-limit)
 * 
 * @version 1.0.0
 * @author CatVRF
 */
Route::prefix('pd')->group(function () {

    // 1. Программы и зачисления
    Route::get('/programs', [PersonalDevelopmentApiController::class, 'indexPrograms'])
        ->name('v1.pd.programs.index');
        
    Route::post('/enroll', [PersonalDevelopmentApiController::class, 'enroll'])
        ->name('v1.pd.programs.enroll');

    // 2. AI-Growth Constructor
    Route::post('/ai-roadmap', [PersonalDevelopmentApiController::class, 'generateAiRoadmap'])
        ->name('v1.pd.ai-roadmap');

});
