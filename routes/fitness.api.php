<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Sports\Fitness\Http\Controllers\GymController;
use App\Domains\Sports\Fitness\Http\Controllers\FitnessClassController;
use App\Domains\Sports\Fitness\Http\Controllers\TrainerController;
use App\Domains\Sports\Fitness\Http\Controllers\MembershipController;
use App\Domains\Sports\Fitness\Http\Controllers\AttendanceController;

Route::prefix('fitness')->group(function () {
    Route::get('/gyms', [GymController::class, 'index']);
    Route::get('/gyms/{id}', [GymController::class, 'show']);
    Route::get('/classes', [FitnessClassController::class, 'index']);
    Route::get('/classes/{id}', [FitnessClassController::class, 'show']);
    Route::get('/trainers', [TrainerController::class, 'index']);
    Route::get('/trainers/{id}', [TrainerController::class, 'show']);
    Route::get('/trainers/{id}/classes', [TrainerController::class, 'myClasses']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('/memberships', [MembershipController::class, 'store']);
        Route::get('/memberships/my', [MembershipController::class, 'myMemberships']);
        Route::get('/memberships/{id}', [MembershipController::class, 'show']);
        Route::patch('/memberships/{id}', [MembershipController::class, 'update']);
        Route::delete('/memberships/{id}', [MembershipController::class, 'cancel']);

        Route::get('/classes/{id}/schedule', [FitnessClassController::class, 'getSchedule']);
        Route::post('/classes/{classId}/book', [MembershipController::class, 'bookClass']);
        Route::get('/my/classes', [FitnessClassController::class, 'myClasses']);

        Route::post('/attendance/{scheduleId}/check-in', [AttendanceController::class, 'checkIn']);
        Route::patch('/attendance/{id}/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/attendance/my', [AttendanceController::class, 'myAttendance']);

        Route::get('/metrics/my', [AttendanceController::class, 'myMetrics']);
        Route::post('/metrics', [AttendanceController::class, 'recordMetric']);

        Route::post('/trainers/register', [TrainerController::class, 'register']);
        Route::get('/trainer/profile', [TrainerController::class, 'myProfile']);
        Route::patch('/trainer/profile', [TrainerController::class, 'updateProfile']);
        Route::get('/trainer/schedule', [TrainerController::class, 'getSchedule']);
        Route::patch('/trainer/schedule', [TrainerController::class, 'updateSchedule']);
        Route::get('/trainer/earnings', [TrainerController::class, 'myEarnings']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'admin'])->group(function () {
        Route::post('/gyms', [GymController::class, 'store']);
        Route::patch('/gyms/{id}', [GymController::class, 'update']);
        Route::delete('/gyms/{id}', [GymController::class, 'delete']);

        Route::post('/classes', [FitnessClassController::class, 'store']);
        Route::patch('/classes/{id}', [FitnessClassController::class, 'update']);
        Route::delete('/classes/{id}', [FitnessClassController::class, 'delete']);

        Route::post('/schedule/{classId}', [FitnessClassController::class, 'addSchedule']);
        Route::patch('/schedule/{scheduleId}', [FitnessClassController::class, 'updateSchedule']);
        Route::delete('/schedule/{scheduleId}', [FitnessClassController::class, 'cancelSchedule']);

        Route::patch('/memberships/{id}/expire', [MembershipController::class, 'expire']);
        Route::get('/metrics/{memberId}', [AttendanceController::class, 'getMemberMetrics']);
    });
});
