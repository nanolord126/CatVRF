# Multi-Type Courier Strategy - Production Implementation

**Version:** 1.0  
**Date:** April 19, 2026  
**Status:** Production Ready

## Overview

This document describes the production-ready multi-type courier strategy implementation for CatVRF, designed to optimize delivery costs while maintaining 95%+ SLA. The system supports five courier types with type-aware assignment, routing, and pricing.

## Courier Types

### 1. Pedestrian (Пеший курьер)
- **Radius:** 1.5-3 km
- **Max Weight:** 8-10 kg
- **Delivery Time:** 30-45 min
- **Routing:** Walking mode (pedestrian zones, sidewalks)
- **Cost Multiplier:** 1.0x
- **Best For:** Dense center, light orders, short distances

### 2. Scooter (Самокат)
- **Radius:** 4-8 km
- **Max Weight:** 15-20 kg
- **Delivery Time:** 20-35 min
- **Routing:** Biking mode (bike lanes, sidewalks where permitted)
- **Battery Threshold:** 30%
- **Cost Multiplier:** 1.2x
- **Best For:** Medium distances, urban areas

### 3. E-Bike (Электровелосипед)
- **Radius:** 4-10 km
- **Max Weight:** 15-20 kg
- **Delivery Time:** 20-30 min
- **Routing:** Biking mode
- **Battery Threshold:** 30%
- **Cost Multiplier:** 1.3x
- **Best For:** Longer urban routes, hilly terrain

### 4. Car (Автомобиль)
- **Radius:** 8-20+ km
- **Max Weight:** 50-100+ kg
- **Delivery Time:** 40-90 min
- **Routing:** Driving mode + parking time (8 min)
- **Cost Multiplier:** 2.0x
- **Best For:** Heavy orders, long distances, suburban areas

### 5. Taxi (Такси-курьер)
- **Radius:** 8-25+ km
- **Max Weight:** 50-100+ kg
- **Delivery Time:** 40-90 min
- **Routing:** Driving mode + parking time (5 min)
- **Cost Multiplier:** 2.2x
- **Best For:** On-demand capacity, peak times, emergency orders

## Architecture

### Core Components

#### 1. CourierType Enum
**Location:** `app/Domains/Logistics/Enums/CourierType.php`

Defines all courier types with their default constraints:
- Max radius, weight, speed
- Delivery time limits
- Battery thresholds
- Routing modes
- Cost multipliers
- Compatibility scoring

```php
use App\Domains\Logistics\Enums\CourierType;

$type = CourierType::PEDESTRIAN;
$maxRadius = $type->getMaxRadiusKm(); // 2.5
$compatibility = $type->getCompatibilityScore(5.0, 2.0); // 0-1 score
```

#### 2. CourierTypeConfiguration Model & Service
**Location:** 
- Model: `app/Domains/Logistics/Models/CourierTypeConfiguration.php`
- Service: `app/Domains/Logistics/Services/CourierTypeConfigurationService.php`

Manages tenant-specific and city-specific rules:
- Override default enum values per tenant/city
- Weather penalties
- Operating hours
- Geographic zones (allowed/restricted)
- Cached for performance (1h TTL)

```php
$config = $configService->getConfiguration($tenantId, 'pedestrian', 'Moscow');
$suitableTypes = $configService->getSuitableTypes($tenantId, 15.0, 5.0, 'Moscow');
```

#### 3. Courier Model Enhancements
**Location:** `app/Domains/Logistics/Models/Courier.php`

Added fields:
- `max_radius_km` - Courier-specific radius override
- `preferred_zones` - GeoJSON polygons for preferred areas
- Helper methods for type-aware operations

```php
$courier->getType(); // Returns CourierType enum
$courier->getMaxRadiusKm(); // Uses override or type default
$courier->hasSufficientBattery(30); // Check battery for electric vehicles
$courier->isInPreferredZone($lat, $lng); // Zone check
```

#### 4. GeoLogisticsService Upgrade
**Location:** `modules/GeoLogistics/Services/GeoLogisticsService.php`

Enhanced routing modes:
- `walking` - Pedestrian routing (OSRM foot profile)
- `biking` - Bike routing (OSRM bike profile)
- `driving` - Car routing (OSRM driving profile)

