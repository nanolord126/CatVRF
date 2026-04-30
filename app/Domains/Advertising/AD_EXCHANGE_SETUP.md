# Ad Exchange System - Setup Guide

**Version:** 1.0  
**Date:** 28.04.2026  
**Project:** CatVRF — AI-powered Healthcare Marketplace

## Overview

The CatVRF Ad Exchange system provides a modern, production-ready programmatic advertising platform with support for:
- Short video ads (15-60 seconds)
- Real-time auctions (Forward, Dutch, Sealed-Bid/VCG)
- OpenRTB 2.6 compliant RTB integration
- Publisher management with inventory reservation
- Fraud prevention and audit logging

## Architecture

### Domain Layer
- **Entities:** AdShort, Auction, Bid, AdInventory, Publisher (readonly, immutable)
- **Events:** AdShortCreated, BidPlaced, BidWon, AuctionClosed, AuctionStarted, InventoryReserved, InventoryReleased, InventoryCreated, PublisherOnboarded, PublisherVerified, PublisherSuspended
- **Services:** AuctionService, AdInventoryService, PublisherIntegrationService, RTBService

### Infrastructure Layer
- **Repositories:** Eloquent implementations for all entities
- **Models:** EloquentAdShort, EloquentAuction, EloquentBid, EloquentAdInventory, EloquentPublisher
- **Migrations:** 5 database migrations for new tables

### Presentation Layer
- **API Controllers:** AdShortController, AuctionController, RTBController
- **Filament Resources:** AdShortResource, AuctionResource, PublisherResource, AdInventoryResource
- **Routes:** `/api/v1/advertising/*` and `/api/v1/rtb/*`

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `ad_shorts` - Short video advertisements
- `auctions` - Auction configurations
- `bids` - Bid records
- `ad_inventory` - Publisher inventory
- `publishers` - Publisher accounts

### 2. Register Service Providers

Add the following to `config/app.php` (if not auto-discovered):

```php
'providers' => [
    // ...
    App\Domains\Advertising\Providers\AdvertisingServiceProvider::class,
],
```

### 3. Configure Redis

Ensure Redis is configured in `config/database.php` for auction locking and caching:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'catvrf_'),
    ],
    // ...
],
```

### 4. Environment Variables

Add to `.env`:

```env
# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Fraud Control
FRAUD_CONTROL_ENABLED=true
```

## API Endpoints

### Short Ads

**Public:**
- `GET /api/v1/advertising/shorts` - List short ads
- `GET /api/v1/advertising/shorts/{uuid}` - Get short ad details

**Authenticated:**
- `POST /api/v1/advertising/shorts` - Create short ad
- `POST /api/v1/advertising/shorts/{uuid}/activate` - Activate ad
- `POST /api/v1/advertising/shorts/{uuid}/pause` - Pause ad

**Create Short Ad Example:**
```json
{
  "tenant_id": 1,
  "title": "Healthcare Service Promo",
  "video_url": "https://cdn.example.com/ads/short.mp4",
  "thumbnail_url": "https://cdn.example.com/ads/thumb.jpg",
  "duration_seconds": 30,
  "budget": 100000,
  "pricing_model": "cpm",
  "targeting_criteria": {
    "age": [25, 55],
    "location": ["moscow", "spb"]
  }
}
```

### Auctions

**Public:**
- `GET /api/v1/advertising/auctions` - List auctions
- `GET /api/v1/advertising/auctions/{uuid}` - Get auction details
- `GET /api/v1/advertising/auctions/{uuid}/bids` - Get auction bids

**Authenticated:**
- `POST /api/v1/advertising/auctions/{uuid}/bid` - Place bid
- `POST /api/v1/advertising/auctions/{uuid}/close` - Close auction

**Place Bid Example:**
```json
{
  "tenant_id": 1,
  "bidder_id": 123,
  "amount": 150000
}
```

### RTB (OpenRTB 2.6)

**Public:**
- `POST /api/v1/rtb/bid` - Bid request (OpenRTB 2.6)
- `POST /api/v1/rtb/win` - Win notification
- `GET /api/v1/rtb/impression` - Impression tracking (returns 1x1 pixel)
- `POST /api/v1/rtb/click` - Click tracking

**RTB Bid Request Example:**
```json
{
  "id": "req-123",
  "imp": [
    {
      "id": "imp-1",
      "video": {
        "placement": "feed",
        "min_duration": 15,
        "max_duration": 60
      }
    }
  ],
  "site": {
    "name": "healthcare-site",
    "domain": "example.com"
  },
  "device": {
    "ip": "192.168.1.1",
    "ua": "Mozilla/5.0"
  },
  "user": {
    "id": 456
  }
}
```

## Filament Admin Panel

Navigate to `/admin` to access the Filament admin panel. Under the "Advertising" group, you'll find:

- **AdShorts** - Manage short video ads
- **Auctions** - Manage auctions and view bids
- **Publishers** - Manage publishers and payouts
- **AdInventory** - Manage publisher inventory

## Services Usage

### AuctionService

```php
use App\Domains\Advertising\Domain\Services\AuctionService;

$auctionService = app(AuctionService::class);

