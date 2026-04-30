# Unified Logistics Core Architecture

**Version:** 1.0  
**Date:** April 2026  
**Status:** Production Ready  

## Overview

The Unified Logistics Core combines couriers, taxi drivers, and pickup points (ПВЗ) into a single intelligent fulfillment system. This architecture enables Ozon-level smart logistics with real-time matching, ML-based scoring, and load balancing.

## Architecture Components

### 1. Core Models

#### Courier Model
- **Location:** `app/Domains/Logistics/Models/Courier.php`
- **Purpose:** Unified fleet entity combining regular couriers and taxi drivers
- **Key Fields:**
  - `vehicle_type`: pedestrian | bike | scooter | car | taxi
  - `status`: online | idle | on_delivery | offline | suspended | banned
  - `is_taxi_driver`: Boolean for hybrid taxi/courier mode
  - `capacity_kg`: Maximum load capacity
  - `current_lat/lng`: Real-time location
  - `rating`: Performance score (0-5)
  - `battery_level`: For electric vehicles
  - `is_verified`, `is_active`: Status flags

#### PickupPoint Model
- **Location:** `app/Domains/Logistics/Models/PickupPoint.php`
- **Purpose:** Pickup points (ПВЗ) for order collection
- **Key Fields:**
  - `lat`, `lng`: Geographic coordinates
  - `capacity_slots`: Maximum order capacity
  - `current_load`: Current order count
  - `working_hours`: Operating hours (e.g., "09:00-21:00")
  - `is_24h`: 24/7 operation flag
  - `status`: active | inactive | maintenance | closed

#### OrderShipment Model
- **Location:** `app/Domains/Logistics/Models/OrderShipment.php`
- **Purpose:** Polymorphic shipment record supporting both courier and PVZ fulfillment
- **Key Fields:**
  - `fulfillment_type`: courier | pickup_point | taxi
  - `fulfillment_id`: Polymorphic reference
  - `status`: pending | assigned | picked | in_transit | delivered | issued_at_pvz | cancelled | failed
  - `qr_code`: Tracking identifier
  - `pickup_code`: 4-digit code for PVZ issuance

### 2. Core Services

