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

$this->route->middleware(['auth:sanctum', 'tenant', 'rate-limit:auto'])->prefix('auto')->name('auto.')->group(function () {
    
    // === AUTO PARTS (Запчасти) ===
    $this->route->prefix('parts')->name('parts.')->group(function () {
        $this->route->get('/', [AutoPartController::class, 'index'])->name('index');
        $this->route->get('/{part}', [AutoPartController::class, 'show'])->name('show');
        $this->route->post('/', [AutoPartController::class, 'store'])->name('store');
        $this->route->put('/{part}', [AutoPartController::class, 'update'])->name('update');
        $this->route->delete('/{part}', [AutoPartController::class, 'destroy'])->name('destroy');
        
        // VIN compatibility check
        $this->route->post('/check-compatibility', [AutoPartController::class, 'checkCompatibility'])->name('check-compatibility');
    });

    // === AUTO SERVICE ORDERS (СТО заказы) ===
    $this->route->prefix('service-orders')->name('service-orders.')->group(function () {
        $this->route->get('/', [AutoServiceOrderController::class, 'index'])->name('index');
        $this->route->get('/{order}', [AutoServiceOrderController::class, 'show'])->name('show');
        $this->route->post('/', [AutoServiceOrderController::class, 'store'])->name('store');
        $this->route->put('/{order}', [AutoServiceOrderController::class, 'update'])->name('update');
        $this->route->delete('/{order}', [AutoServiceOrderController::class, 'destroy'])->name('destroy');
        
        // Complete service order
        $this->route->post('/{order}/complete', [AutoServiceOrderController::class, 'complete'])->name('complete');
        $this->route->post('/{order}/cancel', [AutoServiceOrderController::class, 'cancel'])->name('cancel');
    });

    // === CAR WASH (Мойки) ===
    $this->route->prefix('car-wash')->name('car-wash.')->group(function () {
        $this->route->get('/', [CarWashBookingController::class, 'index'])->name('index');
        $this->route->get('/{booking}', [CarWashBookingController::class, 'show'])->name('show');
        $this->route->post('/', [CarWashBookingController::class, 'store'])->name('store');
        $this->route->put('/{booking}', [CarWashBookingController::class, 'update'])->name('update');
        $this->route->delete('/{booking}', [CarWashBookingController::class, 'destroy'])->name('destroy');
        
        // Complete/cancel booking
        $this->route->post('/{booking}/complete', [CarWashBookingController::class, 'complete'])->name('complete');
        $this->route->post('/{booking}/cancel', [CarWashBookingController::class, 'cancel'])->name('cancel');
    });

    // === CAR DETAILING (Детейлинг) ===
    $this->route->prefix('detailing')->name('detailing.')->group(function () {
        $this->route->get('/', [CarDetailingController::class, 'index'])->name('index');
        $this->route->get('/{detailing}', [CarDetailingController::class, 'show'])->name('show');
        $this->route->post('/', [CarDetailingController::class, 'store'])->name('store');
        $this->route->put('/{detailing}', [CarDetailingController::class, 'update'])->name('update');
        $this->route->delete('/{detailing}', [CarDetailingController::class, 'destroy'])->name('destroy');
        
        // Complete/cancel detailing
        $this->route->post('/{detailing}/complete', [CarDetailingController::class, 'complete'])->name('complete');
        $this->route->post('/{detailing}/cancel', [CarDetailingController::class, 'cancel'])->name('cancel');
    });

    // === VEHICLE INSPECTION (Техосмотр) ===
    $this->route->prefix('inspection')->name('inspection.')->group(function () {
        $this->route->get('/', [VehicleInspectionController::class, 'index'])->name('index');
        $this->route->get('/{inspection}', [VehicleInspectionController::class, 'show'])->name('show');
        $this->route->post('/', [VehicleInspectionController::class, 'store'])->name('store');
        $this->route->put('/{inspection}', [VehicleInspectionController::class, 'update'])->name('update');
        $this->route->delete('/{inspection}', [VehicleInspectionController::class, 'destroy'])->name('destroy');
        
        // Pass/fail inspection
        $this->route->post('/{inspection}/pass', [VehicleInspectionController::class, 'pass'])->name('pass');
        $this->route->post('/{inspection}/fail', [VehicleInspectionController::class, 'fail'])->name('fail');
    });

    // === VEHICLE INSURANCE (Страхование ОСАГО/КАСКО) ===
    $this->route->prefix('insurance')->name('insurance.')->group(function () {
        $this->route->get('/', [VehicleInsuranceController::class, 'index'])->name('index');
        $this->route->get('/{insurance}', [VehicleInsuranceController::class, 'show'])->name('show');
        $this->route->post('/calculate', [VehicleInsuranceController::class, 'calculate'])->name('calculate');
        $this->route->post('/', [VehicleInsuranceController::class, 'store'])->name('store');
        $this->route->delete('/{insurance}', [VehicleInsuranceController::class, 'destroy'])->name('destroy');
    });

    // === TOWING / EVACUATION (Эвакуатор) ===
    $this->route->prefix('towing')->name('towing.')->group(function () {
        $this->route->get('/', [TowingController::class, 'index'])->name('index');
        $this->route->get('/{towing}', [TowingController::class, 'show'])->name('show');
        $this->route->post('/', [TowingController::class, 'store'])->name('store');
        $this->route->put('/{towing}', [TowingController::class, 'update'])->name('update');
        $this->route->post('/{towing}/complete', [TowingController::class, 'complete'])->name('complete');
        $this->route->post('/{towing}/cancel', [TowingController::class, 'cancel'])->name('cancel');
    });

    // === PARKING (Парковка) ===
    $this->route->prefix('parking')->name('parking.')->group(function () {
        $this->route->get('/lots', [ParkingController::class, 'lots'])->name('lots');
        $this->route->get('/bookings', [ParkingController::class, 'index'])->name('index');
        $this->route->get('/{booking}', [ParkingController::class, 'show'])->name('show');
        $this->route->post('/', [ParkingController::class, 'store'])->name('store');
        $this->route->post('/{booking}/complete', [ParkingController::class, 'complete'])->name('complete');
    });

    // === VEHICLE RENTAL (Аренда авто) ===
    $this->route->prefix('rental')->name('rental.')->group(function () {
        $this->route->get('/', [VehicleRentalController::class, 'index'])->name('index');
        $this->route->get('/{rental}', [VehicleRentalController::class, 'show'])->name('show');
        $this->route->post('/', [VehicleRentalController::class, 'store'])->name('store');
        $this->route->put('/{rental}', [VehicleRentalController::class, 'update'])->name('update');
        $this->route->post('/{rental}/pickup', [VehicleRentalController::class, 'pickup'])->name('pickup');
        $this->route->post('/{rental}/return', [VehicleRentalController::class, 'return'])->name('return');
    });

    // === TUNING (Тюнинг) ===
    $this->route->prefix('tuning')->name('tuning.')->group(function () {
        $this->route->get('/', [TuningController::class, 'index'])->name('index');
        $this->route->get('/{tuning}', [TuningController::class, 'show'])->name('show');
        $this->route->post('/', [TuningController::class, 'store'])->name('store');
        $this->route->put('/{tuning}', [TuningController::class, 'update'])->name('update');
        $this->route->post('/{tuning}/complete', [TuningController::class, 'complete'])->name('complete');
    });

    // === PART WARRANTY (Гарантия на запчасти) ===
    $this->route->prefix('warranties/parts')->name('warranties.parts.')->group(function () {
        $this->route->get('/', [PartWarrantyController::class, 'index'])->name('index');
        $this->route->get('/{warranty}', [PartWarrantyController::class, 'show'])->name('show');
        $this->route->post('/', [PartWarrantyController::class, 'store'])->name('store');
        
        // Warranty claims
        $this->route->post('/{warranty}/claim', [PartWarrantyController::class, 'claim'])->name('claim');
        $this->route->post('/{warranty}/approve', [PartWarrantyController::class, 'approve'])->name('approve');
        $this->route->post('/{warranty}/reject', [PartWarrantyController::class, 'reject'])->name('reject');
    });

    // === SERVICE WARRANTY (Гарантия на ремонт) ===
    $this->route->prefix('warranties/services')->name('warranties.services.')->group(function () {
        $this->route->get('/', [ServiceWarrantyController::class, 'index'])->name('index');
        $this->route->get('/{warranty}', [ServiceWarrantyController::class, 'show'])->name('show');
        $this->route->post('/', [ServiceWarrantyController::class, 'store'])->name('store');
        
        // Warranty claims
        $this->route->post('/{warranty}/claim', [ServiceWarrantyController::class, 'claim'])->name('claim');
        $this->route->post('/{warranty}/approve', [ServiceWarrantyController::class, 'approve'])->name('approve');
        $this->route->post('/{warranty}/reject', [ServiceWarrantyController::class, 'reject'])->name('reject');
    });

    // === B2B AUTO (Оптовые продажи запчастей) ===
    $this->route->prefix('b2b')->name('b2b.')->middleware('role:business')->group(function () {
        $this->route->get('/parts', [B2BAutoController::class, 'index'])->name('parts.index');
        $this->route->post('/order', [B2BAutoController::class, 'createOrder'])->name('order.create');
        $this->route->get('/suppliers', [B2BAutoController::class, 'suppliers'])->name('suppliers.index');
    });
});
