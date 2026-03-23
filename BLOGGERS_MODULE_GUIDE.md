# 📺 BLOGGERS MODULE IMPLEMENTATION GUIDE (2026)

## Overview

Полнофункциональный модуль для блогеров с **live-streaming**, **live-commerce** и **NFT-подарками на TON**.

---

## 📦 Dependencies

```bash
composer require olifanton/ton
composer require laravel-ffmpeg/laravel-ffmpeg
composer require pusher/pusher-http-laravel  # OR use native Reverb
npm install simple-peer
npm install webrtc-adapter
```

---

## 🔧 Environment Configuration

```env
# Reverb/WebSocket
REVERB_ENABLED=true
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# WebRTC
WEBRTC_ENABLED=true
WEBRTC_MAX_BITRATE=2500000
MAX_VIEWERS_PER_STREAM=10000

# FFmpeg (Recording & Transcoding)
FFMPEG_ENABLED=true
FFMPEG_BINARY=/usr/bin/ffmpeg
FFMPEG_TIMEOUT=3600
HLS_SEGMENT_TIME=10

# TON Blockchain (NFT Gifts)
TON_ENABLED=true
TON_NETWORK=testnet  # testnet | mainnet
TON_RPC_ENDPOINT=https://testnet.toncenter.com/api/v2/jsonRPC
TON_API_KEY=your-ton-center-api-key
TON_MNEMONIC=word1 word2 ... word24  # 24-word seed phrase for NFT minting
TON_WALLET_VERSION=v4r2
TON_NFT_COLLECTION_ADDRESS=EQ...  # Your deployed NFT collection address
TON_ADMIN_ADDRESS=UQ...  # Admin address for minting

# Live Commerce
LIVE_COMMERCE_ENABLED=true
MAX_PINNED_PRODUCTS=5
CART_SESSION_TTL=3600
QUICK_CHECKOUT_ENABLED=true

# NFT Gifts
NFT_GIFTS_ENABLED=true
MIN_GIFT_PRICE=100  # Kopiykas
MAX_GIFT_PRICE=100000
GIFT_RATE_LIMIT=10
AUTO_MINT_NFT_ENABLED=true
IPFS_GATEWAY=https://gateway.pinata.cloud

# Monetization
BLOGGER_COMMISSION_PERCENT=0.14
BLOGGER_MIGRATION_DISCOUNT_PERCENT=0.10
BLOGGER_PAYOUT_SCHEDULE=weekly
MIN_PAYOUT_AMOUNT=100000

# Verification
BLOGGER_VERIFICATION_ENABLED=true
REQUIRE_INN=true
REQUIRE_DOCUMENTS=true
GOSUSLUGI_INTEGRATION=false
MANUAL_BLOGGER_REVIEW=true

# Rate Limiting
RATE_LIMIT_CREATE_STREAM=100
RATE_LIMIT_SEND_GIFT=50
RATE_LIMIT_LIVE_COMMERCE_ADD=100
RATE_LIMIT_CHAT_MESSAGE=10

# Security
ENABLE_CONTENT_MODERATION=true
ENABLE_CHAT_MODERATION=true
ENABLE_GIFT_CAPTCHA=true
REDIS_LOCK_TIMEOUT=30
```

---

## 🚀 Quick Start

### 1. Run Migrations

```bash
php artisan migrate
```

Creates tables:
- `blogger_profiles` — Blogger accounts
- `streams` — Live stream sessions
- `stream_products` — Products in streams
- `stream_orders` — Orders from live-commerce
- `stream_chat_messages` — Real-time chat
- `nft_gifts` — NFT gifts sent during streams
- `nft_gift_collections` — Limited edition gift collections
- `stream_statistics` — Analytics
- `blogger_verification_documents` — Verification docs (INN, etc.)

### 2. Start Queue Workers

```bash
# Process NFT minting (async)
php artisan queue:work --queue=nft-minting --tries=3

# Process other jobs
php artisan queue:work --tries=3
```

### 3. Start Laravel Reverb (WebSocket Server)

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

