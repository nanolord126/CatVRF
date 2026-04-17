<?php
declare(strict_types=1);

/**
 * ══════════════════════════════════════════════════════════════
 * ROADMAP МАРШРУТОВ — ОЧЕРЕДИ РЕАЛИЗАЦИИ
 * ══════════════════════════════════════════════════════════════
 * Q2 — Confectionery, MeatShops, Furniture, AutoParts, Pharmacy
 * Q3 — FarmDirect, HealthyFood, OfficeCatering, Electronics, ToysKids
 *      (ЗАБЛОКИРОВАНО — ждёт завершения Q1+Q2)
 * ══════════════════════════════════════════════════════════════
 */

// ── Q2 импорты (активно) ─────────────────────────────────────
use App\Http\Controllers\API\ConfectioneryOrderController;
use App\Http\Controllers\API\MeatShopsOrderController;
use App\Http\Controllers\API\FurnitureOrderController;
use App\Http\Controllers\API\AutoPartsOrderController;
use App\Http\Controllers\API\PharmacyOrderController;
// ── Domain-based vertical controllers ───────────────────────────
use App\Domains\Beauty\Http\Controllers\OrderController as BeautyOrderController;
use App\Domains\Food\Http\Controllers\OrderController as FoodOrderController;
use App\Domains\Fashion\Http\Controllers\OrderController as FashionOrderController;
use App\Domains\RealEstate\Http\Controllers\OrderController as RealEstateOrderController;
use App\Domains\Travel\Http\Controllers\OrderController as TravelOrderController;
use App\Domains\Auto\Http\Controllers\OrderController as AutoOrderController;
use App\Domains\Medical\Http\Controllers\OrderController as MedicalOrderController;
use App\Domains\Electronics\Http\Controllers\OrderController as ElectronicsOrderController;
use App\Domains\Fitness\Http\Controllers\OrderController as FitnessOrderController;
use App\Domains\Sports\Http\Controllers\OrderController as SportsOrderController;
use App\Domains\Luxury\Http\Controllers\OrderController as LuxuryOrderController;
use App\Domains\Education\Http\Controllers\OrderController as EducationOrderController;
use App\Domains\Insurance\Http\Controllers\OrderController as InsuranceOrderController;
use App\Domains\Logistics\Http\Controllers\OrderController as LogisticsOrderController;
use App\Domains\CRM\Http\Controllers\OrderController as CRMOrderController;
use App\Domains\Delivery\Http\Controllers\OrderController as DeliveryOrderController;
use App\Domains\Hotels\Http\Controllers\OrderController as HotelsOrderController;
use App\Domains\Payment\Http\Controllers\OrderController as PaymentOrderController;
use App\Domains\Legal\Http\Controllers\OrderController as LegalOrderController;
use App\Domains\Taxi\Http\Controllers\OrderController as TaxiOrderController;
use App\Domains\Flowers\Http\Controllers\OrderController as FlowersOrderController;
use App\Domains\Furniture\Http\Controllers\OrderController as FurnitureDomainOrderController;
use App\Domains\Pharmacy\Http\Controllers\OrderController as PharmacyDomainOrderController;
use App\Domains\Analytics\Http\Controllers\OrderController as AnalyticsOrderController;
use App\Domains\Consulting\Http\Controllers\OrderController as ConsultingOrderController;
use App\Domains\Freelance\Http\Controllers\OrderController as FreelanceOrderController;
use App\Domains\EventPlanning\Http\Controllers\OrderController as EventPlanningOrderController;
use App\Domains\Wallet\Http\Controllers\OrderController as WalletOrderController;
use App\Domains\Pet\Http\Controllers\OrderController as PetOrderController;
use App\Domains\Veterinary\Http\Controllers\OrderController as VeterinaryOrderController;
use App\Domains\ToysAndGames\Http\Controllers\OrderController as ToysAndGamesOrderController;
use App\Domains\Advertising\Http\Controllers\OrderController as AdvertisingOrderController;
use App\Domains\CarRental\Http\Controllers\OrderController as CarRentalOrderController;
use App\Domains\Finances\Http\Controllers\OrderController as FinancesOrderController;
use App\Domains\Photography\Http\Controllers\OrderController as PhotographyOrderController;
use App\Domains\ShortTermRentals\Http\Controllers\OrderController as ShortTermRentalsOrderController;
use App\Domains\SportsNutrition\Http\Controllers\OrderController as SportsNutritionOrderController;
use App\Domains\PersonalDevelopment\Http\Controllers\OrderController as PersonalDevelopmentOrderController;
use App\Domains\HomeServices\Http\Controllers\OrderController as HomeServicesOrderController;
use App\Domains\Gardening\Http\Controllers\OrderController as GardeningOrderController;
use App\Domains\GroceryAndDelivery\Http\Controllers\OrderController as GroceryAndDeliveryOrderController;
use App\Domains\FarmDirect\Http\Controllers\OrderController as FarmDirectOrderController;
use App\Domains\Content\Http\Controllers\OrderController as ContentOrderController;
use App\Domains\Staff\Http\Controllers\OrderController as StaffOrderController;
use App\Domains\Inventory\Http\Controllers\OrderController as InventoryOrderController;
use App\Domains\Tickets\Http\Controllers\OrderController as TicketsOrderController;
use App\Domains\Geo\Http\Controllers\OrderController as GeoOrderController;
use App\Domains\Marketplace\Http\Controllers\OrderController as MarketplaceOrderController;
use App\Domains\Art\Http\Controllers\OrderController as ArtOrderController;
use Illuminate\Support\Facades\Route;

