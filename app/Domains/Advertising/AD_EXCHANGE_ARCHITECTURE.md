# Ad Exchange Architecture - CatVRF 2026

**Version:** 1.0  
**Date:** 2026-04-28  
**Status:** Production Ready  

## Overview

Modern ad exchange system supporting:
- **Short Video Ads** (TikTok/Reels format, 15-60s)
- **Real-Time Bidding (RTB)** with OpenRTB 2.6 compliance
- **Auction System** (forward, dutch, sealed-bid)
- **Ad Inventory Management** for publishers
- **Publisher Integration** with external platforms

## Architecture Principles

1. **Clean Architecture + DDD**
   - Domain entities are readonly and immutable
   - Repository pattern for data access
   - Application services for orchestration
   - Infrastructure for external integrations

2. **Performance First**
   - RTB SLA: 100ms response time
   - Redis for auction state and inventory holds
   - Circuit breakers for external services
   - Async processing for non-critical operations

3. **Security & Fraud Prevention**
   - Fraud check as first action in all public methods
   - Device fingerprinting
   - Rate limiting per publisher/tenant
   - Audit logging for all transactions

4. **Scalability**
   - Horizontal scaling for RTB endpoints
   - Queue-based auction processing
   - Database sharding readiness
   - CDN for ad creative delivery

## Domain Model

### Core Entities

```
AdShort (short video ads)
├── id, uuid, tenant_id
├── title, video_url, thumbnail_url
├── duration_seconds (15-60)
├── status: draft|pending_review|active|paused|completed|rejected|cancelled
├── budget, spent
├── pricing_model: cpm|cpc|cpa|cpv
├── targeting_criteria (array)
└── correlation_id

Auction
├── id, uuid, tenant_id
├── name
├── type: forward|dutch|sealed_bid
├── status: upcoming|active|closed|cancelled
├── start_at, end_at
├── starting_price, current_price, reserve_price
├── inventory_id
├── bid_history (array)
└── correlation_id

Bid
├── id, uuid, auction_id
├── tenant_id, bidder_id
├── amount
├── status: pending|winning|losing|withdrawn
├── placed_at
└── correlation_id

AdInventory
├── id, uuid, publisher_id
├── inventory_type: short|banner|video|native
├── placement: feed|story|search|interstitial
├── available_impressions, reserved_impressions
├── available_from, available_until
├── status: available|reserved|sold_out
├── targeting_restrictions (array)
└── correlation_id

Publisher
├── id, uuid, tenant_id
├── name, website_url
├── status: active|suspended|pending
├── commission_rate (decimal)
├── payout_threshold (int)
├── api_key, webhook_url
├── integration_type: direct|ssp|dsp
└── correlation_id
```

## Service Layer

### RTB Service (Real-Time Bidding)
- **Location:** `App\Domains\Advertising\Services\RTBService`
- **Responsibilities:**
  - Process OpenRTB 2.6 bid requests
  - Fraud validation (100ms SLA)
  - Inventory matching
  - Bid price calculation
  - Win notification handling
  - Impression/click tracking

### Auction Service
- **Location:** `App\Domains\Advertising\Domain\Services\AuctionService`
- **Responsibilities:**
  - Auction lifecycle management
  - Bid validation and processing
  - Winner selection (VCG, first-price, second-price)
  - Auction close logic
  - Reserve price enforcement

### AdInventory Service
- **Location:** `App\Domains\Advertising\Domain\Services\AdInventoryService`
- **Responsibilities:**
  - Inventory availability management
  - Impression reservation/release
  - Inventory forecasting
  - Publisher inventory aggregation

### Publisher Integration Service
- **Location:** `App\Domains\Advertising\Services\PublisherIntegrationService`
- **Responsibilities:**
  - Publisher onboarding
  - API key management
  - Webhook handling
  - Revenue reporting
  - Payout processing

## Repository Interfaces

```php
AdShortRepositoryInterface
├── save(AdShort): AdShort
├── findById(int): ?AdShort
├── findByUuid(string): ?AdShort
├── findByTenant(int): Collection
├── findActive(): Collection
└── updateSpent(int, int): bool

AuctionRepositoryInterface
├── save(Auction): Auction
├── findById(int): ?Auction
├── findByUuid(string): ?Auction
├── findActive(): Collection
├── findByInventory(int): Collection
└── updateBidHistory(int, array): bool

BidRepositoryInterface
├── save(Bid): Bid
├── findById(int): ?Bid
├── findByAuction(int): Collection
├── findByBidder(int): Collection
├── findHighest(int): ?Bid
└── updateStatus(int, string): bool

AdInventoryRepositoryInterface
├── save(AdInventory): AdInventory
├── findById(int): ?AdInventory
├── findByPublisher(int): Collection
├── findAvailable(string, string, array): Collection
├── reserve(int, int): bool
└── release(int, int): bool

PublisherRepositoryInterface
├── save(Publisher): Publisher
├── findById(int): ?Publisher
├── findByUuid(string): ?Publisher
├── findByTenant(int): Collection
├── findActive(): Collection
└── updateStatus(int, string): bool
```

