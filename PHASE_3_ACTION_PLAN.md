# PHASE 3 ACTION PLAN - Policies & Config

**Следующий шаг для полного CANON 2026 compliance**

**Target**: 100% Production Ready  
**Estimated Time**: 2-3 часов  
**Priority**: CRITICAL для deployment

---

## 🚨 BLOCKING ITEMS FOR PHASE 3

### 1. CREATE POLICIES (10+ files) - CRITICAL

#### Critical Policies (Do First)

```php
// app/Policies/PaymentPolicy.php
- Can create payment (fraud check required)
- Can view payment (tenant scoped)
- Can capture payment (only when authorized)
- Can refund payment (only by owner or admin)

// app/Policies/WalletPolicy.php
- Can view wallet (tenant scoped)
- Can withdraw (fraud + amount limit check)
- Can transfer (between users of same tenant)

// app/Policies/OrderPolicy.php
- Can view order
- Can update order (only draft status)
- Can cancel order (with refund logic)
- Can check out

// app/Policies/HotelPolicy.php
- Can view hotel
- Can update hotel (only owner)
- Can delete hotel (soft delete, with data cleanup)
```

#### Supporting Policies (Medium Priority)

- BeautyPolicy.php (salon CRUD)
- AppointmentPolicy.php (booking management)
- ProductPolicy.php (inventory management)
- InventoryPolicy.php (stock operations)
- CommissionPolicy.php (view own commissions)

#### Template for All Policies

```php
<?php declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Models\Tenant;

/**
 * Policy for [Model] authorization.
 * All methods respect tenant scoping + fraud control.
 */
final class XyzPolicy
{
    use HandlesAuthorization;
    
    public function view(User $user, Tenant $tenant): bool
    {
        // Tenant scoping check
        return $user->tenant_id === $tenant->id;
    }
    
    public function create(User $user): bool
    {
        // Fraud check + role check
        return $user->hasRole('admin') || $user->hasRole('business');
    }
    
    // ... other methods
}
```

---

### 2. CREATE CONFIG FILES (5 files) - HIGH

#### config/fraud.php

```php
<?php declare(strict_types=1);

return [
    // ML Fraud Detection
    'ml' => [
        'enabled' => env('FRAUD_ML_ENABLED', true),
        'min_score_for_block' => 0.85, // 85% certainty = block
        'min_score_for_review' => 0.70, // 70% certainty = manual review
        'model_path' => storage_path('models/fraud'),
        'update_frequency' => 'daily', // 03:00 UTC
    ],
    
    // Rule-based Detection
    'rules' => [
        'max_operations_per_5min' => 5,
        'max_failed_attempts' => 3,
        'new_device_threshold' => 100_000, // 1000 RUB
        'ip_change_threshold' => 50_000,  // 500 RUB
        'suspicious_geo_distance' => 1000, // km (same hour)
    ],
    
    // Rate Limiting
    'rate_limit' => [
        'payments_per_minute' => 10,
        'withdrawals_per_hour' => 50,
        'login_attempts_per_5min' => 5,
    ],
];
```

#### config/payments.php

```php
<?php declare(strict_types=1);

return [
    // Tinkoff Settings
    'drivers' => [
        'tinkoff' => [
            'enabled' => true,
            'terminal_id' => env('TINKOFF_TERMINAL_ID'),
            'password' => env('TINKOFF_PASSWORD'),
            'api_key' => env('TINKOFF_API_KEY'),
            'webhook_secret' => env('TINKOFF_WEBHOOK_SECRET'),
            'sbp_enabled' => true,
        ],
        'tochka' => [
            'enabled' => env('TOCHKA_ENABLED', false),
            'api_key' => env('TOCHKA_API_KEY'),
        ],
        'sber' => [
            'enabled' => env('SBER_ENABLED', false),
            'api_key' => env('SBER_API_KEY'),
        ],
    ],
    
    // Idempotency
    'idempotency' => [
        'ttl' => 3600, // 1 hour
        'hash_algo' => 'sha256',
    ],
    
    // Two-stage Processing
    'capture' => [
        'auto_capture' => false, // Manual capture required
        'capture_delay' => 3600, // seconds (1 hour)
    ],
    
    // Refunds
    'refund' => [
        'max_refund_days' => 365,
        'instant_refund_enabled' => true,
    ],
];
```

#### config/wallet.php

```php
<?php declare(strict_types=1);

return [
    // Commission Rules by Vertical
    'commission' => [
        'default' => 14, // percent
        'beauty' => [
            'base' => 14,
            'migration' => [
                'dikidi' => ['percent' => 10, 'duration' => 120], // days
                'flowwow' => ['percent' => 10, 'duration' => 120],
            ],
        ],
        'auto' => [
            'base' => 15,
            'sidecar' => 5, // fleet commission
        ],
        'food' => [
            'base' => 14,
            'delivery' => 3, // additional for delivery
        ],
        'hotels' => [
            'base' => 14,
            'payout_delay' => 4, // days after checkout
        ],
    ],
    
    // Limits
    'limits' => [
        'min_withdraw' => 10_000, // 100 RUB
        'max_withdraw_per_day' => 10_000_000, // 100,000 RUB
        'max_transfer_per_transaction' => 1_000_000, // 10,000 RUB
    ],
    
    // Caching
    'cache' => [
        'ttl' => 300, // seconds
        'key_prefix' => 'wallet:',
    ],
];
```

