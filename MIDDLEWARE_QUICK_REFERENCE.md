# MIDDLEWARE QUICK REFERENCE 2026

## ✅ MIDDLEWARE STATUS

| Middleware | File | Status | Purpose |
|-----------|------|--------|---------|
| **B2CB2BMiddleware** | `app/Http/Middleware/B2CB2BMiddleware.php` | ✅ Ready | B2C/B2B mode detection |
| **AgeVerificationMiddleware** | `app/Http/Middleware/AgeVerificationMiddleware.php` | ✅ Ready | Age 18+/21+ verification |
| **RateLimitingMiddleware** | `app/Http/Middleware/RateLimitingMiddleware.php` | ✅ Ready | Anti-spam/brute-force |
| **FraudCheckMiddleware** | `app/Http/Middleware/FraudCheckMiddleware.php` | ✅ Ready | ML fraud detection |
| **TenantMiddleware** | `app/Http/Middleware/TenantMiddleware.php` | ✅ Ready | Data isolation |

---

## 🎯 UPDATED CONTROLLERS (16 total)

### Beauty Vertical
```
✅ AppointmentController → rate-limit-beauty, b2c-b2b, fraud-check
```

### Party & Events
```
✅ PartySuppliesController → rate-limit-party, b2c-b2b, fraud-check
```

### Luxury
```
✅ LuxuryBookingController → rate-limit-luxury, b2c-b2b, fraud-check
```

### Insurance
```
✅ InsuranceController → rate-limit-insurance, age-verification:18, fraud-check
```

### Internal (Webhooks)
```
✅ PaymentWebhookController → webhook:payment_gateway, webhook-signature, idempotency
```

### Analytics V2
```
✅ FraudDetectionController → rate-limit-analytics, role:admin|manager|accountant
✅ AnalyticsController → rate-limit-analytics, role:admin|manager|accountant
✅ ReportingController → rate-limit-analytics, role:admin|manager|accountant
✅ RecommendationController → rate-limit-recommendations, fraud-check
✅ MLAnalyticsController → rate-limit-analytics, role:admin|manager
```

### Realtime V2
```
✅ ChatController → rate-limit-chat, fraud-check
✅ SearchController → rate-limit-search, tenant
✅ CollaborationController → rate-limit-collaboration, role:admin|manager|team_lead
```

### Promo V1
```
✅ PromoController → rate-limit-promo, b2c-b2b, fraud-check
```

### Wedding Planning V1
```
✅ WeddingPublicController → rate-limit-wedding, b2c-b2b, fraud-check
```

---

## 🔐 RATE LIMITS

| Operation | Limit |
|-----------|-------|
| Beauty appointments | 50/min |
| Party bookings | 100/min |
| Luxury operations | 20/min |
| Promo apply | 50/min |
| Chat messages | 500/hour |
| Search queries | 1000 light / 100 heavy /hour |
| Analytics | 1000 light / 100 heavy /hour |
| Recommendations | 500/hour |
| Wedding bookings | 100/min |
| Insurance policies | 50/min |

---

## 🔑 EXAMPLE: How to Use in Routes

```php
// routes/api.php

Route::post('/api/beauty/appointments', [\App\Http\Controllers\Beauty\AppointmentController::class, 'store'])
    ->middleware('auth:sanctum') // Already in controller, but can be here too
    ->name('beauty.appointments.store');

// The controller constructor will automatically apply:
// - rate-limit-beauty
// - b2c-b2b
// - tenant
// - fraud-check (for this specific method)
```

---

## 📊 DATA FLOW WITH MIDDLEWARE

```
REQUEST
    ↓
[1] auth:sanctum → Verify JWT token
    ↓
[2] rate-limit-* → Check tenant-aware rate limits
    ↓
[3] b2c-b2b → Determine $request->b2c_mode / $request->b2b_mode
    ↓
[4] tenant → Apply tenant scoping (Global Scope)
    ↓
[5] fraud-check → ML fraud detection + rules
    ↓
[6] Controller Method
    ↓
RESPONSE (with correlation_id in audit log)
```

---

## 🛡️ SECURITY LAYERS

### Layer 1: Authentication
```php
$this->middleware('auth:sanctum');
// Verifies JWT token, sets auth()->user()
```

### Layer 2: Rate Limiting
```php
$this->middleware('rate-limit-beauty'); // 50/min
// Prevents brute-force, spam, DDoS
```

### Layer 3: Mode Detection
```php
$this->middleware('b2c-b2b');
// Sets $request->b2c_mode / $request->b2b_mode
// Differentiates pricing, permissions, capabilities
```

