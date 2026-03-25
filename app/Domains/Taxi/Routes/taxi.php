<?php declare(strict_types=1);

use App\Domains\Taxi\Http\Controllers\TaxiRideController;
use App\Domains\Taxi\Http\Controllers\TaxiDriverController;
use App\Domains\Taxi\Http\Controllers\TaxiFleetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Taxi Domain Routes
|--------------------------------------------------------------------------
| Такси, водители, автопарки, surge pricing
*/

$this->route->middleware(['auth:sanctum', 'tenant', 'rate-limit:taxi'])->prefix('taxi')->name('taxi.')->group(function () {
    
    // === TAXI RIDES (Поездки) ===
    $this->route->prefix('rides')->name('rides.')->group(function () {
        $this->route->get('/', [TaxiRideController::class, 'index'])->name('index');
        $this->route->get('/{ride}', [TaxiRideController::class, 'show'])->name('show');
        $this->route->post('/', [TaxiRideController::class, 'store'])->name('store');
        $this->route->post('/{ride}/accept', [TaxiRideController::class, 'accept'])->name('accept');
        $this->route->post('/{ride}/start', [TaxiRideController::class, 'start'])->name('start');
        $this->route->post('/{ride}/complete', [TaxiRideController::class, 'complete'])->name('complete');
        $this->route->post('/{ride}/cancel', [TaxiRideController::class, 'cancel'])->name('cancel');
        
        // Price estimation
        $this->route->post('/estimate', [TaxiRideController::class, 'estimate'])->name('estimate');
    });

    // === TAXI DRIVERS (Водители) ===
    $this->route->prefix('drivers')->name('drivers.')->group(function () {
        $this->route->get('/', [TaxiDriverController::class, 'index'])->name('index');
        $this->route->get('/{driver}', [TaxiDriverController::class, 'show'])->name('show');
        $this->route->post('/', [TaxiDriverController::class, 'store'])->name('store');
        $this->route->put('/{driver}', [TaxiDriverController::class, 'update'])->name('update');
        $this->route->delete('/{driver}', [TaxiDriverController::class, 'destroy'])->name('destroy');
        
        // Driver status
        $this->route->post('/{driver}/online', [TaxiDriverController::class, 'goOnline'])->name('online');
        $this->route->post('/{driver}/offline', [TaxiDriverController::class, 'goOffline'])->name('offline');
        $this->route->post('/{driver}/location', [TaxiDriverController::class, 'updateLocation'])->name('location');
    });

    // === TAXI FLEET (Автопарки) ===
    $this->route->prefix('fleets')->name('fleets.')->middleware('role:business')->group(function () {
        $this->route->get('/', [TaxiFleetController::class, 'index'])->name('index');
        $this->route->get('/{fleet}', [TaxiFleetController::class, 'show'])->name('show');
        $this->route->post('/', [TaxiFleetController::class, 'store'])->name('store');
        $this->route->put('/{fleet}', [TaxiFleetController::class, 'update'])->name('update');
        $this->route->delete('/{fleet}', [TaxiFleetController::class, 'destroy'])->name('destroy');
        
        // Fleet drivers
        $this->route->get('/{fleet}/drivers', [TaxiFleetController::class, 'drivers'])->name('drivers');
    });
});
