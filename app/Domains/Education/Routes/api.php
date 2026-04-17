<?php declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;

Route::prefix('education')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Education\Http\Controllers\EnrollmentController::class, 'index']);
            Route::post('/', [\App\Domains\Education\Http\Controllers\EnrollmentController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Education\Http\Controllers\EnrollmentController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Education\Http\Controllers\EnrollmentController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Education\Http\Controllers\EnrollmentController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Education\Http\Controllers\B2BEnrollmentController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Education\Http\Controllers\B2BEnrollmentController::class, 'bulkOrder']);
                
                // B2B Vertical Training Routes
                Route::prefix('verticals/{vertical}')->group(function () {
                    Route::get('/courses', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'getCoursesForVertical']);
                    Route::get('/courses/required', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'getRequiredCoursesForVertical']);
                    Route::get('/roles/{role}/recommendations', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'getRecommendedCoursesForRole']);
                    Route::post('/enroll-employee', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'enrollEmployeeInRequiredCourses']);
                    Route::post('/courses/{course}/enroll', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'enrollEmployeeInCourse']);
                    Route::get('/employees/{employee}/progress', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'getEmployeeProgressForVertical']);
                    Route::get('/company/progress', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'getCompanyProgressForVertical']);
                });
                
                Route::prefix('vertical-courses')->group(function () {
                    Route::post('/', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'createVerticalCourse']);
                    Route::put('/{id}', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'updateVerticalCourse']);
                    Route::delete('/{id}', [\App\Domains\Education\Http\Controllers\B2BVerticalTrainingController::class, 'deleteVerticalCourse']);
                });
            });
    });
