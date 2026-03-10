<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });

    // Wishlist Public Routes
    Route::get('/wishlist/{slug}', function ($slug) {
        $wishlist = \App\Models\Wishlist::where('slug', $slug)->where('is_public', true)->firstOrFail();
        return view('wishlist.public', compact('wishlist'));
    })->name('wishlist.public');

    Route::post('/wishlist/pay/{itemId}', function ($itemId) {
        $item = \App\Models\WishlistItem::findOrFail($itemId);
        // Интеграция с платежным шлюзом
        return response()->json(['url' => '/payment/mock?amount=' . ($item->price_at_addition - $item->collected_amount)]);
    })->name('wishlist.pay');

    // API Routes для всех вертикалей (Filament)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Taxi
        Route::apiResource('taxi', \App\Domains\Taxi\Http\Controllers\TaxiRideController::class);
        
        // Food
        Route::apiResource('food', \App\Domains\Food\Http\Controllers\FoodOrderController::class);
        
        // Hotel
        Route::apiResource('hotel', \App\Domains\Hotel\Http\Controllers\HotelBookingController::class);
        
        // Sports
        Route::apiResource('sports', \App\Domains\Sports\Http\Controllers\SportsController::class);
        
        // Clinic
        Route::apiResource('clinic', \App\Domains\Clinic\Http\Controllers\ClinicController::class);
        
        // Advertising
        Route::apiResource('advertising', \App\Domains\Advertising\Http\Controllers\AdvertisingController::class);
        
        // Geo
        Route::apiResource('geo', \App\Domains\Geo\Http\Controllers\GeoController::class);
        
        // Delivery
        Route::apiResource('delivery', \App\Domains\Delivery\Http\Controllers\DeliveryController::class);
        
        // Inventory
        Route::apiResource('inventory', \App\Domains\Inventory\Http\Controllers\InventoryController::class);
        
        // Education
        Route::apiResource('education', \App\Domains\Education\Http\Controllers\EducationController::class);
        
        // Events
        Route::apiResource('events', \App\Domains\Events\Http\Controllers\EventController::class);
        
        // Beauty
        Route::apiResource('beauty', \App\Domains\Beauty\Http\Controllers\BeautyController::class);
        
        // RealEstate
        Route::apiResource('real-estate', \App\Domains\RealEstate\Http\Controllers\RealEstateController::class);
        
        // Insurance
        Route::apiResource('insurance', \App\Domains\Insurance\Http\Controllers\InsuranceController::class);
        
        // Communication
        Route::apiResource('communication', \App\Domains\Communication\Http\Controllers\CommunicationController::class);
        
        // Auto (Vehicles)
        Route::apiResource('auto', \App\Domains\Auto\Http\Controllers\VehicleController::class);
        
        // Electronics
        Route::apiResource('electronics', \App\Domains\Electronics\Http\Controllers\ElectronicProductController::class);
        
        // Apparel
        Route::apiResource('apparel', \App\Domains\Apparel\Http\Controllers\ClothingController::class);
        
        // Tourism
        Route::apiResource('tourism', \App\Domains\Tourism\Http\Controllers\PackageController::class);
        
        // Furniture
        Route::apiResource('furniture', \App\Domains\Furniture\Http\Controllers\FurnitureItemController::class);
        
        // Construction
        Route::apiResource('construction', \App\Domains\Construction\Http\Controllers\ProjectController::class);
    });
});
