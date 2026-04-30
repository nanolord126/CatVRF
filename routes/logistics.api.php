<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Logistics\Http\Controllers\CourierServiceController;
use App\Domains\Logistics\Http\Controllers\CourierTypeAssignmentController;
use App\Domains\Logistics\Http\Controllers\ShipmentController;
use App\Domains\Logistics\Http\Controllers\CourierRatingController;
use App\Domains\Logistics\Http\Controllers\ShipmentTrackingController;
use App\Domains\Logistics\Http\Controllers\ShipmentInsuranceController;
use App\Domains\Logistics\Http\Controllers\PickupPointController;
use App\Domains\Logistics\Http\Controllers\PvzAssignmentController;
use App\Domains\Logistics\Http\Controllers\UnifiedFleetController;

// Routes are prefixed with /logistics in api.php
Route::get('/couriers', [CourierServiceController::class, 'index']);
Route::get('/couriers/{id}', [CourierServiceController::class, 'show']);
Route::get('/couriers/{id}/ratings', [CourierRatingController::class, 'getCourierRatings']);
Route::get('/shipments/{trackingNumber}', [ShipmentController::class, 'trackByNumber']);

// Unified Logistics Core - Pickup Points (ПВЗ)
Route::prefix('pickup-points')->group(function () {
    Route::get('/', [PickupPointController::class, 'index']);
    Route::get('/{pickupPoint}', [PickupPointController::class, 'show']);
    Route::get('/nearby', [PickupPointController::class, 'nearby']);
    Route::post('/assign', [PickupPointController::class, 'assign']);
});

// Unified Fleet Service - Courier + Taxi Hybrid
Route::prefix('fleet')->group(function () {
    Route::post('/assign', [UnifiedFleetController::class, 'assignShipment']);
    Route::get('/couriers/available', [UnifiedFleetController::class, 'getAvailableCouriers']);
    Route::get('/couriers/nearby', [UnifiedFleetController::class, 'getNearbyCouriers']);
});

// Multi-Type Courier Assignment (New Strategy)
Route::prefix('courier-type')->group(function () {
    Route::post('/assign', [CourierTypeAssignmentController::class, 'assign']);
    Route::post('/calculate-cost', [CourierTypeAssignmentController::class, 'calculateCost']);
    Route::post('/validate-time-window', [CourierTypeAssignmentController::class, 'validateTimeWindow']);
    Route::post('/best-type-for-window', [CourierTypeAssignmentController::class, 'getBestTypeForWindow']);
    Route::post('/predict-eta', [CourierTypeAssignmentController::class, 'predictEta']);
    Route::post('/recommended-slots', [CourierTypeAssignmentController::class, 'getRecommendedSlots']);
});

// PVZ Assignment Service
Route::prefix('pvz')->group(function () {
    Route::post('/assign', [PvzAssignmentController::class, 'assignToPvz']);
    Route::post('/release-hold', [PvzAssignmentController::class, 'releasePvzHold']);
    Route::get('/nearby', [PvzAssignmentController::class, 'getNearbyPvz']);
});

// Auth endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::get('/shipments/my', [ShipmentController::class, 'myShipments']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
    Route::patch('/shipments/{id}', [ShipmentController::class, 'update']);
    Route::delete('/shipments/{id}', [ShipmentController::class, 'cancel']);

    Route::get('/shipments/{id}/tracking', [ShipmentTrackingController::class, 'getHistory']);
    Route::post('/shipments/{id}/rate', [CourierRatingController::class, 'rateShipment']);
    Route::get('/shipments/{id}/insurance', [ShipmentInsuranceController::class, 'getInsurance']);
    Route::post('/shipments/{id}/insurance', [ShipmentInsuranceController::class, 'addInsurance']);

    Route::post('/couriers', [CourierServiceController::class, 'register']);
    Route::get('/courier/profile', [CourierServiceController::class, 'myProfile']);
    Route::patch('/courier/profile', [CourierServiceController::class, 'updateProfile']);
    Route::get('/courier/shipments', [CourierServiceController::class, 'myShipments']);
    Route::get('/courier/earnings', [CourierServiceController::class, 'myEarnings']);
    Route::patch('/courier/shipments/{id}/status', [CourierServiceController::class, 'updateShipmentStatus']);
});

// Admin endpoints
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/couriers/verify', [CourierServiceController::class, 'verifyCourier']);
    Route::patch('/couriers/{id}', [CourierServiceController::class, 'update']);
    Route::delete('/couriers/{id}', [CourierServiceController::class, 'delete']);

    Route::get('/shipments/all', [ShipmentController::class, 'all']);
    Route::patch('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);
    Route::get('/analytics/couriers', [CourierServiceController::class, 'analytics']);
    Route::get('/analytics/shipments', [ShipmentController::class, 'analytics']);
});