## API Endpoints

### RTB Endpoints (OpenRTB 2.6)
```
POST /api/v1/rtb/bid          - Bid request
POST /api/v1/rtb/win          - Win notification
POST /api/v1/rtb/impression   - Impression tracking
POST /api/v1/rtb/click        - Click tracking
```

### Short Ads Endpoints
```
GET    /api/v1/shorts          - List short ads
POST   /api/v1/shorts          - Create short ad
GET    /api/v1/shorts/{id}     - Get short ad
PUT    /api/v1/shorts/{id}     - Update short ad
DELETE /api/v1/shorts/{id}     - Delete short ad
POST   /api/v1/shorts/{id}/activate   - Activate ad
POST   /api/v1/shorts/{id}/pause      - Pause ad
```

### Auction Endpoints
```
GET    /api/v1/auctions        - List auctions
POST   /api/v1/auctions        - Create auction
GET    /api/v1/auctions/{id}   - Get auction
POST   /api/v1/auctions/{id}/bid     - Place bid
POST   /api/v1/auctions/{id}/close   - Close auction
GET    /api/v1/auctions/{id}/bids    - List bids
```

### Inventory Endpoints
```
GET    /api/v1/inventory       - List inventory
POST   /api/v1/inventory       - Create inventory
GET    /api/v1/inventory/{id}  - Get inventory
PUT    /api/v1/inventory/{id}  - Update inventory
DELETE /api/v1/inventory/{id}  - Delete inventory
GET    /api/v1/inventory/available - Query available inventory
```

### Publisher Endpoints
```
GET    /api/v1/publishers      - List publishers
POST   /api/v1/publishers      - Create publisher
GET    /api/v1/publishers/{id} - Get publisher
PUT    /api/v1/publishers/{id} - Update publisher
POST   /api/v1/publishers/{id}/verify - Verify publisher
GET    /api/v1/publishers/{id}/revenue - Revenue report
```

## Event System

### Domain Events
```
AdShortCreated
AdShortActivated
AdShortPaused
AuctionCreated
AuctionStarted
AuctionClosed
BidPlaced
BidWon
BidLost
InventoryReserved
InventoryReleased
PublisherOnboarded
PublisherVerified
```

### Async Processing
- All events dispatched via Laravel queues
- Event listeners for:
  - Analytics tracking
  - Notification dispatch
  - Revenue calculation
  - Audit logging

## Database Schema

### Tables
```
ad_shorts
auctions
bids
ad_inventory
publishers
ad_impressions (already exists)
```

### Indexes
- UUID indexes for all entities
- Composite indexes on (tenant_id, status)
- Composite indexes on (inventory_id, status)
- Timestamp indexes for time-based queries

## Caching Strategy

### Redis Keys
```
auction:{auction_id}:state     - Auction state (TTL: auction duration)
auction:{auction_id}:bids      - Current bids (TTL: auction duration)
inventory:{inventory_id}:lock  - Inventory lock (TTL: 5s)
publisher:{publisher_id}:quota - Publisher rate limit (TTL: 60s)
rtb:{request_id}:result        - RTB bid result (TTL: 10s)
```

### Cache Tags
```
advertising:shorts:{tenant_id}
advertising:auctions:{tenant_id}
advertising:inventory:{publisher_id}
advertising:publishers
```

## Security

1. **Authentication**
   - API keys for publishers
   - JWT for admin users
   - Tenant-scoped access control

2. **Fraud Prevention**
   - Device fingerprinting
   - IP reputation checks
   - Rate limiting (Redis)
   - Bid pattern analysis

3. **Data Protection**
   - PII anonymization
   - Audit logging (separate channel)
   - Encrypted sensitive fields

## Monitoring

### Metrics
- RTB response time (p50, p95, p99)
- Auction success rate
- Inventory utilization
- Publisher revenue
- Bid win rate

### Alerts
- RTB SLA breach (>100ms)
- Low inventory availability
- High fraud rate
- Publisher payout threshold reached

## Performance Targets

- **RTB Response Time:** <100ms (p95)
- **Auction Processing:** <50ms
- **Inventory Query:** <20ms
- **Concurrent Bids:** 10,000+ per second
- **Throughput:** 50,000+ RPS

## Deployment

### Horizontal Scaling
- Stateless RTB endpoints
- Redis cluster for state
- Database read replicas
- CDN for creative delivery

### Blue-Green Deployment
- Zero-downtime deployments
- Feature flags for new auction types
- Circuit breakers for external SSPs

## Testing

### Unit Tests
- Domain entity business logic
- Repository implementations
- Service layer logic

### Feature Tests
- RTB endpoint compliance
- Auction workflow
- Inventory reservation
- Publisher integration

### Load Tests
- RTB endpoint (10k+ RPS)
- Auction bidding (1k+ concurrent)
- Inventory query throughput

## Compliance

### OpenRTB 2.6
- Full bid request/response support
- GDPR consent handling
- COPPA compliance

### Local Regulations
- 152-FZ (data localization)
- Ad disclosure requirements
- Consumer protection laws
