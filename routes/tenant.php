<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Domains\Advertising\Http\Controllers\AdCampaignController;
use App\Domains\Apparel\Http\Controllers\ClothingController;
use App\Domains\Auto\Http\Controllers\VehicleController;
use App\Domains\BeautyShop\Http\Controllers\BeautyProductController;
use App\Domains\Beauty\Http\Controllers\BeautyController;
use App\Domains\Clinic\Http\Controllers\ClinicController;
use App\Domains\Communication\Http\Controllers\CommunicationController;
use App\Domains\ConstructionAndRepair\Construction\Http\Controllers\ProjectController;
use App\Domains\Delivery\Http\Controllers\DeliveryController;
use App\Domains\Education\Http\Controllers\EducationController;
use App\Domains\Electronics\Http\Controllers\ElectronicProductController;
use App\Domains\EventPlanning\Events\Http\Controllers\EventController;
use App\Domains\Food\Http\Controllers\FoodOrderController;
use App\Domains\Furniture\Http\Controllers\FurnitureItemController;
use App\Domains\Geo\Http\Controllers\GeoController;
use App\Domains\Hotel\Http\Controllers\HotelBookingController;
use App\Domains\Insurance\Http\Controllers\InsuranceController;
use App\Domains\Inventory\Http\Controllers\InventoryController;
use App\Domains\RealEstateRental\Http\Controllers\RentalController;
use App\Domains\RealEstateSales\Http\Controllers\SalesController;
use App\Domains\RealEstate\Http\Controllers\RealEstateController;
use App\Domains\Sports\Http\Controllers\SportsController;
use App\Domains\Taxi\Http\Controllers\TaxiRideController;
use App\Domains\Tourism\Http\Controllers\PackageController;
use App\Models\Wishlist;
use App\Models\WishlistItem;

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
        return 'This is your multi-tenant application. The id of the current tenant is '.tenant('id');
    });

    // Wishlist Public Routes
    Route::get('/wishlist/{slug}', function ($slug) {
        $wishlist = Wishlist::where('slug', $slug)->where('is_public', true)->firstOrFail();

        return view('wishlist.public', compact('wishlist'));
    })->name('wishlist.public');

    Route::post('/wishlist/pay/{itemId}', function ($itemId) {
        $item = WishlistItem::findOrFail($itemId);

        // Интеграция с платежным шлюзом
        return response()->json(['url' => '/payment/mock?amount='.($item->price_at_addition - $item->collected_amount)]);
    })->name('wishlist.pay');

    // API Routes для всех вертикалей (Filament)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Taxi
        Route::apiResource('taxi', TaxiRideController::class);

        // Food
        Route::apiResource('food', FoodOrderController::class);

        // Hotel
        Route::apiResource('hotel', HotelBookingController::class);

        // Sports
        Route::apiResource('sports', SportsController::class);

        // Clinic
        Route::apiResource('clinic', ClinicController::class);

        // Advertising
        Route::apiResource('advertising', AdCampaignController::class);

        // Geo
        Route::apiResource('geo', GeoController::class);

        // Delivery
        Route::apiResource('delivery', DeliveryController::class);

        // Inventory
        Route::apiResource('inventory', InventoryController::class);

        // Education
        Route::apiResource('education', EducationController::class);

        // Events
        Route::apiResource('events', EventController::class);

        // Beauty
        Route::apiResource('beauty', BeautyController::class);

        // RealEstate
        Route::apiResource('real-estate', RealEstateController::class);

        // Insurance
        Route::apiResource('insurance', InsuranceController::class);

        // Communication
        Route::apiResource('communication', CommunicationController::class);

        // Auto (Vehicles)
        Route::apiResource('auto', VehicleController::class);

        // Electronics
        Route::apiResource('electronics', ElectronicProductController::class);

        // Apparel
        Route::apiResource('apparel', ClothingController::class);

        // Tourism
        Route::apiResource('tourism', PackageController::class);

        // Furniture
        Route::apiResource('furniture', FurnitureItemController::class);

        // Construction
        Route::apiResource('construction', ProjectController::class);

        // Real Estate Rental
        Route::apiResource('rental', RentalController::class);

        // Real Estate Sales
        Route::apiResource('sales', SalesController::class);

        // Beauty Shop (Cosmetics & Perfumery)
        Route::apiResource('beauty-shop', BeautyProductController::class);
    });
});
