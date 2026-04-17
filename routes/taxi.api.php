<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Taxi\Http\Controllers\TaxiFinanceController;
use App\Domains\Taxi\Http\Controllers\TaxiAnalyticsController;
use App\Domains\Taxi\Http\Controllers\TaxiGeoController;
use App\Domains\Taxi\Http\Controllers\TaxiFleetManagementController;
use App\Domains\Taxi\Http\Controllers\TaxiDispatcherController;
use App\Domains\Taxi\Http\Controllers\TaxiDriverPortalController;
use App\Domains\Taxi\Http\Controllers\TaxiClientPortalController;

/*
|--------------------------------------------------------------------------
| Taxi API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'tenant'])->prefix('api/v1/taxi')->group(function () {
    
    // Finance Routes
    Route::prefix('finance')->group(function () {
        Route::post('rides/{rideId}/payment', [TaxiFinanceController::class, 'processPayment']);
        Route::post('transactions/{transactionId}/refund', [TaxiFinanceController::class, 'processRefund']);
        Route::get('drivers/{driverId}/financial-summary', [TaxiFinanceController::class, 'getDriverFinancialSummary']);
        Route::post('drivers/{driverId}/withdrawals', [TaxiFinanceController::class, 'createWithdrawal']);
        Route::post('withdrawals/{withdrawalId}/process', [TaxiFinanceController::class, 'processWithdrawal']);
        Route::get('drivers/{driverId}/transactions', [TaxiFinanceController::class, 'getTransactionHistory']);
    });

    // Analytics Routes
    Route::prefix('analytics')->group(function () {
        Route::post('daily-aggregation', [TaxiAnalyticsController::class, 'aggregateDailyAnalytics']);
        Route::post('drivers/{driverId}/aggregation', [TaxiAnalyticsController::class, 'aggregateDriverAnalytics']);
        Route::get('revenue', [TaxiAnalyticsController::class, 'getRevenueAnalytics']);
        Route::get('drivers/{driverId}/performance', [TaxiAnalyticsController::class, 'getDriverPerformanceReport']);
        Route::get('demand-prediction', [TaxiAnalyticsController::class, 'predictDemand']);
    });

    // Geo Routes
    Route::prefix('geo')->group(function () {
        Route::post('route/calculate', [TaxiGeoController::class, 'calculateRoute']);
        Route::post('distance/calculate', [TaxiGeoController::class, 'calculateDistance']);
        Route::post('duration/estimate', [TaxiGeoController::class, 'estimateDuration']);
        Route::get('drivers/nearby', [TaxiGeoController::class, 'findNearbyDrivers']);
        Route::post('eta/predict', [TaxiGeoController::class, 'predictPickupETA']);
        Route::post('zones', [TaxiGeoController::class, 'createGeoZone']);
        Route::get('zones/active', [TaxiGeoController::class, 'getActiveZones']);
        Route::put('drivers/{driverId}/location', [TaxiGeoController::class, 'updateDriverLocation']);
        Route::get('pricing/multipliers', [TaxiGeoController::class, 'getPricingMultipliers']);
    });

    // Fleet Management Routes
    Route::prefix('fleet')->group(function () {
        Route::post('vehicles', [TaxiFleetManagementController::class, 'addVehicle']);
        Route::post('vehicles/{vehicleId}/maintenance', [TaxiFleetManagementController::class, 'scheduleMaintenance']);
        Route::post('maintenance/{maintenanceId}/complete', [TaxiFleetManagementController::class, 'completeMaintenance']);
        Route::post('vehicles/{vehicleId}/inspection', [TaxiFleetManagementController::class, 'scheduleInspection']);
        Route::post('inspections/{inspectionId}/complete', [TaxiFleetManagementController::class, 'completeInspection']);
        Route::get('overview', [TaxiFleetManagementController::class, 'getFleetOverview']);
        Route::get('vehicles/{vehicleId}/maintenance-history', [TaxiFleetManagementController::class, 'getVehicleMaintenanceHistory']);
    });

    // Dispatcher Routes
    Route::prefix('dispatcher')->group(function () {
        Route::post('rides/{rideId}/assign-driver', [TaxiDispatcherController::class, 'assignDriver']);
        Route::post('queue/{queueId}/drivers/{driverId}/accept', [TaxiDispatcherController::class, 'acceptAssignment']);
        Route::post('queue/{queueId}/drivers/{driverId}/decline', [TaxiDispatcherController::class, 'declineAssignment']);
        Route::post('timeouts/process', [TaxiDispatcherController::class, 'processTimeouts']);
        Route::get('dashboard', [TaxiDispatcherController::class, 'getDashboard']);
    });

    // Driver Portal Routes
    Route::prefix('drivers')->group(function () {
        Route::get('{driverId}/dashboard', [TaxiDriverPortalController::class, 'getDashboard']);
        Route::get('{driverId}/earnings', [TaxiDriverPortalController::class, 'getEarnings']);
        Route::post('{driverId}/schedule', [TaxiDriverPortalController::class, 'createSchedule']);
        Route::post('{driverId}/documents', [TaxiDriverPortalController::class, 'uploadDocument']);
        Route::get('{driverId}/documents', [TaxiDriverPortalController::class, 'getDocuments']);
        Route::get('{driverId}/rides', [TaxiDriverPortalController::class, 'getRideHistory']);
        Route::put('{driverId}/availability', [TaxiDriverPortalController::class, 'toggleAvailability']);
        Route::get('{driverId}/performance', [TaxiDriverPortalController::class, 'getPerformanceReport']);
    });

    // Client Portal Routes
    Route::prefix('clients')->group(function () {
        Route::get('{userId}/dashboard', [TaxiClientPortalController::class, 'getDashboard']);
        Route::get('{userId}/rides', [TaxiClientPortalController::class, 'getRideHistory']);
        Route::post('{userId}/favorites/locations', [TaxiClientPortalController::class, 'addFavoriteLocation']);
        Route::post('{userId}/favorites/drivers/{driverId}', [TaxiClientPortalController::class, 'addFavoriteDriver']);
        Route::delete('{userId}/favorites/{uuid}', [TaxiClientPortalController::class, 'removeFavorite']);
        Route::get('{userId}/statistics', [TaxiClientPortalController::class, 'getStatistics']);
        Route::post('{userId}/rides/{rideUuid}/rate', [TaxiClientPortalController::class, 'rateRide']);
        Route::get('{userId}/rides/{rideUuid}/details', [TaxiClientPortalController::class, 'getRideDetails']);
    });
});
