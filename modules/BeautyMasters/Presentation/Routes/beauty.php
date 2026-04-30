<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\BeautyMasters\Presentation\Http\Controllers\BeautyMastersController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('beauty')->group(function () {
        Route::prefix('appointments')->group(function () {
            Route::post('/', [BeautyMastersController::class, 'createAppointment'])
                ->name('beauty.appointments.create');

            Route::put('{appointmentId}', [BeautyMastersController::class, 'updateAppointment'])
                ->name('beauty.appointments.update');

            Route::post('{appointmentId}/confirm', [BeautyMastersController::class, 'confirmAppointment'])
                ->name('beauty.appointments.confirm');

            Route::post('{appointmentId}/start', [BeautyMastersController::class, 'startAppointment'])
                ->name('beauty.appointments.start');

            Route::post('{appointmentId}/complete', [BeautyMastersController::class, 'completeAppointment'])
                ->name('beauty.appointments.complete');

            Route::post('{appointmentId}/cancel', [BeautyMastersController::class, 'cancelAppointment'])
                ->name('beauty.appointments.cancel');

            Route::get('{appointmentId}', [BeautyMastersController::class, 'getAppointment'])
                ->name('beauty.appointments.show');
        });

        Route::get('slots/available', [BeautyMastersController::class, 'getAvailableSlots'])
            ->name('beauty.slots.available');
    });
});