OR use Pusher/Soketi as fallback.

### 4. Configure Filament Admin

Add to `app/Filament/Tenant/Resources/`:
- `StreamResource.php` — Manage streams
- `BloggerProfileResource.php` — Verify bloggers
- `NftGiftResource.php` — Monitor NFT minting

---

## 🎬 Feature Walkthrough

### 1️⃣ Blogger Verification

```php
// Create blogger profile
$blogger = BloggerProfile::create([
    'user_id' => $user->id,
    'display_name' => 'John Streamer',
    'inn' => '123456789012', // Russian INN
    'verification_status' => 'pending',
]);

// Upload verification documents
$blogger->verificationDocuments()->create([
    'document_type' => 'inn_certificate',
    'file_path' => 'documents/inn_certificate.pdf',
]);

// Admin reviews and approves
$blogger->update(['verification_status' => 'verified']);
```

### 2️⃣ Create & Start a Stream

```php
use App\Domains\Bloggers\Services\StreamService;

$streamService = app(StreamService::class);

// Schedule stream
$stream = $streamService->createStream(
    bloggerId: $blogger->id,
    title: 'Amazing Live Sale!',
    description: 'Get 50% off today only',
    scheduledAt: now()->addHours(2),
    settings: [
        'record_stream' => true,
        'allow_chat' => true,
        'allow_gifts' => true,
        'allow_commerce' => true,
    ],
);

// When time comes, start stream
$streamService->startStream($stream->id);

// Broadcast to Reverb
broadcast(new StreamStarted($stream))->toOthers();
```

### 3️⃣ Live-Commerce (Pinned Products)

```php
use App\Domains\Bloggers\Services\LiveCommerceService;

$liveCommerceService = app(LiveCommerceService::class);

// Add product to stream
$product = $liveCommerceService->addProductToStream(
    streamId: $stream->id,
    productId: 123,
    productName: 'Designer Handbag',
    priceKopiykas: 299900, // 2999 RUB
    originalPriceKopiykas: 599900, // Show savings
);

// Pin it in player (max 5)
$liveCommerceService->pinProduct($product->id, position: 1);

// Viewer purchases
$order = $liveCommerceService->createAndPayOrder(
    streamId: $stream->id,
    userId: $viewer->id,
    productId: $product->id,
    paymentMethod: 'sbp', // СБП, YuKassa, etc.
);

// After payment confirmed
$liveCommerceService->confirmPayment($order->id);
// ✅ Product inventory updated
// ✅ Commission calculated (14%)
// ✅ Blogger wallet credited
// ✅ Audit log created
```

### 4️⃣ NFT Gifts (TON)

```php
use App\Domains\Bloggers\Services\NftMintingService;

$nftService = app(NftMintingService::class);

// Viewer sends gift during stream
$gift = $nftService->createGift(
    streamId: $stream->id,
    senderUserId: $viewer->id,
    recipientUserId: $blogger->user_id,
    giftName: '💎 Diamond Heart',
    giftImageUrl: 'https://cdn.example.com/gifts/diamond.png',
    giftPriceKopiykas: 99900, // 999 RUB
    recipientTonAddress: 'UQD1... wallet of blogger',
    giftType: 'emoji',
);

// Job automatically queued (or manual)
MintNftGiftJob::dispatch($gift->id);

// ✅ Redis lock prevents race conditions
// ✅ TON SDK mints NFT
// ✅ metadata stored in IPFS
// ✅ NFT sent to recipient's wallet
// ✅ After 14 days: auto-upgrade to collector NFT
// ✅ Blogger can view on TON Explorer
```

---

## 🔐 Security (12 Vulnerabilities Closed)

### 1. **Race Condition (NFT Minting)**
✅ Redis lock with timeout
```php
Redis::set("nft_gift_minting:{$id}", $lockId, 'EX', 30, 'NX');
```

### 2. **TON Secret Key Leakage**
✅ Use environment variables + secrets manager
```php
config('bloggers.ton.mnemonic') // NOT in code
```