```php
$route = $geoService->calculateRoute($from, $to, 'walking');
$cost = $geoService->calculateDeliveryCost($from, $to, 5.0, 'biking', 1.2);
```

#### 5. CourierAssignmentCriteriaService
**Location:** `app/Domains/Logistics/Services/CourierAssignmentCriteriaService.php`

Multi-criteria filtering:
- Hard constraints (weight, radius, battery, availability)
- Soft constraints (scoring with penalties)
- Weather-aware filtering
- Operating hours validation
- Preferred zones check

```php
$filtered = $criteriaService->filterByOrderConstraints(
    $couriers,
    $tenantId,
    10.0, // weight
    $pickup,
    $delivery,
    'Moscow',
    ['rain'] // weather conditions
);
```

#### 6. CourierFitScoringService
**Location:** `app/Domains/Logistics/Services/CourierFitScoringService.php`

ML-ready feature extraction:
- 30+ features for XGBoost/LightGBM
- Type compatibility scoring
- Historical performance metrics
- Rule-based fallback

```php
$features = $scoringService->extractFeatures($courier, $tenantId, $weight, $distance, ...);
$score = $scoringService->calculateRuleBasedScore($courier, $tenantId, $weight, $distance, ...);
```

#### 7. UnifiedFleetService
**Location:** `app/Domains/Logistics/Services/UnifiedFleetService.php`

Type-aware assignment logic:
- Try types in priority order
- Filter by constraints
- Score candidates
- Shadow mode for A/B testing
- Multi-type cost calculation

```php
$courier = $fleetService->assignCourierMultiType(
    $tenantId,
    10.0, // weight
    $pickup,
    $delivery,
    'Moscow',
    ['rain'],
    useML: false,
    shadowMode: false
);

$cost = $fleetService->calculateDeliveryCostMultiType($tenantId, $weight, $pickup, $delivery, 'Moscow');
```

#### 8. TimeWindowValidator
**Location:** `app/Domains/Logistics/Services/TimeWindowValidator.php`

SLA compliance validation:
- Time window validation
- ETA calculation
- SLA risk detection
- Recommended time slots

```php
$canMeet = $validator->canMeetTimeWindow($tenantId, 'pedestrian', 2.0, '18:00', '20:00');
$bestType = $validator->getBestTypeForTimeWindow($tenantId, 5.0, '18:00', '20:00');
$isAtRisk = $validator->isSlaAtRisk($tenantId, 'scooter', 5.0, '19:30');
$slots = $validator->getRecommendedTimeSlots($tenantId, 'car', 10.0);
```

#### 9. MultiModalRouteOptimizationService
**Location:** `app/Domains/Logistics/Services/MultiModalRouteOptimizationService.php`

VRP optimization for single-type clusters:
- Cost matrix calculation
- Order clustering
- Route building with constraints
- Type-specific cost calculation

```php
$routes = $optimizationService->optimizeRoutesForType(
    'pedestrian',
    $orders,
    $depot,
    10.0, // max capacity
    45, // max time
    10 // max stops
);
```

#### 10. CourierFitPredictionService
**Location:** `app/Domains/Logistics/Services/CourierFitPredictionService.php`

ETA and success prediction:
- Rule-based ETA calculation
- ML prediction placeholder
- Success probability estimation
- Batch prediction support

```php
$eta = $predictionService->predictETA($courier, $pickup, $delivery, $weight, $tenantId);
$fitScore = $predictionService->predictFitScore($courier, $tenantId, $weight, $distance, ...);
$successProb = $predictionService->predictSuccessProbability($courier, $distance, $weight);
```

## Database Schema

### courier_type_rules Table
```sql
CREATE TABLE courier_type_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    city VARCHAR(100) NULL,
    max_radius_km DECIMAL(5,2) DEFAULT 10.0,
    max_weight_kg DECIMAL(5,2) DEFAULT 20.0,
    avg_speed_kmh DECIMAL(5,2) DEFAULT 15.0,
    max_delivery_time_min INT DEFAULT 60,
    parking_time_min INT DEFAULT 0,
    battery_threshold INT NULL,
    requires_battery BOOLEAN DEFAULT FALSE,
    allowed_zones JSON NULL,
    restricted_zones JSON NULL,
    cost_multiplier DECIMAL(5,3) DEFAULT 1.000,
    weather_penalties JSON NULL,
    operating_hours JSON NULL,
    priority INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY (tenant_id, type, city),
    INDEX (tenant_id, type, is_active)
);
```