// ── Q3 импорты (ЗАБЛОКИРОВАНО — раскомментировать после Q1+Q2) ─
// use App\Http\Controllers\API\FarmDirectOrderController;
// use App\Http\Controllers\API\HealthyFoodDietController;
// use App\Http\Controllers\API\OfficeCateringOrderController;
// use App\Http\Controllers\API\ElectronicsOrderController;
// use App\Http\Controllers\API\ToysKidsOrderController;

Route::middleware(['api', 'auth:sanctum', 'throttle:api'])->prefix('api/v1')->group(function () {

    // ══════════════════════════════════════════════════════════
    // ОЧЕРЕДЬ 2 — Активные маршруты
    // ══════════════════════════════════════════════════════════

    // Confectionery Routes [Q2 — кондитерские, фуд-смежные]
    Route::resource('bakery-orders', ConfectioneryOrderController::class)
        ->only(['index', 'store']);
    Route::post('bakery-orders/{id}/mark-ready', [ConfectioneryOrderController::class, 'markReady']);

    // MeatShops Routes [Q2 — мясные, фуд-смежные]
    Route::resource('meat-orders', MeatShopsOrderController::class)
        ->only(['index', 'store']);

    // Furniture Routes [Q2 — мебель и интерьер]
    Route::resource('furniture-orders', FurnitureOrderController::class)
        ->only(['index', 'store']);
    Route::post('furniture-orders/{id}/schedule-delivery', [FurnitureOrderController::class, 'scheduleDelivery']);

    // AutoParts Routes [Q2 — запчасти (смежно с Auto)]
    Route::resource('auto-parts-orders', AutoPartsOrderController::class)
        ->only(['index', 'store']);
    Route::get('auto-parts/compatible/{vin}', [AutoPartsOrderController::class, 'findCompatible']);

    // Pharmacy Routes [Q2 — аптеки, медицина-смежные]
    Route::resource('pharmacy-orders', PharmacyOrderController::class)
        ->only(['index', 'store']);
    Route::post('pharmacy-orders/verify-prescription', [PharmacyOrderController::class, 'verifyPrescription']);

    // ══════════════════════════════════════════════════════════
    // Domain-based vertical order routes
    // ══════════════════════════════════════════════════════════

    // Beauty
    Route::prefix('beauty')->group(function () {
        Route::post('orders', [BeautyOrderController::class, 'create']);
        Route::get('delivery-estimate', [BeautyOrderController::class, 'getDeliveryEstimate']);
    });

    // Food
    Route::prefix('food')->group(function () {
        Route::post('orders', [FoodOrderController::class, 'create']);
        Route::get('delivery-estimate', [FoodOrderController::class, 'getDeliveryEstimate']);
    });

    // Fashion
    Route::prefix('fashion')->group(function () {
        Route::post('orders', [FashionOrderController::class, 'create']);
        Route::get('delivery-estimate', [FashionOrderController::class, 'getDeliveryEstimate']);
    });

    // RealEstate
    Route::prefix('realestate')->group(function () {
        Route::post('orders', [RealEstateOrderController::class, 'create']);
        Route::get('delivery-estimate', [RealEstateOrderController::class, 'getDeliveryEstimate']);
    });

    // Travel
    Route::prefix('travel')->group(function () {
        Route::post('orders', [TravelOrderController::class, 'create']);
        Route::get('delivery-estimate', [TravelOrderController::class, 'getDeliveryEstimate']);
    });

    // Auto
    Route::prefix('auto')->group(function () {
        Route::post('orders', [AutoOrderController::class, 'create']);
        Route::get('delivery-estimate', [AutoOrderController::class, 'getDeliveryEstimate']);
    });

    // Medical
    Route::prefix('medical')->group(function () {
        Route::post('orders', [MedicalOrderController::class, 'create']);
        Route::get('delivery-estimate', [MedicalOrderController::class, 'getDeliveryEstimate']);
    });

    // Electronics
    Route::prefix('electronics')->group(function () {
        Route::post('orders', [ElectronicsOrderController::class, 'create']);
        Route::get('delivery-estimate', [ElectronicsOrderController::class, 'getDeliveryEstimate']);
    });

    // Fitness
    Route::prefix('fitness')->group(function () {
        Route::post('orders', [FitnessOrderController::class, 'create']);
        Route::get('delivery-estimate', [FitnessOrderController::class, 'getDeliveryEstimate']);
    });

    // Sports
    Route::prefix('sports')->group(function () {
        Route::post('orders', [SportsOrderController::class, 'create']);
        Route::get('delivery-estimate', [SportsOrderController::class, 'getDeliveryEstimate']);
    });

    // Luxury
    Route::prefix('luxury')->group(function () {
        Route::post('orders', [LuxuryOrderController::class, 'create']);
        Route::get('delivery-estimate', [LuxuryOrderController::class, 'getDeliveryEstimate']);
    });

    // Education
    Route::prefix('education')->group(function () {
        Route::post('orders', [EducationOrderController::class, 'create']);
        Route::get('delivery-estimate', [EducationOrderController::class, 'getDeliveryEstimate']);
    });

    // Insurance
    Route::prefix('insurance')->group(function () {
        Route::post('orders', [InsuranceOrderController::class, 'create']);
        Route::get('delivery-estimate', [InsuranceOrderController::class, 'getDeliveryEstimate']);
    });

    // Logistics
    Route::prefix('logistics')->group(function () {
        Route::post('orders', [LogisticsOrderController::class, 'create']);
        Route::get('delivery-estimate', [LogisticsOrderController::class, 'getDeliveryEstimate']);
    });

    // CRM
    Route::prefix('crm')->group(function () {
        Route::post('orders', [CRMOrderController::class, 'create']);
        Route::get('delivery-estimate', [CRMOrderController::class, 'getDeliveryEstimate']);
    });

    // Delivery
    Route::prefix('delivery')->group(function () {
        Route::post('orders', [DeliveryOrderController::class, 'create']);
        Route::get('delivery-estimate', [DeliveryOrderController::class, 'getDeliveryEstimate']);
    });

    // Hotels
    Route::prefix('hotels')->group(function () {
        Route::post('orders', [HotelsOrderController::class, 'create']);
        Route::get('delivery-estimate', [HotelsOrderController::class, 'getDeliveryEstimate']);
    });

    // Payment
    Route::prefix('payment')->group(function () {
        Route::post('orders', [PaymentOrderController::class, 'create']);
        Route::get('delivery-estimate', [PaymentOrderController::class, 'getDeliveryEstimate']);
    });

    // Legal
    Route::prefix('legal')->group(function () {
        Route::post('orders', [LegalOrderController::class, 'create']);
        Route::get('delivery-estimate', [LegalOrderController::class, 'getDeliveryEstimate']);
    });

    // Taxi
    Route::prefix('taxi')->group(function () {
        Route::post('orders', [TaxiOrderController::class, 'create']);
        Route::get('delivery-estimate', [TaxiOrderController::class, 'getDeliveryEstimate']);
    });

    // Flowers
    Route::prefix('flowers')->group(function () {
        Route::post('orders', [FlowersOrderController::class, 'create']);
        Route::get('delivery-estimate', [FlowersOrderController::class, 'getDeliveryEstimate']);
    });

    // Furniture (Domain)
    Route::prefix('furniture-domain')->group(function () {
        Route::post('orders', [FurnitureDomainOrderController::class, 'create']);
        Route::get('delivery-estimate', [FurnitureDomainOrderController::class, 'getDeliveryEstimate']);
    });

    // Pharmacy (Domain)
    Route::prefix('pharmacy-domain')->group(function () {
        Route::post('orders', [PharmacyDomainOrderController::class, 'create']);
        Route::get('delivery-estimate', [PharmacyDomainOrderController::class, 'getDeliveryEstimate']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::post('orders', [AnalyticsOrderController::class, 'create']);
        Route::get('delivery-estimate', [AnalyticsOrderController::class, 'getDeliveryEstimate']);
    });

    // Consulting
    Route::prefix('consulting')->group(function () {
        Route::post('orders', [ConsultingOrderController::class, 'create']);
        Route::get('delivery-estimate', [ConsultingOrderController::class, 'getDeliveryEstimate']);
    });

    // Freelance
    Route::prefix('freelance')->group(function () {
        Route::post('orders', [FreelanceOrderController::class, 'create']);
        Route::get('delivery-estimate', [FreelanceOrderController::class, 'getDeliveryEstimate']);
    });

    // EventPlanning
    Route::prefix('eventplanning')->group(function () {
        Route::post('orders', [EventPlanningOrderController::class, 'create']);
        Route::get('delivery-estimate', [EventPlanningOrderController::class, 'getDeliveryEstimate']);
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::post('orders', [WalletOrderController::class, 'create']);
        Route::get('delivery-estimate', [WalletOrderController::class, 'getDeliveryEstimate']);
    });

    // Pet
    Route::prefix('pet')->group(function () {
        Route::post('orders', [PetOrderController::class, 'create']);
        Route::get('delivery-estimate', [PetOrderController::class, 'getDeliveryEstimate']);
    });

    // Veterinary
    Route::prefix('veterinary')->group(function () {
        Route::post('orders', [VeterinaryOrderController::class, 'create']);
        Route::get('delivery-estimate', [VeterinaryOrderController::class, 'getDeliveryEstimate']);
    });

    // ToysAndGames
    Route::prefix('toysandgames')->group(function () {
        Route::post('orders', [ToysAndGamesOrderController::class, 'create']);
        Route::get('delivery-estimate', [ToysAndGamesOrderController::class, 'getDeliveryEstimate']);
    });

    // Advertising
    Route::prefix('advertising')->group(function () {
        Route::post('orders', [AdvertisingOrderController::class, 'create']);
        Route::get('delivery-estimate', [AdvertisingOrderController::class, 'getDeliveryEstimate']);
    });

    // CarRental
    Route::prefix('carrental')->group(function () {
        Route::post('orders', [CarRentalOrderController::class, 'create']);
        Route::get('delivery-estimate', [CarRentalOrderController::class, 'getDeliveryEstimate']);
    });

    // Finances
    Route::prefix('finances')->group(function () {
        Route::post('orders', [FinancesOrderController::class, 'create']);
        Route::get('delivery-estimate', [FinancesOrderController::class, 'getDeliveryEstimate']);
    });

    // Photography
    Route::prefix('photography')->group(function () {
        Route::post('orders', [PhotographyOrderController::class, 'create']);
        Route::get('delivery-estimate', [PhotographyOrderController::class, 'getDeliveryEstimate']);
    });

    // ShortTermRentals
    Route::prefix('shorttermrentals')->group(function () {
        Route::post('orders', [ShortTermRentalsOrderController::class, 'create']);
        Route::get('delivery-estimate', [ShortTermRentalsOrderController::class, 'getDeliveryEstimate']);
    });

    // SportsNutrition
    Route::prefix('sportsnutrition')->group(function () {
        Route::post('orders', [SportsNutritionOrderController::class, 'create']);
        Route::get('delivery-estimate', [SportsNutritionOrderController::class, 'getDeliveryEstimate']);
    });

    // PersonalDevelopment
    Route::prefix('personaldevelopment')->group(function () {
        Route::post('orders', [PersonalDevelopmentOrderController::class, 'create']);
        Route::get('delivery-estimate', [PersonalDevelopmentOrderController::class, 'getDeliveryEstimate']);
    });

    // HomeServices
    Route::prefix('homeservices')->group(function () {
        Route::post('orders', [HomeServicesOrderController::class, 'create']);
        Route::get('delivery-estimate', [HomeServicesOrderController::class, 'getDeliveryEstimate']);
    });

    // Gardening
    Route::prefix('gardening')->group(function () {
        Route::post('orders', [GardeningOrderController::class, 'create']);
        Route::get('delivery-estimate', [GardeningOrderController::class, 'getDeliveryEstimate']);
    });

    // GroceryAndDelivery
    Route::prefix('groceryanddelivery')->group(function () {
        Route::post('orders', [GroceryAndDeliveryOrderController::class, 'create']);
        Route::get('delivery-estimate', [GroceryAndDeliveryOrderController::class, 'getDeliveryEstimate']);
    });

    // FarmDirect
    Route::prefix('farmdirect')->group(function () {
        Route::post('orders', [FarmDirectOrderController::class, 'create']);
        Route::get('delivery-estimate', [FarmDirectOrderController::class, 'getDeliveryEstimate']);
    });

    // Content
    Route::prefix('content')->group(function () {
        Route::post('orders', [ContentOrderController::class, 'create']);
        Route::get('delivery-estimate', [ContentOrderController::class, 'getDeliveryEstimate']);
    });

    // Staff
    Route::prefix('staff')->group(function () {
        Route::post('orders', [StaffOrderController::class, 'create']);
        Route::get('delivery-estimate', [StaffOrderController::class, 'getDeliveryEstimate']);
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::post('orders', [InventoryOrderController::class, 'create']);
        Route::get('delivery-estimate', [InventoryOrderController::class, 'getDeliveryEstimate']);
    });

    // Tickets
    Route::prefix('tickets')->group(function () {
        Route::post('orders', [TicketsOrderController::class, 'create']);
        Route::get('delivery-estimate', [TicketsOrderController::class, 'getDeliveryEstimate']);
    });

    // Geo
    Route::prefix('geo')->group(function () {
        Route::post('orders', [GeoOrderController::class, 'create']);
        Route::get('delivery-estimate', [GeoOrderController::class, 'getDeliveryEstimate']);
    });

    // Marketplace
    Route::prefix('marketplace')->group(function () {
        Route::post('orders', [MarketplaceOrderController::class, 'create']);
        Route::get('delivery-estimate', [MarketplaceOrderController::class, 'getDeliveryEstimate']);
    });

    // Art
    Route::prefix('art')->group(function () {
        Route::post('orders', [ArtOrderController::class, 'create']);
        Route::get('delivery-estimate', [ArtOrderController::class, 'getDeliveryEstimate']);
    });

    // ══════════════════════════════════════════════════════════
    // ОЧЕРЕДЬ 3 — ЗАБЛОКИРОВАНО (раскомментировать после Q1+Q2)
    // ══════════════════════════════════════════════════════════

    // [Q3 — FarmDirect] Фермерские продукты напрямую
    // Route::resource('farm-orders', FarmDirectOrderController::class)
    //     ->only(['index', 'show', 'store', 'update', 'destroy']);

    // [Q3 — HealthyFood] Диетические планы
    // Route::resource('diet-plans', HealthyFoodDietController::class)
    //     ->only(['index', 'store']);
    // Route::post('diet-plans/{id}/subscribe', [HealthyFoodDietController::class, 'subscribe']);

    // [Q3 — OfficeCatering] Офисный кейтеринг
    // Route::resource('catering-orders', OfficeCateringOrderController::class)
    //     ->only(['index', 'store']);
    // Route::post('catering-orders/{id}/setup-recurring', [OfficeCateringOrderController::class, 'setupRecurring']);

    // [Q3 — Electronics] Электроника
    // Route::resource('electronics-orders', ElectronicsOrderController::class)
    //     ->only(['index', 'store']);
    // Route::post('electronics-orders/warranty-claim', [ElectronicsOrderController::class, 'claimWarranty']);

    // [Q3 — ToysKids] Детские товары и игрушки
    // Route::resource('toy-orders', ToysKidsOrderController::class)
    //     ->only(['index', 'store']);
});

