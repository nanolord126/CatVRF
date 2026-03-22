<?php declare(strict_types=1);

use App\Domains\Auto\Http\Controllers\AutoPartController;
use App\Domains\Auto\Http\Controllers\AutoServiceOrderController;
use App\Domains\Auto\Http\Controllers\CarWashBookingController;
use App\Domains\Auto\Http\Controllers\CarDetailingController;
use App\Domains\Auto\Http\Controllers\VehicleInspectionController;
use App\Domains\Auto\Http\Controllers\VehicleInsuranceController;
use App\Domains\Auto\Http\Controllers\TowingController;
use App\Domains\Auto\Http\Controllers\ParkingController;
use App\Domains\Auto\Http\Controllers\VehicleRentalController;
use App\Domains\Auto\Http\Controllers\TuningController;
use App\Domains\Auto\Http\Controllers\PartWarrantyController;
use App\Domains\Auto\Http\Controllers\ServiceWarrantyController;
use App\Domains\Auto\Http\Controllers\B2BAutoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auto Domain Routes
|--------------------------------------------------------------------------
| СТО, мойки, детейлинг, запчасти, тюнинг
*/

Route::middleware(['auth:sanctum', 'tenant', 'rate-limit:auto'])->prefix('auto')->name('auto.')->group(function () {
    
    // === AUTO PARTS (Запчасти) ===
    Route::prefix('parts')->name('parts.')->group(function () {
        Route::get('/', [AutoPartController::class, 'index'])->name('index');
        Route::get('/{part}', [AutoPartController::class, 'show'])->name('show');
        Route::post('/', [AutoPartController::class, 'store'])->name('store');
        Route::put('/{part}', [AutoPartController::class, 'update'])->name('update');
        Route::delete('/{part}', [AutoPartController::class, 'destroy'])->name('destroy');
        
        // VIN compatibility check
        Route::post('/check-compatibility', [AutoPartController::class, 'checkCompatibility'])->name('check-compatibility');
    });

    // === AUTO SERVICE ORDERS (СТО заказы) ===
    Route::prefix('service-orders')->name('service-orders.')->group(function () {
        Route::get('/', [AutoServiceOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [AutoServiceOrderController::class, 'show'])->name('show');
        Route::post('/', [AutoServiceOrderController::class, 'store'])->name('store');
        Route::put('/{order}', [AutoServiceOrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [AutoServiceOrderController::class, 'destroy'])->name('destroy');
        
        // Complete service order
        Route::post('/{order}/complete', [AutoServiceOrderController::class, 'complete'])->name('complete');
        Route::post('/{order}/cancel', [AutoServiceOrderController::class, 'cancel'])->name('cancel');
    });

    // === CAR WASH (Мойки) ===
    Route::prefix('car-wash')->name('car-wash.')->group(function () {
        Route::get('/', [CarWashBookingController::class, 'index'])->name('index');
        Route::get('/{booking}', [CarWashBookingController::class, 'show'])->name('show');
        Route::post('/', [CarWashBookingController::class, 'store'])->name('store');
        Route::put('/{booking}', [CarWashBookingController::class, 'update'])->name('update');
        Route::delete('/{booking}', [CarWashBookingController::class, 'destroy'])->name('destroy');
        
        // Complete/cancel booking
        Route::post('/{booking}/complete', [CarWashBookingController::class, 'complete'])->name('complete');
        Route::post('/{booking}/cancel', [CarWashBookingController::class, 'cancel'])->name('cancel');
    });

    // === CAR DETAILING (Детейлинг) ===
    Route::prefix('detailing')->name('detailing.')->group(function () {
        Route::get('/', [CarDetailingController::class, 'index'])->name('index');
        Route::get('/{detailing}', [CarDetailingController::class, 'show'])->name('show');
        Route::post('/', [CarDetailingController::class, 'store'])->name('store');
        Route::put('/{detailing}', [CarDetailingController::class, 'update'])->name('update');
        Route::delete('/{detailing}', [CarDetailingController::class, 'destroy'])->name('destroy');
        
        // Complete/cancel detailing
        Route::post('/{detailing}/complete', [CarDetailingController::class, 'complete'])->name('complete');
        Route::post('/{detailing}/cancel', [CarDetailingController::class, 'cancel'])->name('cancel');
    });

    // === VEHICLE INSPECTION (Техосмотр) ===
    Route::prefix('inspection')->name('inspection.')->group(function () {
        Route::get('/', [VehicleInspectionController::class, 'index'])->name('index');
        Route::get('/{inspection}', [VehicleInspectionController::class, 'show'])->name('show');
        Route::post('/', [VehicleInspectionController::class, 'store'])->name('store');
        Route::put('/{inspection}', [VehicleInspectionController::class, 'update'])->name('update');
        Route::delete('/{inspection}', [VehicleInspectionController::class, 'destroy'])->name('destroy');
        
        // Pass/fail inspection
        Route::post('/{inspection}/pass', [VehicleInspectionController::class, 'pass'])->name('pass');
        Route::post('/{inspection}/fail', [VehicleInspectionController::class, 'fail'])->name('fail');
    });

    // === VEHICLE INSURANCE (Страхование ОСАГО/КАСКО) ===
    Route::prefix('insurance')->name('insurance.')->group(function () {
        Route::get('/', [VehicleInsuranceController::class, 'index'])->name('index');
        Route::get('/{insurance}', [VehicleInsuranceController::class, 'show'])->name('show');
        Route::post('/calculate', [VehicleInsuranceController::class, 'calculate'])->name('calculate');
        Route::post('/', [VehicleInsuranceController::class, 'store'])->name('store');
        Route::delete('/{insurance}', [VehicleInsuranceController::class, 'destroy'])->name('destroy');
    });

    // === TOWING / EVACUATION (Эвакуатор) ===
    Route::prefix('towing')->name('towing.')->group(function () {
        Route::get('/', [TowingController::class, 'index'])->name('index');
        Route::get('/{towing}', [TowingController::class, 'show'])->name('show');
        Route::post('/', [TowingController::class, 'store'])->name('store');
        Route::put('/{towing}', [TowingController::class, 'update'])->name('update');
        Route::post('/{towing}/complete', [TowingController::class, 'complete'])->name('complete');
        Route::post('/{towing}/cancel', [TowingController::class, 'cancel'])->name('cancel');
    });

    // === PARKING (Парковка) ===
    Route::prefix('parking')->name('parking.')->group(function () {
        Route::get('/lots', [ParkingController::class, 'lots'])->name('lots');
        Route::get('/bookings', [ParkingController::class, 'index'])->name('index');
        Route::get('/{booking}', [ParkingController::class, 'show'])->name('show');
        Route::post('/', [ParkingController::class, 'store'])->name('store');
        Route::post('/{booking}/complete', [ParkingController::class, 'complete'])->name('complete');
    });

    // === VEHICLE RENTAL (Аренда авто) ===
    Route::prefix('rental')->name('rental.')->group(function () {
        Route::get('/', [VehicleRentalController::class, 'index'])->name('index');
        Route::get('/{rental}', [VehicleRentalController::class, 'show'])->name('show');
        Route::post('/', [VehicleRentalController::class, 'store'])->name('store');
        Route::put('/{rental}', [VehicleRentalController::class, 'update'])->name('update');
        Route::post('/{rental}/pickup', [VehicleRentalController::class, 'pickup'])->name('pickup');
        Route::post('/{rental}/return', [VehicleRentalController::class, 'return'])->name('return');
    });

    // === TUNING (Тюнинг) ===
    Route::prefix('tuning')->name('tuning.')->group(function () {
        Route::get('/', [TuningController::class, 'index'])->name('index');
        Route::get('/{tuning}', [TuningController::class, 'show'])->name('show');
        Route::post('/', [TuningController::class, 'store'])->name('store');
        Route::put('/{tuning}', [TuningController::class, 'update'])->name('update');
        Route::post('/{tuning}/complete', [TuningController::class, 'complete'])->name('complete');
    });

    // === PART WARRANTY (Гарантия на запчасти) ===
    Route::prefix('warranties/parts')->name('warranties.parts.')->group(function () {
        Route::get('/', [PartWarrantyController::class, 'index'])->name('index');
        Route::get('/{warranty}', [PartWarrantyController::class, 'show'])->name('show');
        Route::post('/', [PartWarrantyController::class, 'store'])->name('store');
        
        // Warranty claims
        Route::post('/{warranty}/claim', [PartWarrantyController::class, 'claim'])->name('claim');
        Route::post('/{warranty}/approve', [PartWarrantyController::class, 'approve'])->name('approve');
        Route::post('/{warranty}/reject', [PartWarrantyController::class, 'reject'])->name('reject');
    });

    // === SERVICE WARRANTY (Гарантия на ремонт) ===
    Route::prefix('warranties/services')->name('warranties.services.')->group(function () {
        Route::get('/', [ServiceWarrantyController::class, 'index'])->name('index');
        Route::get('/{warranty}', [ServiceWarrantyController::class, 'show'])->name('show');
        Route::post('/', [ServiceWarrantyController::class, 'store'])->name('store');
        
        // Warranty claims
        Route::post('/{warranty}/claim', [ServiceWarrantyController::class, 'claim'])->name('claim');
        Route::post('/{warranty}/approve', [ServiceWarrantyController::class, 'approve'])->name('approve');
        Route::post('/{warranty}/reject', [ServiceWarrantyController::class, 'reject'])->name('reject');
    });

    // === B2B AUTO (Оптовые продажи запчастей) ===
    Route::prefix('b2b')->name('b2b.')->middleware('role:business')->group(function () {
        Route::get('/parts', [B2BAutoController::class, 'index'])->name('parts.index');
        Route::post('/order', [B2BAutoController::class, 'createOrder'])->name('order.create');
        Route::get('/suppliers', [B2BAutoController::class, 'suppliers'])->name('suppliers.index');
    });
});
