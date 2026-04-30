<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Veterinary\Presentation\Http\Controllers\AppointmentController;

Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])
    ->prefix('api/v1/veterinary')
    ->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'create'])
            ->name('api.veterinary.appointments.create');

        Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])
            ->name('api.veterinary.appointments.cancel');

        Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm'])
            ->name('api.veterinary.appointments.confirm');

        Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete'])
            ->name('api.veterinary.appointments.complete');
    });