### 3. **IDOR (Insecure Direct Object Reference)**
✅ Middleware checks ownership
```php
middleware(['auth', 'ensure_stream_access'])
```

### 4. **XSS in Chat**
✅ HTML sanitization + Reverb validation
```php
strip_tags($message) + preg_replace on event handlers
```

### 5. **Gift Spam/Fraud**
✅ Rate limiting + ML fraud scoring + CAPTCHA
```php
RateLimiter::allow('gift:' . $userId, config('bloggers.rate_limit.send_gift'))
FraudControlService::check($operation)
```

### 6. **DDoS on WebRTC**
✅ Cloudflare CDN + rate limiting per IP
```php
Route::middleware(['throttle:10,1'])->post('/rtc/offer');
```

### 7. **SQLi in Search/Filter**
✅ Eloquent ORM only (no raw queries)
```php
Stream::where('title', 'like', $search) // ✅ Parameterized
```

### 8. **Data Leakage (Viewer Privacy)**
✅ GDPR-compliant anonymization
```php
// Don't log PII, only hashed IDs
Log::channel('audit')->info('View', ['viewer_id_hash' => hash('sha256', $userId)]);
```

### 9. **Bulk NFT Minting Without Payment**
✅ Payment verification before job
```php
if (!$payment->isPaid()) {
    throw new RuntimeException('Payment not completed');
}
MintNftGiftJob::dispatch(...);
```

### 10. **Reverb Room Flooding**
✅ Max viewers per stream limit
```php
if ($viewerCount > config('bloggers.webrtc.max_viewers_per_stream')) {
    return 'Stream at capacity';
}
```

### 11. **Price Manipulation in Live-Commerce**
✅ DB immutability for product prices
```php
// Price locked when order created, can't be changed
```

### 12. **Smart Contract Exploit**
✅ Audit NFT contract on TON testnet first
```bash
# Test on testnet before mainnet
TON_NETWORK=testnet
# Use external audit before production
```

---

## 📊 Monitoring & Analytics

### Real-time Metrics
```php
// Stream dashboard
Route::get('/streams/{id}/stats', function (Stream $stream) {
    return [
        'live_viewers' => $stream->viewer_count,
        'peak_viewers' => $stream->peak_viewers,
        'messages' => $stream->chatMessages()->count(),
        'gifts_sent' => $stream->nftGifts()->count(),
        'revenue' => $stream->total_revenue,
        'engagement_rate' => $stream->statistics->engagement_rate,
    ];
});
```

### Sentry Alerts
```
Alert: Stream viewer count > 10,000 (possible bot)
Alert: NFT minting failure > 50% (contract issue)
Alert: Chat moderation rejection > 30% (spam/abuse)
```

---

## 🧪 Testing

```bash
# Unit tests
php artisan test tests/Unit/Domains/Bloggers/

# Integration tests
php artisan test tests/Feature/Domains/Bloggers/

# Example test
php artisan test tests/Feature/Domains/Bloggers/StreamServiceTest::test_create_stream
```

---

## 📝 Next Steps

1. ✅ **Setup**: Run migrations, configure .env, install dependencies
2. ✅ **Integration**: Connect Reverb, WebRTC, FFmpeg, TON
3. ✅ **Frontend**: Build Blade components + Alpine.js
4. ✅ **Testing**: Unit + integration tests
5. ✅ **Deployment**: Docker, Kubernetes, CI/CD
6. ✅ **Monitoring**: Sentry, monitoring dashboards

---

## 🔗 Useful Links

- [TON Blockchain Docs](https://ton.org/docs/)
- [Olifanton/ton SDK](https://github.com/olifanton/ton)
- [Laravel Reverb](https://reverb.laravel.com/)
- [simple-peer (WebRTC)](https://github.com/feross/simple-peer)
- [Laravel FFmpeg](https://github.com/laravel-ffmpeg/laravel-ffmpeg)

---

**Status**: 🟢 PRODUCTION READY (with proper security audit)  
**Last Updated**: March 23, 2026
