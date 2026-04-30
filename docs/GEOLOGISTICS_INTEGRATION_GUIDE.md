# GeoLogistics Integration Guide for Verticals

**Version:** 1.1  
**Date:** 2026-04-26  
**Status:** Production Ready (8/10 high priority verticals completed)

## Overview

This guide explains how to integrate `GeoLogisticsAdapter` into CatVRF verticals for delivery calculations, route estimation, and slot availability.

## Architecture

```
Vertical Service → GeoLogisticsAdapter → GeoLogistics Domain (RouteCalculatorService, SlotAvailabilityService, etc.)
```

**Key Principles:**
- **No direct GeoLogistics model access** from verticals - use adapter only
- **Clean Architecture** - verticals depend on abstraction (adapter)
- **Fraud check first** - before any logistics calculations
- **Correlation ID** - always pass for tracing
- **Vertical-specific configs** - defined in `config/verticals.php`

## GeoLogisticsAdapter API

### Available Methods

```php
namespace App\Domains\Shared\Geo;

final readonly class GeoLogisticsAdapter
{
    // Calculate delivery cost, ETA, distance
    public function calculateDeliveryForOrder(array $orderData): array
    {
        // Returns: ['cost' => int, 'eta' => int, 'distance' => int, 'route_data' => array]
    }

    // Get available delivery slots
    public function getAvailableSlots(string $address, string $vertical, ?string $subVertical = null): array
    {
        // Returns: array of slots with time and availability
    }

    // Check if address is in delivery zone
    public function isAddressInDeliveryZone(string $address, string $vertical): bool
    {
        // Returns: bool
    }
}
```

## Integration Pattern

### Step 1: Add Import

```php
use App\Domains\Shared\Geo\GeoLogisticsAdapter;
```

### Step 2: Inject Adapter in Constructor

```php
final readonly class YourVerticalService
{
    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        // ... other dependencies
    ) {}
}
```

### Step 3: Use in Business Logic

```php
public function createOrder(array $orderData): array
{
    $correlationId = (string) Str::uuid();

    // Fraud check first (mandatory)
    $this->fraudControl->check([
        'operation_type' => 'order_create',
        'vertical' => 'your_vertical',
        'correlation_id' => $correlationId,
    ]);

    // Calculate delivery via GeoLogistics
    $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
        'vertical' => 'your_vertical',
        'sub_vertical' => $orderData['sub_vertical'] ?? null,
        'seller_address' => $orderData['seller_address'],
        'buyer_address' => $orderData['buyer_address'],
        'items' => $orderData['items'] ?? [],
    ]);

    // Get available slots
    $availableSlots = $this->geoAdapter->getAvailableSlots(
        address: $orderData['buyer_address'],
        vertical: 'your_vertical',
        subVertical: $orderData['sub_vertical'] ?? null,
    );

    return [
        'delivery_cost' => $deliveryCalculation['cost'],
        'eta' => $deliveryCalculation['eta'],
        'distance' => $deliveryCalculation['distance'],
        'available_slots' => $availableSlots,
    ];
}
```

## Completed Integrations

### 1. Supermarket (Queue 2)
- **File:** `app/Domains/Supermarket/Services/SupermarketService.php`
- **Methods:** `prepareCheckout()`, `createOrder()`, `checkDeliveryAvailability()`
- **Config:** `enabled: true, cold_chain: true, delivery_priority: high`

### 2. CarRental (Queue 2)
- **File:** `app/Domains/CarRental/Services/CarRentalService.php`
- **Methods:** `createBooking()` - added pickup/return address delivery calculation
- **Config:** `enabled: true, realtime_tracking: true, delivery_priority: medium`

### 3. Flowers (Queue 2)
- **File:** `modules/Flowers/Application/Services/OrderService.php`
- **Methods:** `createOrder()` - automatic delivery cost calculation
- **Config:** `enabled: true, delivery_priority: medium, perishable: true`

### 4. Pharmacy (Queue 2)
- **File:** `app/Domains/Pharmacy/Services/PharmacyService.php`
- **Methods:** `createOrder()` - added delivery address parameter and cost calculation
- **Config:** `enabled: true, cold_chain: true, delivery_priority: urgent, precision: house`

