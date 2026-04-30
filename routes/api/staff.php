<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CatCRM\Infrastructure\Http\Controllers\Api\StaffController;

/**
 * API Routes for Staff HRM — Layer 2: API/HTTP Layer
 *
 * Part of 9-layer architecture
 */
Route::middleware(['auth:sanctum', 'tenant'])->prefix('staff')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/{employeeId}', [StaffController::class, 'show'])->name('staff.show');
    Route::put('/{employeeId}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/{employeeId}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // AI/ML endpoints
    Route::post('/{employeeId}/analyze-performance', [StaffController::class, 'analyzePerformance'])
        ->name('staff.analyze-performance');
    Route::post('/{employeeId}/predict-burnout', [StaffController::class, 'predictBurnout'])
        ->name('staff.predict-burnout');

    // Gamification endpoints
    Route::get('/leaderboard', [StaffController::class, 'leaderboard'])
        ->name('staff.leaderboard');
    Route::post('/{employeeId}/award-badge', [StaffController::class, 'awardBadge'])
        ->name('staff.award-badge');

    // Schedule endpoints
    Route::get('/{employeeId}/shifts', [StaffController::class, 'shifts'])
        ->name('staff.shifts');
    Route::post('/{employeeId}/shifts', [StaffController::class, 'createShift'])
        ->name('staff.shifts.create');

    // Leave endpoints
    Route::post('/{employeeId}/leave', [StaffController::class, 'requestLeave'])
        ->name('staff.leave.request');
    Route::post('/leave/{leaveId}/approve', [StaffController::class, 'approveLeave'])
        ->name('staff.leave.approve');

    // Wellness endpoints
    Route::post('/{employeeId}/wellness', [StaffController::class, 'recordWellness'])
        ->name('staff.wellness');

    // Social endpoints
    Route::post('/{employeeId}/peer-review', [StaffController::class, 'submitPeerReview'])
        ->name('staff.peer-review');

    // Manager dashboard
    Route::get('/dashboard/manager', [StaffController::class, 'managerDashboard'])
        ->name('staff.dashboard.manager');

    // Onboarding
    Route::post('/{employeeId}/onboard', [StaffController::class, 'onboard'])
        ->name('staff.onboard');
});
