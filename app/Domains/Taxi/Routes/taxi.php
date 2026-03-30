<?php declare(strict_types=1);

namespace App\Domains\Taxi\Routes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class taxi extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // === TAXI RIDES (Поездки) ===
        Route::prefix('rides')->name('rides.')->group(function () {
            Route::get('/', [TaxiRideController::class, 'index'])->name('index');
            Route::get('/{ride}', [TaxiRideController::class, 'show'])->name('show');
            Route::post('/', [TaxiRideController::class, 'store'])->name('store');
            Route::post('/{ride}/accept', [TaxiRideController::class, 'accept'])->name('accept');
            Route::post('/{ride}/start', [TaxiRideController::class, 'start'])->name('start');
            Route::post('/{ride}/complete', [TaxiRideController::class, 'complete'])->name('complete');
            Route::post('/{ride}/cancel', [TaxiRideController::class, 'cancel'])->name('cancel');

            // Price estimation
            Route::post('/estimate', [TaxiRideController::class, 'estimate'])->name('estimate');
        });

        // === TAXI DRIVERS (Водители) ===
        Route::prefix('drivers')->name('drivers.')->group(function () {
            Route::get('/', [TaxiDriverController::class, 'index'])->name('index');
            Route::get('/{driver}', [TaxiDriverController::class, 'show'])->name('show');
            Route::post('/', [TaxiDriverController::class, 'store'])->name('store');
            Route::put('/{driver}', [TaxiDriverController::class, 'update'])->name('update');
            Route::delete('/{driver}', [TaxiDriverController::class, 'destroy'])->name('destroy');

            // Driver status
            Route::post('/{driver}/online', [TaxiDriverController::class, 'goOnline'])->name('online');
            Route::post('/{driver}/offline', [TaxiDriverController::class, 'goOffline'])->name('offline');
            Route::post('/{driver}/location', [TaxiDriverController::class, 'updateLocation'])->name('location');
        });

        // === TAXI FLEET (Автопарки) ===
        Route::prefix('fleets')->name('fleets.')->middleware('role:business')->group(function () {
            Route::get('/', [TaxiFleetController::class, 'index'])->name('index');
            Route::get('/{fleet}', [TaxiFleetController::class, 'show'])->name('show');
            Route::post('/', [TaxiFleetController::class, 'store'])->name('store');
            Route::put('/{fleet}', [TaxiFleetController::class, 'update'])->name('update');
            Route::delete('/{fleet}', [TaxiFleetController::class, 'destroy'])->name('destroy');

            // Fleet drivers
            Route::get('/{fleet}/drivers', [TaxiFleetController::class, 'drivers'])->name('drivers');
        });
}