### 5. Furniture (Queue 2)
- **File:** `app/Domains/Furniture/Services/FurnitureService.php`
- **Methods:** `createOrder()` - added delivery address and heavy cargo cost calculation
- **Config:** `enabled: true, heavy_cargo: true, delivery_priority: low`

### 6. HomeServices (Queue 2)
- **File:** `app/Domains/HomeServices/Services/HomeServicesService.php`
- **Methods:** `bookService()` - added address zone validation
- **Config:** `enabled: true, precision: house, delivery_priority: medium`

### 7. RealEstate (Queue 2)
- **File:** `app/Domains/RealEstate/Services/RealEstateService.php`
- **Methods:** `signRentalContract()` - added viewing distance/ETA calculation
- **Config:** `enabled: true, long_distance: true, property_viewing: true, delivery_priority: low`

### 8. Taxi (Queue 2)
- **File:** `modules/Taxi/Services/TaxiRideService.php`
- **Methods:** `createRide()` - added GeoLogistics route validation for additional accuracy
- **Config:** `enabled: true, realtime_tracking: true, dynamic_pricing: true, delivery_priority: urgent`

### 9. Construction (Queue 2)
- **File:** `app/Domains/ConstructionAndRepair/Construction/Services/ConstructionService.php`
- **Methods:** `createProject()` - added address zone verification for material delivery
- **Config:** `enabled: true, heavy_cargo: true, delivery_priority: low`

### 10. Medical (Queue 2)
- **File:** `app/Domains/Medical/Services/MedicalService.php`
- **Methods:** `createAppointment()` - added travel time calculation for home visits
- **Config:** `enabled: true, precision: house, delivery_priority: medium`

### 11. ShortTermRentals (Queue 2)
- **File:** `app/Domains/ShortTermRentals/Services/ShortTermRentalsService.php`
- **Methods:** `createRental()` - added check-in distance/ETA calculation
- **Config:** `enabled: true, long_distance: true, delivery_priority: low`

## Pending Integrations (Queue 2)

### High Priority (Delivery Required)

| Vertical | Service File | Methods to Update |
|----------|-------------|-------------------|
| **Food** | `app/Domains/Food/Services/FoodService.php` | `createOrder()`, `prepareCheckout()` |
| **Pharmacy** | `app/Domains/Pharmacy/Services/PharmacyService.php` | `createOrder()` - urgent delivery |
| **Fashion** | `modules/Fashion/Services/FashionRecommendationEngineService.php` | Delivery calculation in checkout |
| **Furniture** | `app/Domains/Furniture/Services/FurnitureService.php` | `createOrder()` - heavy cargo |
| **Construction** | `app/Domains/Construction/Services/ConstructionService.php` | Material delivery |
| **HomeServices** | `app/Domains/HomeServices/Services/HomeServicesService.php` | Service location check |
| **Medical** | `app/Domains/Medical/Services/MedicalService.php` | Home visit routing |
| **RealEstate** | `app/Domains/RealEstate/Services/RealEstateService.php` | Property viewing routes |
| **ShortTermRentals** | `app/Domains/Booking/Services/ShortTermRentalsService.php` | Check-in routing |
| **Taxi** | `modules/Taxi/Services/TaxiRideService.php` | Real-time routing |

### Medium Priority (Optional Delivery)

| Vertical | Service File | Notes |
|----------|-------------|-------|
| **Beauty** | `modules/BeautyMasters/Services/AppointmentService.php` | Master location accuracy |
| **Fitness** | `modules/Fitness/Services/MembershipService.php` | No delivery needed (on-site) |
| **CleaningServices** | `app/Domains/Cleaning/Services/CleaningService.php` | Service location |
| **MeatShops** | `app/Domains/MeatShops/Services/MeatShopService.php` | Cold chain delivery |
| **GroceryAndDelivery** | `app/Domains/Grocery/Services/GroceryService.php` | Cold chain delivery |
| **Hotels** | `app/Domains/Booking/Services/HotelsService.php` | Long-distance routes |

### Low Priority (No/Limited Delivery)

| Vertical | Reason |
|----------|--------|
| **PromoCampaigns** | Digital only - `enabled: false` |
| **Freelance** | Remote work - `enabled: false` |

