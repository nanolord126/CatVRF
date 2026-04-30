<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Dental\Presentation\Http\Controllers\DentalController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('dental')->group(function () {
        Route::prefix('treatment-plans')->group(function () {
            Route::post('/', [DentalController::class, 'createTreatmentPlan'])
                ->name('dental.treatment-plans.create');

            Route::get('{planId}', [DentalController::class, 'getPlan'])
                ->name('dental.treatment-plans.show');

            Route::post('{planId}/activate', [DentalController::class, 'activatePlan'])
                ->name('dental.treatment-plans.activate');

            Route::post('{planId}/complete', [DentalController::class, 'completePlan'])
                ->name('dental.treatment-plans.complete');

            Route::post('{planId}/discount', [DentalController::class, 'applyDiscount'])
                ->name('dental.treatment-plans.discount');

            Route::post('{planId}/steps', [DentalController::class, 'addTreatmentStep'])
                ->name('dental.treatment-plans.steps.create');

            Route::post('{planId}/steps/{stepId}/schedule', [DentalController::class, 'scheduleStep'])
                ->name('dental.treatment-plans.steps.schedule');

            Route::post('{planId}/steps/{stepId}/complete', [DentalController::class, 'completeStep'])
                ->name('dental.treatment-plans.steps.complete');
        });

        Route::get('patients/{patientId}/treatment-plans', [DentalController::class, 'getPatientPlans'])
            ->name('dental.patients.plans');
    });
});
