<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;
use App\Domains\Education\Http\Controllers\B2BEnrollmentController;
use App\Domains\Education\Http\Controllers\B2BVerticalTrainingController;
use App\Domains\Education\Http\Controllers\EnrollmentController;

Route::prefix('education')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [EnrollmentController::class, 'index']);
            Route::post('/', [EnrollmentController::class, 'store']);
            Route::get('/{id}', [EnrollmentController::class, 'show']);
            Route::put('/{id}', [EnrollmentController::class, 'update']);
            Route::delete('/{id}', [EnrollmentController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BEnrollmentController::class, 'catalog']);
                Route::post('/bulk-order', [B2BEnrollmentController::class, 'bulkOrder']);

                // B2B Vertical Training Routes
                Route::prefix('verticals/{vertical}')->group(function () {
                    Route::get('/courses', [B2BVerticalTrainingController::class, 'getCoursesForVertical']);
                    Route::get('/courses/required', [B2BVerticalTrainingController::class, 'getRequiredCoursesForVertical']);
                    Route::get('/roles/{role}/recommendations', [B2BVerticalTrainingController::class, 'getRecommendedCoursesForRole']);
                    Route::post('/enroll-employee', [B2BVerticalTrainingController::class, 'enrollEmployeeInRequiredCourses']);
                    Route::post('/courses/{course}/enroll', [B2BVerticalTrainingController::class, 'enrollEmployeeInCourse']);
                    Route::get('/employees/{employee}/progress', [B2BVerticalTrainingController::class, 'getEmployeeProgressForVertical']);
                    Route::get('/company/progress', [B2BVerticalTrainingController::class, 'getCompanyProgressForVertical']);
                });

                Route::prefix('vertical-courses')->group(function () {
                    Route::post('/', [B2BVerticalTrainingController::class, 'createVerticalCourse']);
                    Route::put('/{id}', [B2BVerticalTrainingController::class, 'updateVerticalCourse']);
                    Route::delete('/{id}', [B2BVerticalTrainingController::class, 'deleteVerticalCourse']);
                });
            });
    });