### couriers Table (Enhanced)
```sql
ALTER TABLE couriers ADD COLUMN max_radius_km DECIMAL(5,2) NULL;
ALTER TABLE couriers ADD COLUMN preferred_zones JSON NULL;
```

## Filament Dashboard

**Resource:** `CourierTypeConfigurationResource`

Features:
- View/edit courier type rules per tenant/city
- Configure capacity, time, battery constraints
- Set weather penalties and operating hours
- Manage cost multipliers
- Filter by type and status

Navigation: Logistics → Courier Type Rules

## API Usage

### REST API Endpoints

All endpoints are prefixed with `/api/v1/logistics/courier-type`

#### POST `/courier-type/assign`
Assign courier using multi-type strategy.

**Request:**
```json
{
  "tenant_id": 1,
  "weight_kg": 8.0,
  "pickup_lat": 55.7558,
  "pickup_lng": 37.6173,
  "delivery_lat": 55.7600,
  "delivery_lng": 37.6200,
  "city": "Moscow",
  "weather_conditions": ["rain"],
  "use_ml": false,
  "shadow_mode": false
}
```

**Response:**
```json
{
  "courier_id": 123,
  "courier_uuid": "uuid-here",
  "vehicle_type": "pedestrian",
  "correlation_id": "uuid-here"
}
```

#### POST `/courier-type/calculate-cost`
Calculate delivery cost using multi-type strategy.

**Request:**
```json
{
  "tenant_id": 1,
  "weight_kg": 8.0,
  "pickup_lat": 55.7558,
  "pickup_lng": 37.6173,
  "delivery_lat": 55.7600,
  "delivery_lng": 37.6200,
  "city": "Moscow",
  "weather_conditions": ["rain"]
}
```

**Response:**
```json
{
  "cost_kopek": 18000,
  "cost_rubles": 180.0
}
```

#### POST `/courier-type/validate-time-window`
Validate if courier type can meet delivery time window.

**Request:**
```json
{
  "tenant_id": 1,
  "type": "pedestrian",
  "distance_km": 2.0,
  "time_window_start": "18:00",
  "time_window_end": "20:00",
  "city": "Moscow",
  "buffer_min": 15
}
```

**Response:**
```json
{
  "can_meet": true
}
```

#### POST `/courier-type/best-type-for-window`
Get best courier type for time window.

**Request:**
```json
{
  "tenant_id": 1,
  "distance_km": 5.0,
  "time_window_start": "18:00",
  "time_window_end": "20:00",
  "city": "Moscow"
}
```

**Response:**
```json
{
  "type": "scooter",
  "eta_min": 25,
  "can_meet": true,
  "priority": 20,
  "cost_multiplier": 1.2
}
```

#### POST `/courier-type/predict-eta`
Predict ETA for specific courier.

**Request:**
```json
{
  "courier_id": 123,
  "pickup_lat": 55.7558,
  "pickup_lng": 37.6173,
  "delivery_lat": 55.7600,
  "delivery_lng": 37.6200,
  "weight_kg": 8.0,
  "city": "Moscow"
}
```

**Response:**
```json
{
  "eta_min": 35,
  "courier_id": 123,
  "vehicle_type": "pedestrian"
}
```

#### POST `/courier-type/recommended-slots`
Get recommended delivery time slots.

**Request:**
```json
{
  "tenant_id": 1,
  "type": "pedestrian",
  "distance_km": 2.0,
  "city": "Moscow",
  "slot_duration_min": 60,
  "slots_ahead": 5
}
```

**Response:**
```json
{
  "slots": [
    {
      "start": "14:30",
      "end": "15:30",
      "eta_min": 25,
      "available_at": "2026-04-19T14:30:00Z"
    }
  ]
}
```

### Service Usage (PHP)

#### Assign Courier (Multi-Type)
```php
use App\Domains\Logistics\Services\UnifiedFleetService;

$courier = $fleetService->assignCourierMultiType(
    tenantId: 1,
    weightKg: 8.0,
    pickupLocation: ['lat' => 55.7558, 'lng' => 37.6173],
    deliveryLocation: ['lat' => 55.7600, 'lng' => 37.6200],
    city: 'Moscow',
    weatherConditions: ['rain'],
    useML: false,
    shadowMode: false,
    correlationId: 'order-123'
);
```