// Place a bid
$bid = $auctionService->placeBid(
    auctionId: 1,
    tenantId: 1,
    bidderId: 123,
    amount: 150000,
    userId: 0,
    correlationId: 'uuid-here'
);

// Close auction
$auctionService->closeAuction(auctionId: 1);

// Start auction
$auctionService->startAuction(auctionId: 1);
```

### AdInventoryService

```php
use App\Domains\Advertising\Domain\Services\AdInventoryService;

$inventoryService = app(AdInventoryService::class);

// Reserve inventory
$success = $inventoryService->reserveInventory(
    inventoryId: 1,
    impressions: 10000,
    userId: 0
);

// Release inventory
$success = $inventoryService->releaseInventory(
    inventoryId: 1,
    impressions: 5000
);

// Get available inventory for RTB
$inventory = $inventoryService->getAvailableInventory(
    inventoryType: 'short',
    placement: 'feed',
    targeting: ['age' => [18, 35]]
);

// Forecast inventory
$forecast = $inventoryService->forecastInventory(publisherId: 1, days: 30);
```

### PublisherIntegrationService

```php
use App\Domains\Advertising\Services\PublisherIntegrationService;

$publisherService = app(PublisherIntegrationService::class);

// Onboard publisher
$publisher = $publisherService->onboardPublisher(
    tenantId: 1,
    name: 'Healthcare Media',
    websiteUrl: 'https://healthmedia.com',
    commissionRate: 0.15,
    payoutThreshold: 1000000,
    integrationType: 'direct',
    webhookUrl: 'https://healthmedia.com/webhook',
    userId: 0
);

// Verify publisher
$publisherService->verifyPublisher(publisherId: 1);

// Suspend publisher
$publisherService->suspendPublisher(publisherId: 1, reason: 'Violation of terms');

// Regenerate API key
$newApiKey = $publisherService->regenerateApiKey(publisherId: 1);

// Process payouts
$payouts = $publisherService->processPayouts();
```

## Testing

Run the feature tests:

```bash
# Run all ad exchange tests
php artisan test --testsuite=Feature --filter=Advertising

# Run specific test
php artisan test --testsuite=Feature --filter=AdShortFeatureTest
php artisan test --testsuite=Feature --filter=AuctionFeatureTest
php artisan test --testsuite=Feature --filter=PublisherFeatureTest
php artisan test --testsuite=Feature --filter=RTBFeatureTest
```

## Performance Considerations

### Redis Locks
Auction bidding uses Redis locks to prevent race conditions:
- TTL: 5 seconds
- Lock key: `auction:{id}:lock`

### Caching
Inventory data is cached with:
- TTL: 1 hour
- Cache tags: `['advertising', 'inventory', 'publisher:{id}']`

### Rate Limiting
API endpoints are rate-limited:
- Public endpoints: 60 requests/minute
- Authenticated endpoints: 60 requests/minute
- RTB endpoints: 1000 requests/minute
- Bidding: 100 requests/minute

### Database Indexes
All tables have proper indexes for:
- Status queries
- Tenant filtering
- UUID lookups
- Date ranges

## Security

### Fraud Control
All critical operations include fraud checks via `FraudControlService`:
- Bid placement
- Inventory reservation
- Publisher onboarding
- Short ad creation

### Audit Logging
All services support audit logging for:
- Entity creation
- Entity updates
- Status changes
- Financial operations

### API Authentication
- Public endpoints: No auth required
- Authenticated endpoints: Sanctum tokens required
- RTB endpoints: Publisher API key authentication

## Event Listeners

You can create event listeners for domain events:

```php
// In EventServiceProvider
protected $listen = [
    \App\Domains\Advertising\Domain\Events\BidPlaced::class => [
        \App\Listeners\SendBidNotification::class,
    ],
    \App\Domains\Advertising\Domain\Events\AuctionClosed::class => [
        \App\Listeners\ProcessAuctionWinner::class,
    ],
];
```

## Troubleshooting

### Auction Not Starting
Check if:
- Auction status is 'upcoming'
- Start time has passed
- Redis is running

### Bid Rejected
Check if:
- Auction is active
- Bid amount is higher than current price (for forward auctions)
- Bid amount equals current price (for dutch auctions)
- User has not exceeded rate limits

### Inventory Not Available
Check if:
- Inventory status is 'available'
- Available impressions > reserved impressions
- Date range is valid

### RTB No Bid Response
Check if:
- Request format is valid OpenRTB 2.6
- Inventory is available for matching criteria
- Publisher is active

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Check audit logs: `storage/logs/audit.log`
3. Review architecture: `AD_EXCHANGE_ARCHITECTURE.md`

## Production Checklist

- [ ] Run migrations on production database
- [ ] Configure Redis cluster for high availability
- [ ] Set up monitoring for auction latency (target: <100ms)
- [ ] Configure rate limits appropriately
- [ ] Set up fraud detection thresholds
- [ ] Configure webhook URLs for publishers
- [ ] Enable audit logging
- [ ] Set up CDN for ad creative delivery
- [ ] Configure SSL certificates for RTB endpoints
- [ ] Set up backup strategy for Redis and database
- [ ] Load test with k6 scripts in `/k6/` directory
- [ ] Configure Prometheus/Grafana monitoring