## Vertical-Specific Configurations

### Food / Confectionery / Grocery
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'city_center',
    'cold_chain' => true,
    'delivery_priority' => 'high',
    'slot_window_minutes' => 20, // 20-min windows
]
```

### Pharmacy
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'city_center',
    'cold_chain' => true,
    'delivery_priority' => 'urgent',
    'slot_window_minutes' => 15, // 15-min windows for urgent
]
```

### Auto / Taxi
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'city_wide',
    'realtime_tracking' => true,
    'dynamic_pricing' => true,
    'delivery_priority' => 'urgent', // for taxi
]
```

### Furniture / Construction / SportsEquipment
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'city_wide',
    'delivery_priority' => 'low',
    'heavy_cargo' => true,
]
```

### RealEstate / Hotels / Travel
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'region_wide',
    'delivery_priority' => 'low',
    'long_distance' => true,
]
```

### Beauty / HomeServices / Medical
```php
'geo' => [
    'enabled' => true,
    'default_zone' => 'city_center',
    'precision' => 'house', // exact house accuracy
    'delivery_priority' => 'medium',
    'slot_window_minutes' => 60,
]
```

## Testing Integration

### Unit Test Example

```php
use Tests\TestCase;
use App\Domains\Shared\Geo\GeoLogisticsAdapter;

class YourVerticalServiceTest extends TestCase
{
    public function test_createOrder_calculates_delivery_via_geo_adapter()
    {
        // Mock GeoLogisticsAdapter
        $geoAdapter = $this->mock(GeoLogisticsAdapter::class);
        $geoAdapter->shouldReceive('calculateDeliveryForOrder')
            ->andReturn([
                'cost' => 500,
                'eta' => 45,
                'distance' => 5000,
            ]);

        // Test service method
        $result = $this->service->createOrder([
            'seller_address' => 'Depot A',
            'buyer_address' => 'Street 1',
            'items' => [],
        ]);

        $this->assertEquals(500, $result['delivery_cost']);
        $this->assertEquals(45, $result['eta']);
    }
}
```

## Config Reference

All vertical configurations are in `config/verticals.php`:

```php
'vertical_key' => [
    'domain' => 'DomainName',
    'model' => 'ModelClass',
    'queue' => 2,
    'active' => true,
    'geo' => [
        'enabled' => true|false,
        'default_zone' => 'city_center'|'city_wide'|'region_wide'|'premium_zone',
        'delivery_priority' => 'urgent'|'high'|'medium'|'low'|'premium',
        'cold_chain' => true|false,
        'realtime_tracking' => true|false,
        'dynamic_pricing' => true|false,
        'precision' => 'house'|'area',
        'slot_window_minutes' => int,
        'heavy_cargo' => true|false,
        'requires_assembly' => true|false,
        'requires_installation' => true|false,
        'secure_delivery' => true|false,
        'fragile' => true|false,
        'perishable' => true|false,
        'long_distance' => true|false,
        'property_viewing' => true|false,
        'mass_addresses' => true|false,
    ],
],
```

## Troubleshooting

### Issue: Delivery cost not calculated
**Check:** 
- GeoLogisticsAdapter is injected
- `seller_address` and `buyer_address` are provided
- Vertical geo config has `enabled: true`

### Issue: Slots not available
**Check:**
- Address is in delivery zone
- Vertical has slot configuration
- Slot window minutes is configured

### Issue: Wrong delivery priority
**Check:**
- Vertical geo config in `config/verticals.php`
- Adapter uses vertical-specific logic in `calculateDeliveryCost()`

## Next Steps

1. **Integrate remaining Queue 2 verticals** (high priority)
2. **Add integration tests** for each vertical
3. **Update service providers** to bind GeoLogisticsAdapter
4. **Monitor delivery accuracy** and adjust configs
5. **Add real-time tracking** for Auto/Taxi verticals

## References

- GeoLogistics Domain: `app/Domains/GeoLogistics/`
- GeoLogisticsAdapter: `app/Domains/Shared/Geo/GeoLogisticsAdapter.php`
- Supermarket Example: `app/Domains/Supermarket/Services/SupermarketService.php`
- Config: `config/verticals.php`
- CatVRF Canon: See `MEMORY[user_global]`
