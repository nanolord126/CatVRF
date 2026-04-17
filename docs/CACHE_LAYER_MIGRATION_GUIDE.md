# Cache Layer Migration Guide for All Verticals

## Overview
This guide explains how to manually update all 64 business verticals to use the new `CacheService` instead of direct `Cache::` facade calls.

## Prerequisites
- New `CacheService` is available at `app/Services/Cache/CacheService.php`
- New `CacheMetricsService` is available at `app/Services/Cache/CacheMetricsService.php`
- Model observers are registered in `app/Providers/EventServiceProvider.php`

## Migration Steps per Vertical

### 1. Update Service Constructor

**Before:**
```php
public function __construct(
    private readonly SomeDependency $dep,
) {}
```

**After:**
```php
public function __construct(
    private readonly SomeDependency $dep,
    private readonly CacheService $cache,
) {}
```

### 2. Replace Cache::remember with CacheService::rememberWithTags

**Before:**
```php
$result = Cache::remember("beauty:slots:{$masterId}:{$date}", 60, function () {
    return $this->fetchSlots();
});
```

**After:**
```php
$result = $this->cache->rememberWithTags(
    tenant()?->id,
    $this->cache->getSlotsKey(tenant()?->id, $masterId, $date),
    CacheService::TTL_SLOTS,
    ['beauty', 'slots'],
    fn () => $this->fetchSlots()
);
```

### 3. Replace Cache::forget with CacheService::invalidate

**Before:**
```php
Cache::forget("beauty:slots:{$masterId}:{$date}");
```

**After:**
```php
$this->cache->invalidateSlots(tenant()?->id, $masterId, null, $date);
```

### 4. Replace Cache::tags()->flush with CacheService::invalidate

**Before:**
```php
Cache::tags(['beauty', 'slots'])->flush();
```

**After:**
```php
$this->cache->invalidateVertical(tenant()?->id, 'beauty');
```

### 5. Add Model Observer

Create observer for main vertical model:

**File:** `app/Observers/BeautyServiceObserver.php`
```php
<?php declare(strict_types=1);

namespace App\Observers;

use App\Domains\Beauty\Models\BeautyService;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

final readonly class BeautyServiceObserver
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    public function created(BeautyService $entity): void
    {
        $this->invalidateRelatedCache($entity);
    }

    public function updated(BeautyService $entity): void
    {
        $this->invalidateRelatedCache($entity);
    }

    public function deleted(BeautyService $entity): void
    {
        $this->invalidateRelatedCache($entity);
    }

    private function invalidateRelatedCache(BeautyService $entity): void
    {
        $tenantId = $entity->tenant_id ?? null;

        try {
            $this->cache->invalidateVertical($tenantId, 'beauty');
            
            Log::info('BeautyService cache invalidated', [
                'tenant_id' => $tenantId,
                'entity_id' => $entity->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate BeautyService cache', [
                'tenant_id' => $tenantId,
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Register in EventServiceProvider:**
```php
use App\Observers\BeautyServiceObserver;
use App\Domains\Beauty\Models\BeautyService;

public function boot(): void
{
    BeautyService::observe(BeautyServiceObserver::class);
}
```

## Vertical-Specific TTL Constants

Use these TTL constants from `CacheService`:

| Cache Type | TTL Constant | Value |
|------------|--------------|-------|
| Medical Diagnosis | TTL_MEDICAL_DIAGNOSIS | 300s |
| Medical Health Score | TTL_MEDICAL_HEALTH_SCORE | 600s |
| Recommendations | TTL_RECOMMENDATIONS | 900s |
| Slots/Availability | TTL_SLOTS | 60s |
| Dynamic Pricing | TTL_DYNAMIC_PRICE | 300s |
| Embeddings | TTL_EMBEDDINGS | 86400s |
| Quota Counters | TTL_QUOTA_COUNTERS | 300s |

## Example: Beauty Vertical

### Services to Update:
1. `BeautyBookingService.php` - booking slots cache
2. `DynamicPricingService.php` - pricing cache
3. `BeautyLoyaltyService.php` - loyalty points cache
4. `BookingSlotHoldService.php` - slot holds cache

### Models to Add Observers:
1. `BeautyService` - main service model
2. `Appointment` - appointments model

## Automated Migration

The automated command `php artisan cache:update-verticals` is available but requires:
- Linux/Mac environment (needs pcntl/posix extensions)
- Laravel Horizon installed
- Docker or WSL on Windows

To run on Linux/Mac:
```bash
# Dry run (preview changes)
php artisan cache:update-verticals --dry-run

# Update specific vertical
php artisan cache:update-verticals --vertical=beauty

# Update all verticals
php artisan cache:update-verticals
```

## Checklist per Vertical

- [ ] Add `CacheService` injection to all service constructors
- [ ] Replace `Cache::remember` with `cacheService->rememberWithTags`
- [ ] Replace `Cache::forget` with `cacheService->invalidate*`
- [ ] Replace `Cache::tags()->flush` with `cacheService->invalidateVertical`
- [ ] Create observer for main model
- [ ] Register observer in EventServiceProvider
- [ ] Test cache invalidation manually
- [ ] Run unit tests for the vertical

## Priority Order

1. **High Priority** (Core Business):
   - Medical, Beauty, Food, Auto, Hotels

2. **Medium Priority** (Revenue):
   - Travel, Fashion, Luxury, Electronics, Sports

3. **Low Priority** (Supporting):
   - All other verticals (55 remaining)

## Expected Results

After migration:
- ✅ Automatic cache invalidation via observers
- ✅ Tenant-isolated cache keys
- ✅ Stampede protection with atomic locks
- ✅ Prometheus metrics for observability
- ✅ Layered cache fallback (Redis → File)
- ✅ Proper TTL per cache type

## Support

For issues or questions, refer to:
- `app/Services/Cache/CacheService.php` - Main service API
- `tests/Unit/Services/Cache/CacheServiceTest.php` - Usage examples
- `app/Observers/MedicalRecordObserver.php` - Observer example