#### Calculate Delivery Cost
```php
$costKopek = $fleetService->calculateDeliveryCostMultiType(
    tenantId: 1,
    weightKg: 8.0,
    pickupLocation: ['lat' => 55.7558, 'lng' => 37.6173],
    deliveryLocation: ['lat' => 55.7600, 'lng' => 37.6200],
    city: 'Moscow'
);
```

### Validate Time Window
```php
use App\Domains\Logistics\Services\TimeWindowValidator;

$canMeet = $validator->canMeetTimeWindow(
    tenantId: 1,
    type: 'pedestrian',
    distanceKm: 2.0,
    timeWindowStart: '18:00',
    timeWindowEnd: '20:00',
    city: 'Moscow',
    bufferMin: 15
);
```

## Configuration

### Default Rules Seeding
The migration `2026_04_19_000001_create_courier_type_rules_table.php` automatically seeds default rules for all tenants with production-ready values.

### City-Specific Overrides
Create city-specific rules to adjust for local conditions:
```php
$configService->upsertConfiguration(
    tenantId: 1,
    type: 'pedestrian',
    data: [
        'max_radius_km' => 2.0, // Smaller radius for dense center
        'max_delivery_time_min' => 35,
        'priority' => 5, // Higher priority
    ],
    city: 'Moscow'
);
```

## Testing

### Unit Tests
```bash
php artisan test --filter=CourierTypeTest
php artisan test --filter=CourierAssignmentCriteriaServiceTest
php artisan test --filter=CourierFitScoringServiceTest
```

### Feature Tests
```bash
php artisan test --filter=MultiTypeCourierAssignmentTest
php artisan test --filter=TimeWindowValidatorTest
```

## Performance Considerations

### Caching
- CourierTypeConfiguration: 1h TTL with cache tags
- GeoService responses: Consider Redis caching for frequent routes

### Database Indexes
- Composite index on `(tenant_id, status, vehicle_type, is_active)`
- Geo-spatial index on `(current_lat, current_lng)`

### Query Optimization
- Use geospatial queries for nearest courier search
- Batch feature extraction for ML scoring
- Limit candidate sets to top 20 for scoring

## Monitoring & Metrics

### Key Metrics to Track
- Assignment success rate by type
- Average delivery time by type
- Cost per delivery by type
- SLA compliance by type
- Battery level distribution
- Weather penalty impact

### Filament Dashboard Widgets
Add to CourierResource:
- % orders by courier type
- Average delivery time by type
- SLA violation rate by type
- Cost per delivery trend

## Migration Path

### Phase 1: Foundation (Completed)
- CourierType enum
- CourierTypeConfiguration model & service
- Courier model enhancements
- GeoLogisticsService upgrade
- Database migrations

### Phase 2: Assignment Logic (Completed)
- CourierAssignmentCriteriaService
- CourierFitScoringService
- UnifiedFleetService enhancements
- TimeWindowValidator

### Phase 3: Optimization (Completed)
- MultiModalRouteOptimizationService
- CourierFitPredictionService
- Filament dashboard

### Phase 4: ML Integration (Future)
- Train XGBoost/LightGBM model on historical data
- Deploy model inference service
- A/B test vs rule-based scoring
- Gradual rollout with feature flags

## Expected Benefits

Based on Ozon production experience (2025-2026):

- **Cost Reduction:** 20-35% for small orders (pedestrian/scooter vs car)
- **SLA Improvement:** 95%+ with type-aware assignment
- **Courier Satisfaction:** Better utilization, no over-assignment
- **Fleet Efficiency:** Higher utilization, lower idle time
- **Scalability:** Handle peak demand with flexible type allocation

## Troubleshooting

### No Suitable Types Found
Check:
- Order weight vs type max weight
- Distance vs type max radius
- Weather conditions and penalties
- Operating hours for type

### Assignment Fails
Check:
- Courier availability (status, battery)
- Preferred zones configuration
- Configuration cache (clear if stale)

### ETA Inaccurate
Check:
- Avg speed configuration for type
- Parking time settings
- Weather conditions not applied
- Traffic data integration needed

## References

- Original strategy specification (user request)
- Ozon production experience (2025-2026)
- OSRM routing profiles
- CatVRF coding standards and best practices
