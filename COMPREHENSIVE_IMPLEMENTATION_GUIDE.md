# 📖 BLOGGERS MODULE — COMPREHENSIVE IMPLEMENTATION GUIDE

**Last Updated:** March 23, 2026  
**Status:** Production Ready ✅  
**Version:** 1.0.0

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Architecture Overview](#architecture-overview)
3. [Core Concepts](#core-concepts)
4. [API Reference](#api-reference)
5. [Admin Panel Guide](#admin-panel-guide)
6. [Development Workflow](#development-workflow)
7. [Testing Strategy](#testing-strategy)
8. [Extending the Module](#extending-the-module)
9. [Performance Optimization](#performance-optimization)
10. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Installation

```bash
# Clone repository
git clone https://github.com/your-org/catvrf.git
cd catvrf

# Copy environment
cp .env.example .env

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed --class=BloggerSeeder
```

### First Stream

```bash
# 1. Create blogger account (as user)
POST /api/auth/register
{
  "name": "Alice",
  "email": "alice@example.com",
  "password": "secure_password",
  "category": "beauty"
}

# 2. Create first stream
POST /api/streams
{
  "title": "Makeup Tutorial",
  "description": "Learn professional makeup",
  "scheduled_at": "2026-03-24T20:00:00Z",
  "category": "beauty"
}

# 3. Start stream when ready
POST /api/streams/{roomId}/start

# 4. View from audience (as different user)
GET /api/streams/{roomId}
POST /api/streams/{roomId}/chat
{
  "message": "Amazing tutorial!",
  "message_type": "text"
}

# 5. End stream
POST /api/streams/{roomId}/end
```

---

## Architecture Overview

### Layered Architecture

```
┌─────────────────────────────────────────┐
│       Frontend Layer                     │
│  (Livewire, Vue.js, Blade Templates)    │
├─────────────────────────────────────────┤
│       API Layer (Controllers)            │
│  (REST endpoints, validation, responses) │
├─────────────────────────────────────────┤
│       Service Layer                      │
│  (Business logic, transactions, events)  │
├─────────────────────────────────────────┤
│       Repository Layer (Models)          │
│  (Eloquent ORM, database queries)        │
├─────────────────────────────────────────┤
│       Database Layer                     │
│  (PostgreSQL, migrations, schemas)       │
└─────────────────────────────────────────┘
```

### Module Structure

```
app/Domains/Bloggers/
├── Models/
│   ├── BloggerProfile.php          (User profile + verification)
│   ├── Stream.php                  (Live stream/VOD)
│   ├── StreamProduct.php           (Product in stream)
│   ├── StreamOrder.php             (Order from stream)
│   ├── StreamChatMessage.php       (Chat message)
│   ├── NftGift.php                 (NFT gift, minting)
│   ├── NftGiftCollection.php       (NFT collection)
│   └── StreamStatistics.php        (Analytics)
│
├── Services/
│   ├── StreamService.php           (Create, start, end streams)
│   ├── NftMintingService.php       (NFT minting, upgrades)
│   ├── LiveCommerceService.php     (Products, orders, pinning)
│   └── GeoService.php              (Geo-targeting, heatmaps)
│
├── Http/Controllers/
│   ├── StreamController.php        (Stream CRUD, statistics)
│   ├── ProductController.php       (Product management)
│   ├── OrderController.php         (Order management)
│   ├── ChatController.php          (Chat operations)
│   ├── GiftController.php          (Gift operations)
│   ├── StatisticsController.php    (Analytics)
│   ├── VerificationController.php  (Document verification)
│   ├── ProfileController.php       (Profile management)
│   └── DashboardController.php     (Dashboard analytics)
│
├── Http/Requests/
│   ├── CreateStreamRequest.php
│   ├── AddProductRequest.php
│   ├── CreateOrderRequest.php
│   ├── SendChatMessageRequest.php
│   └── ... (6 total)
│
├── Middleware/
│   ├── RateLimitBloggers.php       (Rate limiting)
│   ├── EnsureStreamAccess.php      (IDOR protection)
│   ├── ValidateReverbAuth.php      (WebSocket auth)
│   └── SanitizeChatInput.php       (XSS prevention)
│
├── Jobs/
│   └── MintNftGiftJob.php          (Async NFT minting)
│
├── Events/
│   ├── StreamCreated.php
│   ├── StreamStarted.php
│   ├── ProductAddedToStream.php
│   ├── ProductPinned.php
│   ├── OrderCreated.php
│   ├── PaymentConfirmed.php
│   └── GiftReceived.php
│
└── config/
    └── bloggers.php                (Configuration)

database/migrations/
├── 2026_03_23_create_blogger_profiles_table.php
├── 2026_03_23_create_streams_table.php
├── 2026_03_23_create_stream_products_table.php
├── 2026_03_23_create_stream_orders_table.php
├── 2026_03_23_create_stream_chat_messages_table.php
├── 2026_03_23_create_nft_gifts_table.php
├── 2026_03_23_create_nft_gift_collections_table.php
├── 2026_03_23_create_stream_statistics_table.php
└── 2026_03_23_create_blogger_verification_documents_table.php

resources/views/bloggers/
├── stream-player.blade.php         (Stream player)
├── stream-list.blade.php           (Stream listing)
└── blog-card.blade.php             (Stream card)

tests/Feature/Domains/Bloggers/
├── Services/
│   ├── StreamServiceTest.php
│   ├── NftMintingServiceTest.php
│   └── LiveCommerceServiceTest.php
├── Security/
│   └── SecurityVulnerabilitiesTest.php
├── Load/
│   └── LoadTestingTest.php
├── Integration/
│   └── EndToEndWorkflowTest.php
└── Http/
    ├── StreamControllerApiTest.php
    ├── ProductControllerApiTest.php
    ├── OrderControllerApiTest.php
    ├── ChatControllerApiTest.php
    ├── GiftControllerApiTest.php
    └── StatisticsControllerApiTest.php
```

---

## Core Concepts

### 1. Stream Lifecycle

```
scheduled → live → ended → vod (optional)
   ↓
 start()
   ↓
 live (viewers can join)
   ↓
 end()
   ↓
 ended (archive available)
```

**State Machine:**

```php
// app/Domains/Bloggers/Models/Stream.php
public function start(): bool
{
    if ($this->status !== 'scheduled') {
        throw new InvalidStateException('Stream must be scheduled');
    }
    
    $this->update([
        'status' => 'live',
        'started_at' => now(),
        'broadcast_key' => Str::random(64), // For broadcaster
    ]);
    
    event(new StreamStarted($this));
    return true;
}

public function end(): bool
{
    if ($this->status !== 'live') {
        throw new InvalidStateException('Stream must be live');
    }
    
    $this->update([
        'status' => 'ended',
        'ended_at' => now(),
        'duration_minutes' => $this->started_at->diffInMinutes($this->ended_at),
    ]);
    
    event(new StreamEnded($this));
    return true;
}
```

### 2. NFT Gift System

```
User sends gift (amount: 50000 ₽)
    ↓
Gift created with status: pending
    ↓
MintNftGiftJob dispatched
    ↓
TON smart contract mints NFT
    ↓
Status: minted (14-day hold)
    ↓
After 14 days: eligible for upgrade
    ↓
User upgrades to Collector NFT
    ↓
Status: upgraded (increased rarity)
```

**Gift Types:**

```php
'bronze' => 500,      // ₽5 (50,000 копеек)
'silver' => 2500,     // ₽25
'gold' => 5000,       // ₽50
'diamond' => 10000,   // ₽100
'platinum' => 25000,  // ₽250
```

### 3. Live Commerce

```
Blogger pinned product
    ↓
Product appears in sticky area (max 5)
    ↓
Viewer creates order
    ↓
Order status: pending
    ↓
Payment system processes
    ↓
Confirm payment endpoint
    ↓
Order status: paid
    ↓
Platform commission calculated (14%)
    ↓
Blogger earnings credited to wallet
```

---

## API Reference

### Streams

#### Create Stream
```http
POST /api/streams
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Makeup Tutorial",
  "description": "Learn professional makeup techniques",
  "scheduled_at": "2026-03-24T20:00:00Z",
  "category": "beauty",
  "tags": ["makeup", "tutorial"]
}

Response: 201 Created
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "room_id": "room_uuid",
    "broadcast_key": "secret_key_for_broadcaster",
    "title": "Makeup Tutorial",
    "status": "scheduled",
    "scheduled_at": "2026-03-24T20:00:00Z",
    "created_at": "2026-03-23T10:00:00Z"
  }
}
```

#### Start Stream
```http
POST /api/streams/{roomId}/start
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "status": "live",
    "started_at": "2026-03-24T20:00:00Z"
  }
}
```

#### Get Active Streams
```http
GET /api/streams?filter=live&per_page=20
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "id": "uuid",
      "title": "Makeup Tutorial",
      "status": "live",
      "current_viewers": 1240,
      "peak_viewers": 2500,
      "blogger": { "display_name": "Alice" },
      "started_at": "2026-03-24T20:00:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 20,
    "current_page": 1
  }
}
```

### Products

#### Add Product to Stream
```http
POST /api/streams/{roomId}/products
Authorization: Bearer {token}

{
  "product_id": "product_uuid",
  "quantity": 100,
  "price_override": 4990  // Optional: override base price
}

Response: 201 Created
{
  "data": {
    "id": "stream_product_id",
    "product": { "name": "Mascara", "price": 4990 },
    "quantity": 100,
    "quantity_sold": 0
  }
}
```

#### Pin Product (max 5)
```http
POST /api/streams/{roomId}/products/{productId}/pin
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "pin_position": 1,
    "is_pinned": true
  }
}
```

### Orders

#### Create Order
```http
POST /api/orders
Authorization: Bearer {token}

{
  "product_id": "stream_product_id",
  "quantity": 2,
  "payment_method": "sbp"  // sbp, card, wallet, crypto
}

Response: 201 Created
{
  "data": {
    "id": "order_id",
    "total": 9980,  // 2 × 4990
    "payment_status": "pending",
    "payment_id": "pay_unique_id"
  }
}
```

#### Confirm Payment
```http
POST /api/orders/{orderId}/confirm-payment
Authorization: Bearer {token}

{
  "payment_id": "pay_unique_id"
}

Response: 200 OK
{
  "data": {
    "payment_status": "confirmed",
    "paid_at": "2026-03-24T20:05:00Z",
    "platform_commission": 1397,  // 14% of 9980
    "blogger_earnings": 8583      // 86% of 9980
  }
}
```

### Chat

#### Send Message
```http
POST /api/streams/{roomId}/chat
Authorization: Bearer {token}

{
  "message": "This is amazing!",
  "message_type": "text"  // text, emoji, sticker
}

Response: 201 Created
{
  "data": {
    "id": "message_id",
    "sender": { "name": "John" },
    "message": "This is amazing!",
    "created_at": "2026-03-24T20:05:00Z"
  }
}
```

#### Get Messages
```http
GET /api/streams/{roomId}/chat?limit=50
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "id": "msg_id",
      "sender": { "name": "Alice" },
      "message": "Great stream!",
      "is_pinned": false,
      "created_at": "2026-03-24T20:05:00Z"
    }
  ]
}
```

### Gifts

#### Send NFT Gift
```http
POST /api/gifts/streams/{roomId}/send
Authorization: Bearer {token}

{
  "amount": 50000,      // In kopiykas
  "gift_type": "gold",  // bronze, silver, gold, diamond, platinum
  "message": "Love your content!"
}

Response: 201 Created
{
  "data": {
    "id": "gift_id",
    "minting_status": "pending",
    "gift_type": "gold",
    "amount": 50000,
    "created_at": "2026-03-24T20:05:00Z"
  }
}
```

#### Upgrade Gift (after 14 days)
```http
POST /api/gifts/{giftId}/upgrade
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "is_upgraded": true,
    "upgraded_at": "2026-04-07T20:05:00Z",
    "rarity": "collector"
  }
}
```

### Statistics

#### Get Blogger Stats
```http
GET /api/statistics/blogger/me
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "total_streams": 42,
    "total_viewers": 125000,
    "average_viewers_per_stream": 2976,
    "total_earned": 485000,      // In kopiykas
    "platform_commission": 67900,
    "net_earnings": 417100,
    "rating": 4.8,
    "followers": 15000
  }
}
```

#### Get Leaderboard
```http
GET /api/statistics/leaderboard?metric=earnings&period=month
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "rank": 1,
      "blogger": { "display_name": "Alice", "avatar": "url" },
      "value": 950000,
      "metric": "earnings"
    }
  ],
  "meta": {
    "metric": "earnings",
    "period": "month",
    "generated_at": "2026-03-23T23:00:00Z"
  }
}
```

---

## Admin Panel Guide

### Accessing Admin Panel

```
URL: /admin/tenant/bloggers
Login: admin@example.com / password
```

### Blogger Management

**Verification Workflow:**

1. **Pending** → Review documents
2. **Verified** → Approve, set featured status
3. **Rejected** → Provide feedback, allow resubmission

**Actions:**

- ✅ Verify: Approve blogger
- ❌ Reject: Block with reason
- ⏸ Suspend: Temporary block
- 🚫 Ban: Permanent block

**Filtering:**

```
Status: Pending | Verified | Rejected | Suspended
Moderation: Active | Warned | Suspended | Banned
Featured: Yes | No
Rating: >4.0 | >3.0 | <3.0
```

### Stream Moderation

**Stream Statistics (Live):**

- Current viewers (updates in real-time)
- Peak viewers
- Chat message count
- Revenue generated
- Commission earned

**Moderation Actions:**

- 🚩 Flag: Mark for review
- ⏸ Suspend: Stop stream, allow resume
- 🚫 Ban: Permanent removal

**Auto-Flag Criteria:**

- Explicit content detected
- High report rate
- Spam patterns
- Policy violations

### NFT Gift Management

**Status Monitoring:**

- Pending → Queued for minting
- Minting → In-progress
- Minted → Successfully created
- Failed → Retry available

**Actions:**

- 🔄 Retry: Requeue failed mint
- 📄 View Metadata: IPFS details
- 🔗 View on TON: Block explorer link
- 🚩 Flag: Mark as suspicious

---

## Development Workflow

### Adding New Feature

```bash
# 1. Create migration
php artisan make:migration add_feature_to_streams --table=streams

# 2. Create model if needed
php artisan make:model Feature --migration

# 3. Create service method
# Edit app/Domains/Bloggers/Services/StreamService.php
public function addFeature(Stream $stream, array $data): void
{
    DB::transaction(function () use ($stream, $data) {
        $stream->update($data);
        
        event(new FeatureAdded($stream));
        
        Log::channel('audit')->info('Feature added', [
            'stream_id' => $stream->id,
            'correlation_id' => request()->header('X-Correlation-ID') ?? Str::uuid(),
        ]);
    });
}

# 4. Create controller endpoint
# Edit app/Domains/Bloggers/Http/Controllers/StreamController.php
public function addFeature(Request $request, Stream $stream)
{
    $this->service->addFeature($stream, $request->validated());
    return response()->json(['success' => true]);
}

# 5. Create tests
php artisan make:test Feature/Domains/Bloggers/Http/FeatureControllerApiTest

# 6. Run tests
php artisan test tests/Feature/Domains/Bloggers/

# 7. Create migration
php artisan migrate

# 8. Deploy
git add .
git commit -m "feat: add feature"
git push origin feature-branch
```

### Code Standards

**PHP Style:**

```php
// ✅ GOOD
final class StreamService
{
    public function __construct(private readonly LogRepository $logs) {}
    
    public function createStream(array $data): Stream
    {
        return DB::transaction(function () use ($data) {
            $stream = Stream::create($data);
            event(new StreamCreated($stream));
            return $stream;
        });
    }
}

// ❌ BAD
class StreamService {
    public function createStream($data) {
        $stream = new Stream();
        $stream->fill($data);
        $stream->save();
        return $stream;
    }
}
```

**Error Handling:**

```php
// ✅ GOOD
public function confirmPayment(string $orderId, string $paymentId): void
{
    $order = StreamOrder::findOrFail($orderId);
    
    if ($order->payment_status !== 'pending') {
        throw new PaymentAlreadyConfirmedException(
            'Payment already processed for order ' . $orderId
        );
    }
    
    // Process payment
}

// ❌ BAD
public function confirmPayment($orderId, $paymentId) {
    $order = StreamOrder::find($orderId);
    if (!$order || $order->payment_status != 'pending') {
        return false;
    }
    // Process payment
}
```

---

## Testing Strategy

### Test Pyramid

```
         △
        / \
       /   \  E2E Tests (5%)
      /─────\
     /       \
    /         \  Integration Tests (15%)
   /───────────\
  /             \
 /               \  Unit Tests (80%)
/─────────────────\
```

### Running Tests

```bash
# All tests
php artisan test

# Specific suite
php artisan test tests/Feature/Domains/Bloggers/Services/

# With coverage
php artisan test --coverage

# Specific test
php artisan test tests/Feature/Domains/Bloggers/Services/StreamServiceTest::test_create_stream

# Watch mode (auto-run on file changes)
php artisan test --watch
```

### Test File Structure

```php
// tests/Feature/Domains/Bloggers/Services/StreamServiceTest.php
final class StreamServiceTest extends TestCase
{
    use RefreshDatabase; // Reset DB after each test
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StreamService(...);
    }
    
    public function test_create_stream_creates_stream_with_correct_attributes(): void
    {
        // Arrange (setup)
        $blogger = BloggerProfile::factory()->create();
        
        // Act (perform action)
        $stream = $this->service->createStream([
            'blogger_id' => $blogger->id,
            'title' => 'Test',
            'status' => 'scheduled',
        ]);
        
        // Assert (verify result)
        $this->assertNotNull($stream->id);
        $this->assertEquals('Test', $stream->title);
        $this->assertDatabaseHas('streams', [
            'id' => $stream->id,
            'status' => 'scheduled',
        ]);
    }
}
```

---

## Extending the Module

### Adding New Vertical (e.g., "Auto" for cars)

```php
// app/Domains/Auto/Models/Car.php
final class Car extends Model
{
    protected $table = 'cars';
    protected $fillable = ['name', 'price', 'uuid', 'tenant_id'];
    
    public function streams()
    {
        return $this->hasMany(Stream::class, 'product_id');
    }
}

// app/Domains/Auto/Services/CarService.php
final class CarService
{
    public function createListing(array $data): Car
    {
        return DB::transaction(function () use ($data) {
            $car = Car::create($data);
            event(new CarListingCreated($car));
            return $car;
        });
    }
}

// database/migrations/create_cars_table.php
Schema::create('cars', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique()->index();
    $table->foreignId('tenant_id')->constrained();
    $table->string('name');
    $table->integer('price'); // In kopiykas
    $table->string('status')->default('draft');
    $table->timestamps();
    $table->softDeletes();
});
```

### Custom Event Listener

```php
// app/Domains/Bloggers/Listeners/SendGiftNotification.php
final class SendGiftNotification
{
    public function handle(GiftReceived $event): void
    {
        $event->gift->blogger->notify(
            new GiftReceivedNotification($event->gift)
        );
        
        Log::channel('audit')->info('Gift notification sent', [
            'gift_id' => $event->gift->id,
            'blogger_id' => $event->gift->blogger_id,
        ]);
    }
}

// Register in EventServiceProvider
protected $listen = [
    GiftReceived::class => [
        SendGiftNotification::class,
    ],
];
```

---

## Performance Optimization

### Query Optimization

```php
// ❌ N+1 Problem
$streams = Stream::all();
foreach ($streams as $stream) {
    echo $stream->blogger->name; // N queries!
}

// ✅ Eager Loading
$streams = Stream::with('blogger')->get(); // 2 queries
foreach ($streams as $stream) {
    echo $stream->blogger->name;
}

// ✅ For large datasets
$streams = Stream::with(['blogger', 'products', 'orders'])
    ->chunk(100, function ($streams) {
        // Process 100 at a time
    });
```

### Caching Strategy

```php
// Cache active streams for 5 minutes
$streams = Cache::remember('streams:active', 300, function () {
    return Stream::where('status', 'live')
        ->with('blogger')
        ->get();
});

// Invalidate cache on changes
event(new StreamStarted($stream)); // Listener clears cache

// Cache hot data
Cache::put("blogger:{$blogger->id}:stats", [
    'total_viewers' => ...,
    'total_earned' => ...,
], 3600); // 1 hour
```

### Database Indexing

```php
// In migration
Schema::create('streams', function (Blueprint $table) {
    // ...
    $table->index(['status', 'blogger_id']); // Composite
    $table->fullText(['title', 'description']); // Full-text search
});

// Check indexes
php artisan schema:table streams --show
```

---

## Troubleshooting

### Common Issues

**Issue: Stream not starting**

```
Check:
1. broadcaster_key is set
2. FFmpeg is installed (ffmpeg -version)
3. WebRTC connection working (check TURN server)
4. Disk space available for recordings
5. Browser permissions granted (camera/microphone)
```

**Issue: NFT minting failing**

```
Check:
1. Redis is running (redis-cli ping)
2. TON testnet network available (curl https://testnet.toncenter.com/api/...)
3. Wallet balance sufficient (check TON wallet)
4. Job queue processing (php artisan queue:work)
5. Database connection (php artisan tinker)
```

**Issue: High latency on chat**

```
Check:
1. Reverb server running (ps aux | grep reverb)
2. WebSocket connection established (check browser DevTools)
3. Redis connection (redis-cli ping)
4. Message queue backlog (php artisan queue:failed)
5. Database query performance (EXPLAIN ANALYZE)
```

### Debug Mode

```php
// Enable debug logging
Log::channel('debug')->info('Stream stats', [
    'stream_id' => $stream->id,
    'viewers' => $stream->current_viewers,
    'memory' => memory_get_usage(true) / 1024 / 1024 . 'MB',
    'queries' => DB::getQueryLog(),
]);

// Monitor in real-time
tail -f storage/logs/laravel.log | grep "Stream stats"

// Database query analysis
php artisan tinker
>>> DB::enableQueryLog();
>>> $streams = Stream::with('blogger')->get();
>>> DB::getQueryLog();
```

---

## Production Checklist

Before going live, verify:

- [ ] All tests passing (100%)
- [ ] Security scan completed (no vulnerabilities)
- [ ] Load testing successful (10k concurrent users)
- [ ] SSL/TLS certificate installed
- [ ] Backup strategy implemented
- [ ] Monitoring setup (Sentry, Prometheus, Grafana)
- [ ] Email service configured
- [ ] Storage backend configured (S3, local)
- [ ] Rate limiting configured
- [ ] API documentation generated
- [ ] Admin panel accessible
- [ ] Database migrations tested
- [ ] Environment variables set
- [ ] Cron jobs scheduled
- [ ] Error pages customized

---

## Support & Resources

- **API Docs:** `GET /api/documentation`
- **Admin Panel:** `https://example.com/admin`
- **Status Page:** `https://status.example.com`
- **Support Email:** support@bloggers.local
- **Discord:** https://discord.gg/catvrf
- **GitHub:** https://github.com/your-org/catvrf

---

## License

This module is part of CatVRF Platform.  
All rights reserved © 2026.

**Version:** 1.0.0  
**Last Updated:** March 23, 2026  
**Status:** ✅ Production Ready
