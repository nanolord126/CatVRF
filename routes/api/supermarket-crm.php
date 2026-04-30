<?php

use Illuminate\Support\Facades\Route;
use Modules\CatCRM\Infrastructure\Http\Controllers\Api\SupermarketCRMController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Customer management
    Route::post('/crm/supermarket/customers', [SupermarketCRMController::class, 'getOrCreateCustomer']);
    Route::post('/crm/supermarket/customers/{customerId}/statistics', [SupermarketCRMController::class, 'updateCustomerStatistics']);

    // Order management
    Route::post('/crm/supermarket/orders', [SupermarketCRMController::class, 'createOrderDeal']);
    Route::post('/crm/supermarket/orders/{dealId}/status', [SupermarketCRMController::class, 'updateOrderStatus']);

    // Age verification
    Route::post('/crm/supermarket/orders/{dealId}/age-verification/check', [SupermarketCRMController::class, 'checkAgeVerification']);
    Route::post('/crm/supermarket/orders/{dealId}/age-verification', [SupermarketCRMController::class, 'recordAgeVerification']);

    // Subscriptions
    Route::post('/crm/supermarket/subscriptions', [SupermarketCRMController::class, 'createSubscriptionDeal']);
    Route::post('/crm/supermarket/subscriptions/{dealId}/delivery', [SupermarketCRMController::class, 'updateSubscriptionDelivery']);

    // Returns
    Route::post('/crm/supermarket/returns', [SupermarketCRMController::class, 'createReturnDeal']);

    // Analytics
    Route::get('/crm/supermarket/analytics', [SupermarketCRMController::class, 'getCustomerAnalytics']);
    Route::get('/crm/supermarket/customers/top', [SupermarketCRMController::class, 'getTopCustomers']);
});