#### config/bonuses.php

```php
<?php declare(strict_types=1);

return [
    'types' => [
        'referral_bonus' => [
            'amount' => 200_000, // 2000 RUB
            'condition' => 'turnover >= 5000000', // 50,000 RUB
            'recipient' => 'referrer',
        ],
        'referral_client' => [
            'amount' => 100_000, // 1000 RUB
            'condition' => 'spent >= 1000000', // 10,000 RUB
            'recipient' => 'referrer',
        ],
        'turnover_bonus' => [
            'amount' => 200_000, // 2000 RUB
            'condition' => 'monthly_turnover >= 5000000',
            'recipient' => 'business',
        ],
        'loyalty_bonus' => [
            'amount' => 50_000, // 500 RUB per purchase
            'condition' => 'every_purchase',
            'recipient' => 'user',
        ],
    ],
    
    'withdrawal_rules' => [
        'business' => 'can_withdraw', // можно выводить
        'user' => 'spend_only', // только тратить
    ],
];
```

#### config/verticals.php

```php
<?php declare(strict_types=1);

return [
    'registered' => [
        'beauty' => [
            'name' => 'Beauty & Wellness',
            'commission' => 14,
            'payment_delay' => 0,
            'features' => ['rating', 'portfolio', 'appointment', 'consumables'],
        ],
        'auto' => [
            'name' => 'Auto & Mobility',
            'commission' => 15,
            'payment_delay' => 0,
            'features' => ['gps_tracking', 'surge_pricing', 'fleet'],
        ],
        'food' => [
            'name' => 'Food & Delivery',
            'commission' => 14,
            'payment_delay' => 0,
            'features' => ['kds', 'delivery_zones', 'inventory'],
        ],
        'hotels' => [
            'name' => 'Hotels & Accommodation',
            'commission' => 14,
            'payment_delay' => 96, // hours (4 days)
            'features' => ['booking', 'invoicing', 'housekeeping'],
        ],
        'realestate' => [
            'name' => 'Real Estate',
            'commission' => 14,
            'payment_delay' => 0,
            'features' => ['3d_tours', 'mortgage', 'legal'],
        ],
    ],
];
```

---

## 📋 IMPLEMENTATION CHECKLIST

### Step 1: Create Policies (1 hour)

- [ ] PaymentPolicy.php (CRITICAL)
- [ ] WalletPolicy.php (CRITICAL)
- [ ] OrderPolicy.php (CRITICAL)
- [ ] BeautyPolicy.php
- [ ] HotelPolicy.php
- [ ] AppointmentPolicy.php
- [ ] ProductPolicy.php
- [ ] InventoryPolicy.php
- [ ] CommissionPolicy.php
- [ ] TenantPolicy.php (review existing)

Register in: `AuthServiceProvider.php`

```php
Gate::policy(Payment::class, PaymentPolicy::class);
Gate::policy(Wallet::class, WalletPolicy::class);
// ... etc
```

### Step 2: Create Config Files (1 hour)

- [ ] config/fraud.php
- [ ] config/payments.php
- [ ] config/wallet.php
- [ ] config/bonuses.php
- [ ] config/verticals.php

Publish configs:

```bash
php artisan config:clear
```

### Step 3: Register Policies in Controllers (30 min)

Update each Controller's method:

```php
public function store(StorePaymentRequest $request): JsonResponse
{
    // Check authorization
    $this->authorize('create', Payment::class);
    
    // ... rest of logic
}
```

### Step 4: Test Authorization (30 min)

```bash
php artisan test --filter PolicyTest
php artisan test --filter PaymentTest
```

---

## 🧪 TESTING PHASE 3

```bash
# Test Policies
php artisan test tests/Unit/Policies/

# Test Config Loading
php artisan config:cache
php artisan config:show fraud

# Test Controllers with Authorization
php artisan test tests/Feature/Controllers/

# Full Integration Test
php artisan test tests/Feature/Payments/
```

---

## 🚀 DEPLOYMENT AFTER PHASE 3

```bash
# 1. Migrate (if any new tables)
php artisan migrate

# 2. Cache config
php artisan config:cache

# 3. Cache routes
php artisan route:cache

# 4. Publish assets (if any)
php artisan publish

# 5. Run tests
php artisan test

# 6. Deploy!
```

---

## ⚠️ CRITICAL NOTES

1. **Policies must use tenant scoping**

   ```php
   public function view(User $user, Model $model): bool
   {
       return $user->tenant_id === $model->tenant_id;
   }
   ```

2. **Config values must be env() with fallbacks**

   ```php
   'terminal_id' => env('TINKOFF_TERMINAL_ID', 'TEST_TERMINAL'),
   ```

3. **All authorization checks must happen BEFORE data operations**

   ```php
   $this->authorize('create', Model::class);
   // THEN do operations
   ```

4. **Log all authorization denials**

   ```php
   public function create(User $user): bool
   {
       if (!$user->hasRole('admin')) {
           Log::warning('Unauthorized access attempt', ['user_id' => $user->id]);
           return false;
       }
       return true;
   }
   ```

---

**Ready for Phase 3?** ✅ YES  
**Time Estimate**: 2-3 hours  
**Difficulty**: MEDIUM (straightforward but requires attention to detail)

Generated: 18 марта 2026