### Layer 4: Multi-Tenancy
```php
$this->middleware('tenant');
// Applies Global Scope: WHERE tenant_id = X
// Ensures data isolation
```

### Layer 5: Fraud Detection
```php
$this->middleware('fraud-check', ['only' => ['store', 'payment']]);
// ML fraud scoring (0-1)
// Blocks suspicious transactions
// Logs all anomalies
```

### Layer 6: Age Verification (Sensitive Verticals)
```php
$this->middleware('age-verification:18');
// Blocks under-18 users from alcohol, pharmacy, etc.
// Checks date_of_birth vs today
```

---

## 📝 KERNEL.PHP REGISTRATION

All middleware are registered in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'rate-limit-beauty' => \App\Http\Middleware\RateLimitingMiddleware::class . ':beauty:50',
    'rate-limit-luxury' => \App\Http\Middleware\RateLimitingMiddleware::class . ':luxury:20',
    'b2c-b2b' => \App\Http\Middleware\B2CB2BMiddleware::class,
    'tenant' => \App\Http\Middleware\TenantMiddleware::class,
    'fraud-check' => \App\Http\Middleware\FraudCheckMiddleware::class,
    'age-verification:18' => \App\Http\Middleware\AgeVerificationMiddleware::class . ':18',
    'webhook:payment_gateway' => \App\Http\Middleware\WebhookValidationMiddleware::class,
    // ... more
];
```

---

## 🚀 HOW TO ADD MIDDLEWARE TO NEW CONTROLLERS

### Step 1: Create Controller with Constructor
```php
final class MyNewController extends Controller
{
    public function __construct(
        private readonly MyService $service
    ) {
        // Add middleware here
        $this->middleware('auth:sanctum');
        $this->middleware('rate-limit-mynew');
        $this->middleware('tenant');
    }
}
```

### Step 2: Apply Only to Specific Methods
```php
$this->middleware('fraud-check', ['only' => ['store', 'update']]);
```

### Step 3: Exclude Public Methods
```php
$this->middleware('auth:sanctum')->except(['index', 'show']);
```

### Step 4: Check in Methods (if needed)
```php
public function store(Request $request): JsonResponse
{
    $isB2B = $request->b2b_mode; // Set by B2CB2BMiddleware
    $correlationId = $request->header('X-Correlation-ID');
    
    // All other middleware checks are automatic
}
```

---

## ✅ VERIFICATION CHECKLIST

Before deploying:

- [ ] All 16 controllers have middleware in constructor
- [ ] All middleware are registered in `app/Http/Kernel.php`
- [ ] Rate limits are appropriate for each operation
- [ ] Fraud check is applied to all mutable operations
- [ ] Age verification is applied to sensitive verticals
- [ ] B2C/B2B mode is correctly determined
- [ ] Tenant scoping is working (test with 2 tenants)
- [ ] correlation_id is logged in all operations
- [ ] All tests pass with middleware enabled

---

## 🔍 DEBUGGING

### Check if Middleware is Applied
```bash
php artisan route:list | grep -i appointment
```

### Check Middleware Order
```bash
php artisan middleware:list
```

### Test Fraud Check
```bash
curl -X POST http://localhost/api/beauty/appointments \
  -H "X-Correlation-ID: test-123" \
  -H "Authorization: Bearer {token}" \
  -d "master_id=1&datetime=2026-03-28"
# Should be blocked if fraud score > threshold
```

### Test Rate Limiting
```bash
# First 50 requests = OK (200)
# Request 51 = Rejected (429)
for i in {1..55}; do
  curl http://localhost/api/beauty/appointments \
    -H "Authorization: Bearer {token}"
done
```

### Test B2C/B2B Mode
```bash
# B2C request (no inn)
curl -X POST http://localhost/api/beauty/appointments \
  -H "Authorization: Bearer {token}" \
  -d "phone=+7999..."

# B2B request (with inn + business_card_id)
curl -X POST http://localhost/api/beauty/appointments \
  -H "Authorization: Bearer {token}" \
  -H "X-Inn: 7712345678" \
  -H "X-Business-Card-Id: bc-123" \
  -d "phone=+7999..."
```

---

## 📚 RELATED FILES

- Middleware: `app/Http/Middleware/`
- Kernel: `app/Http/Kernel.php`
- Controllers: `app/Http/Controllers/` (all updated)
- Full Report: `MIDDLEWARE_IMPLEMENTATION_2026.md`

---

**Implementation Date:** 27 Марта 2026  
**Mode:** Production Ready  
**Status:** ✅ Complete
