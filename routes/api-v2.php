<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\MedicalController;
use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Controllers\Api\V2\PaymentController;

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
|
| API v2 endpoints for breaking changes and new features.
| All routes here are prefixed with /api/v2
|
*/

// Health check
Route::get('/health', [HealthController::class, 'index'])
    ->name('api.v2.health');

// Medical vertical - v2 with enhanced security and rate limiting
Route::middleware(['auth:sanctum', 'tenant', 'ability:medical:diagnose'])
    ->prefix('/medical')
    ->group(function () {
        Route::post('/diagnose', [MedicalController::class, 'diagnose'])
            ->name('api.v2.medical.diagnose');

        Route::get('/diagnoses/{id}', [MedicalController::class, 'showDiagnosis'])
            ->name('api.v2.medical.diagnoses.show');
    });

// Example of breaking change - medical appointments with new structure
Route::middleware(['auth:sanctum', 'tenant', 'ability:medical:appointments'])
    ->prefix('/medical/appointments')
    ->group(function () {
        Route::post('/', [MedicalController::class, 'createAppointment'])
            ->name('api.v2.medical.appointments.create');

        Route::get('/', [MedicalController::class, 'listAppointments'])
            ->name('api.v2.medical.appointments.list');
    });

// Payment vertical - v2 with enhanced security
Route::middleware(['auth:sanctum', 'tenant', 'ability:payment:initiate'])
    ->prefix('/payments')
    ->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiate'])
            ->name('api.v2.payments.initiate');
    });
