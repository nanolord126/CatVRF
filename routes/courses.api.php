<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Education\Courses\Http\Controllers\CourseController;
use App\Domains\Education\Courses\Http\Controllers\LessonController;
use App\Domains\Education\Courses\Http\Controllers\EnrollmentController;
use App\Domains\Education\Courses\Http\Controllers\CourseReviewController;
use App\Domains\Education\Courses\Http\Controllers\CertificateController;

Route::prefix('api')->group(function () {
    // Public routes
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::get('/courses/{id}/lessons', [LessonController::class, 'indexByCourse']);
    Route::get('/courses/{id}/reviews', [CourseReviewController::class, 'indexByCourse']);
    Route::get('/courses/categories', [CourseController::class, 'categories']);

    // Protected routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Enrollments
        Route::post('/enrollments', [EnrollmentController::class, 'store']);
        Route::get('/enrollments/my', [EnrollmentController::class, 'myEnrollments']);
        Route::get('/enrollments/{id}', [EnrollmentController::class, 'show']);
        Route::patch('/enrollments/{id}', [EnrollmentController::class, 'update']);
        Route::delete('/enrollments/{id}', [EnrollmentController::class, 'drop']);

        // Lesson Progress
        Route::patch('/enrollments/{enrollmentId}/lessons/{lessonId}/progress', [LessonController::class, 'updateProgress']);
        Route::get('/enrollments/{enrollmentId}/progress', [EnrollmentController::class, 'progress']);

        // Certificates
        Route::get('/certificates/my', [CertificateController::class, 'myGertificates']);
        Route::get('/certificates/{id}', [CertificateController::class, 'show']);
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
        Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify']);

        // Reviews
        Route::post('/courses/{id}/reviews', [CourseReviewController::class, 'store']);
        Route::get('/reviews/my', [CourseReviewController::class, 'myReviews']);
        Route::patch('/reviews/{id}', [CourseReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [CourseReviewController::class, 'delete']);

        // Instructor routes
        Route::post('/courses', [CourseController::class, 'store'])->can('create', 'App\Domains\Education\Courses\Models\Course');
        Route::patch('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'delete']);

        Route::post('/courses/{courseId}/lessons', [LessonController::class, 'store']);
        Route::patch('/lessons/{id}', [LessonController::class, 'update']);
        Route::delete('/lessons/{id}', [LessonController::class, 'delete']);

        Route::get('/courses/{id}/analytics', [CourseController::class, 'analytics']);
        Route::get('/courses/{id}/students', [EnrollmentController::class, 'courseStudents']);
    });
});