#### UnifiedFleetService
- **Location:** `app/Domains/Logistics/Services/UnifiedFleetService.php`
- **Purpose:** Real-time matching of orders to optimal couriers/taxi drivers
- **Algorithm:**
  1. **Fraud Check** (CatVRF rule #1) - First action always
  2. **Geospatial Query** - Find couriers within 5km radius
  3. **ML Scoring** - Score candidates using XGBoost/LightGBM or heuristic fallback
  4. **Assignment** - Assign to best candidate
  5. **Broadcast** - Notify courier via WebSocket

**Scoring Formula:**
```
Score = 0.4×distance_score + 0.3×rating_score + 0.2×availability_score + 0.1×taxi_bonus - battery_penalty
```

**Key Features:**
- Hybrid taxi/courier mode (taxi drivers can take courier orders)
- Battery level filtering for electric vehicles
- Vehicle type preferences
- Real-time location tracking
- ETA calculation by vehicle type

#### PvzAssignmentService
- **Location:** `app/Domains/Logistics/Services/PvzAssignmentService.php`
- **Purpose:** Smart pickup point assignment (Ozon-level algorithm)
- **Algorithm:**
  1. **Filter** - PVZ within 3km + active + available slots (<85% load)
  2. **ML Score** = 0.4×proximity + 0.3×load_balance + 0.2×user_history + 0.1×predicted_demand
  3. **Fallback** - Nearest courier if all PVZ overloaded
  4. **Hold** - 20-minute reservation via Redis

**Scoring Factors:**
- **Proximity:** Distance-based score (closer = better)
- **Load Balance:** Prefer PVZs with 40-70% load
- **User History:** Bonus for frequently used PVZs
- **Predicted Demand:** Peak hour awareness
- **24/7 Bonus:** Extra points for late hours

**Key Features:**
- Load balancing across PVZs
- User preference tracking
- Peak hour demand prediction
- 24/7 PVZ preference for late orders
- Redis-based hold mechanism

### 3. Background Jobs

#### DispatchPvzIssuanceJob
- **Location:** `app/Domains/Logistics/Jobs/DispatchPvzIssuanceJob.php`
- **Purpose:** Async notification and QR code generation for PVZ orders
- **Features:**
  - Unique job per shipment (ShouldBeUnique)
  - Dedicated queue: `logistics`
  - Retry mechanism (3 attempts)
  - Email/push/SMS notifications
  - Audit logging

### 4. API Endpoints

#### Unified Fleet API
```
POST   /api/v1/logistics/fleet/assign              Assign shipment to courier/taxi
GET    /api/v1/logistics/fleet/couriers/available  Get available couriers
GET    /api/v1/logistics/fleet/couriers/nearby     Get nearby couriers with distance
```

#### PVZ Assignment API
```
POST   /api/v1/logistics/pvz/assign                Assign order to PVZ
POST   /api/v1/logistics/pvz/release-hold          Release PVZ reservation
GET    /api/v1/logistics/pvz/nearby                Get nearby PVZs with availability
```

### 5. Filament Resources

#### CourierResource
- **Location:** `app/Domains/Logistics/Filament/Resources/CourierResource.php`
- **Features:**
  - Courier management interface
  - Real-time status tracking
  - Vehicle type filtering
  - Battery level monitoring
  - Earnings tracking
  - Rating management

#### PickupPointResource
- **Location:** `app/Domains/Logistics/Filament/Resources/PickupPointResource.php`
- **Features:**
  - PVZ management interface
  - Load percentage visualization
  - 24/7 status tracking
  - Overload warnings (>85%)
  - Working hours management

## Database Schema

### Couriers Table
```sql
CREATE TABLE couriers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    tenant_id BIGINT UNSIGNED,
    business_group_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED,
    vehicle_type VARCHAR(20),
    status VARCHAR(20),
    current_lat DECIMAL(10, 8),
    current_lng DECIMAL(11, 8),
    capacity_kg DECIMAL(8, 2),
    is_taxi_driver BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3, 2) DEFAULT 5.00,
    delivery_count INT DEFAULT 0,
    earnings_kopeki BIGINT DEFAULT 0,
    battery_level INT NULL,
    last_location_update TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_location (current_lat, current_lng),
    SPATIAL INDEX idx_spatial (POINT(current_lng, current_lat))
);
```

### Pickup Points Table
```sql
CREATE TABLE pickup_points (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    tenant_id BIGINT UNSIGNED,
    name VARCHAR(255),
    address TEXT,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    capacity_slots INT,
    current_load INT DEFAULT 0,
    working_hours VARCHAR(20),
    is_24h BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    phone VARCHAR(20),
    metadata JSON,
    correlation_id CHAR(36),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_location (lat, lng),
    SPATIAL INDEX idx_spatial (POINT(lng, lat))
);
```

### Order Shipments Table
```sql
CREATE TABLE order_shipments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    tenant_id BIGINT UNSIGNED,
    order_id BIGINT UNSIGNED,
    courier_id BIGINT UNSIGNED NULL,
    pickup_point_id BIGINT UNSIGNED NULL,
    fulfillment_type VARCHAR(20),
    fulfillment_id BIGINT UNSIGNED NULL,
    status VARCHAR(20),
    eta_minutes INT NULL,
    route_polyline TEXT,
    distance_km DECIMAL(10, 2),
    assigned_at TIMESTAMP NULL,
    picked_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    issued_at_pvz TIMESTAMP NULL,
    qr_code VARCHAR(50),
    pickup_code VARCHAR(4),
    metadata JSON,
    correlation_id CHAR(36),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_order (order_id),
    INDEX idx_courier (courier_id),
    INDEX idx_pickup_point (pickup_point_id),
    INDEX idx_fulfillment (fulfillment_type, fulfillment_id),
    INDEX idx_status (status)
);
```

## Production Features

### Fraud Detection
- **FraudControlService** integration on all public operations
- Velocity checks for assignment frequency
- IP/device fingerprinting
- Audit logging

### Performance
- **PostGIS** spatial queries for geospatial filtering
- **Redis** caching for:
  - PVZ holds (20-minute reservations)
  - User preferences (24h TTL)
  - Distance calculations (5min TTL)
- **Queue** jobs for async operations
- **WebSocket** broadcasting for real-time updates

### Scalability
- Tenant-scoped queries (multi-tenancy)
- Circuit breaker for external ML calls
- Rate limiting per tenant
- Database indexing on critical fields
- Spatial indexes for location queries

### Monitoring
- **Prometheus** metrics for:
  - Assignment success rate
  - Average ETA accuracy
  - PVZ load distribution
  - Courier utilization
- **Audit logging** for all operations
- **Correlation IDs** for distributed tracing

## Usage Examples

### Assign Shipment to Courier/Taxi
```php
use App\Domains\Logistics\Services\UnifiedFleetService;

$fleetService = app(UnifiedFleetService::class);

$result = $fleetService->assignShipment([
    'order_id' => $order->id,
    'tenant_id' => tenant()->id,
    'delivery_lat' => 55.7550,
    'delivery_lng' => 37.6180,
    'weight_kg' => 5.0,
    'amount' => 1000,
    'time_sensitive' => false,
], $correlationId);

// Access result
$courier = $result->courier;
$shipment = $result->shipment;
$eta = $result->shipment->eta_minutes;
```

### Assign Order to PVZ
```php
use App\Domains\Logistics\Services\PvzAssignmentService;

$pvzService = app(PvzAssignmentService::class);

$pvz = $pvzService->assignToPvz([
    'order_id' => $order->id,
    'user_id' => $user->id,
    'user_lat' => 55.7550,
    'user_lng' => 37.6180,
    'tenant_id' => tenant()->id,
], $correlationId);

// PVZ hold is automatically created (20 minutes)
// DispatchPvzIssuanceJob is queued for notifications
```

### Get Available Couriers
```php
$couriers = Courier::query()
    ->available()
    ->nearby($lat, $lng, 5.0) // 5km radius
    ->byVehicleType(Courier::VEHICLE_BIKE)
    ->orderByDesc('rating')
    ->limit(20)
    ->get();
```

### Get Nearby PVZs
```php
$pvzs = PickupPoint::query()
    ->where('status', PickupPoint::STATUS_ACTIVE)
    ->where('current_load', '<', DB::raw('capacity_slots * 0.85'))
    ->selectRaw("*, ST_Distance_Sphere(...) as distance_meters")
    ->having('distance_meters', '<=', 3000)
    ->orderBy('distance_meters')
    ->get();
```

## Testing

### Unit Tests
- `tests/Unit/Domains/Logistics/UnifiedFleetServiceTest.php` - Fleet service logic
- `tests/Unit/Domains/Logistics/PvzAssignmentServiceTest.php` - PVZ assignment logic

### Feature Tests
- `tests/Feature/Domains/Logistics/UnifiedFleetApiTest.php` - Fleet API endpoints
- `tests/Feature/Domains\Logistics/PvzAssignmentApiTest.php` - PVZ API endpoints

### Test Coverage
- Assignment algorithms
- Fraud detection integration
- Geospatial queries
- PVZ load balancing
- User preference tracking
- Hold mechanism
- Error handling

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Seed initial PVZ locations
- [ ] Configure Redis for holds
- [ ] Set up Horizon for queue processing
- [ ] Configure WebSocket broadcasting
- [ ] Set up PostGIS spatial indexes
- [ ] Configure fraud detection thresholds
- [ ] Set up monitoring dashboards
- [ ] Train ML models for scoring (optional)
- [ ] Configure rate limiting

## Performance Targets

- **Assignment Latency:** <200ms (p95)
- **API Response Time:** <100ms (p95)
- **Courier Discovery:** <50ms for 5km radius
- **PVZ Assignment:** <150ms
- **Queue Processing:** <5s for notifications
- **Concurrent Assignments:** 1000+ per minute

## Security Considerations

- All public operations require fraud checks
- PII anonymization for location data (152-ФZ compliance)
- Tenant isolation for multi-tenancy
- Rate limiting per API endpoint
- Audit logging for all operations
- Correlation IDs for traceability

## Future Enhancements

1. **ML Model Training**
   - XGBoost/LightGBM models for courier scoring
   - PVZ demand prediction models
   - ETA prediction accuracy improvement

2. **Advanced Features**
   - Dynamic pricing based on demand
   - Surge pricing for peak hours
   - Multi-stop route optimization
   - Real-time traffic integration

3. **Analytics**
   - Courier performance dashboards
   - PVZ heatmaps in Filament
   - Delivery time analytics
   - Cost optimization reports

## Support & Maintenance

- **Logs:** `storage/logs/laravel.log`, `storage/logs/audit.log`
- **Monitoring:** Grafana dashboards (see `docs/grafana/`)
- **Alerts:** Configure via Prometheus AlertManager
- **Documentation:** Update this file on architecture changes

## References

- CatVRF Production Standards: See project root
- Fraud Detection: `app/Services/Fraud/FraudMLService.php`
- Geo Service: `app/Services/Geo/GeoService.php`
- Realtime Service: `app/Domains/Realtime/Services/RealtimeService.php`
- PostGIS Documentation: https://postgis.net/documentation/
