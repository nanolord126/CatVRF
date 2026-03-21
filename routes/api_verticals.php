<?php
declare(strict_types=1);

use App\Http\Controllers\API\FarmDirectOrderController;
use App\Http\Controllers\API\HealthyFoodDietController;
use App\Http\Controllers\API\ConfectioneryOrderController;
use App\Http\Controllers\API\MeatShopsOrderController;
use App\Http\Controllers\API\OfficeCateringOrderController;
use App\Http\Controllers\API\FurnitureOrderController;
use App\Http\Controllers\API\ElectronicsOrderController;
use App\Http\Controllers\API\ToysKidsOrderController;
use App\Http\Controllers\API\AutoPartsOrderController;
use App\Http\Controllers\API\PharmacyOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'throttle:api'])->prefix('api/v1')->group(function () {
    // FarmDirect Routes
    Route::resource('farm-orders', FarmDirectOrderController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    // HealthyFood Routes
    Route::resource('diet-plans', HealthyFoodDietController::class)
        ->only(['index', 'store']);
    Route::post('diet-plans/{id}/subscribe', [HealthyFoodDietController::class, 'subscribe']);

    // Confectionery Routes
    Route::resource('bakery-orders', ConfectioneryOrderController::class)
        ->only(['index', 'store']);
    Route::post('bakery-orders/{id}/mark-ready', [ConfectioneryOrderController::class, 'markReady']);

    // MeatShops Routes
    Route::resource('meat-orders', MeatShopsOrderController::class)
        ->only(['index', 'store']);

    // OfficeCatering Routes
    Route::resource('catering-orders', OfficeCateringOrderController::class)
        ->only(['index', 'store']);
    Route::post('catering-orders/{id}/setup-recurring', [OfficeCateringOrderController::class, 'setupRecurring']);

    // Furniture Routes
    Route::resource('furniture-orders', FurnitureOrderController::class)
        ->only(['index', 'store']);
    Route::post('furniture-orders/{id}/schedule-delivery', [FurnitureOrderController::class, 'scheduleDelivery']);

    // Electronics Routes
    Route::resource('electronics-orders', ElectronicsOrderController::class)
        ->only(['index', 'store']);
    Route::post('electronics-orders/warranty-claim', [ElectronicsOrderController::class, 'claimWarranty']);

    // ToysKids Routes
    Route::resource('toy-orders', ToysKidsOrderController::class)
        ->only(['index', 'store']);

    // AutoParts Routes
    Route::resource('auto-parts-orders', AutoPartsOrderController::class)
        ->only(['index', 'store']);
    Route::get('auto-parts/compatible/{vin}', [AutoPartsOrderController::class, 'findCompatible']);

    // Pharmacy Routes
    Route::resource('pharmacy-orders', PharmacyOrderController::class)
        ->only(['index', 'store']);
    Route::post('pharmacy-orders/verify-prescription', [PharmacyOrderController::class, 'verifyPrescription']);
});
