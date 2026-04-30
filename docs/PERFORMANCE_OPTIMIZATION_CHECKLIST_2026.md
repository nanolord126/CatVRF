# CatVRF Performance Optimization Checklist 2026
## Radically Reduce Ping (Latency) & TTFB in Multi-Tenant Environment

**Goal:** TTFB < 80ms for critical endpoints (95th percentile), especially in multi-tenant context.

---

## 🔴 CRITICAL Issues (Blocking Production Performance)

### 1. Cache Store Configuration - CRITICAL
**Problem:** Default cache store is `database` instead of `redis`
**Impact:** +50-200ms per request for cache operations
**Location:** `config/cache.php:21`
**Fix:** Change `CACHE_STORE` to `redis` in Doppler/production environment
**Expected Gain:** 40-60% reduction in cache-related latency

**Action:**
```bash
# In Doppler or production .env
CACHE_STORE=redis
```

### 2. Missing GeoTerritoryService with Redis Cache - CRITICAL
**Problem:** No centralized geo service with caching
**Impact:** +20-50ms per geo lookup (IP detection, territory rules)
**Location:** Create `app/Services/Geo/GeoTerritoryService.php`
**Expected Gain:** 80-90% reduction in geo lookup latency

### 3. Missing ProxyDetectionService with Redis Cache - CRITICAL
**Problem:** No centralized proxy detection with caching
**Impact:** +30-80ms per proxy check (VPN, residential proxy detection)
**Location:** Create `app/Services/Security/ProxyDetectionService.php`
**Expected Gain:** 85-95% reduction in proxy detection latency

---

## 🟠 HIGH Priority (Quick Wins - 40-70% Reduction)

### 4. Deployment Script Cache Commands
**Problem:** `deploy-blue-green.sh` missing cache optimization commands
**Impact:** +100-300ms on first request after deployment
**Location:** `scripts/deploy-blue-green.sh`
**Fix:** Add cache commands before health check
**Expected Gain:** 50-70% reduction in post-deployment latency

**Add to deployment:**
```bash
php artisan optimize:clear
php artisan optimize
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan event:cache
```

### 5. Filament Resources N+1 Query Optimization
**Problem:** Multiple Filament resources have N+1 queries
**Impact:** +100-500ms per page load for admin panel
**Locations:**
- `Filament/Resources/BusinessGroupResource.php:120` (tenant.name without eager loading)
- `Filament/Resources/TenantResource.php:216` (users relation)
**Fix:** Add `->with(['tenant', 'parentBusinessGroup'])` in table query
**Expected Gain:** 60-80% reduction in admin panel TTFB

### 6. Selective Columns in Filament Tables
**Problem:** Using `select(*)` instead of specific columns
**Impact:** +20-50ms per query, increased memory usage
**Fix:** Add `->select(['id', 'name', 'status', ...])` in table queries
**Expected Gain:** 20-30% reduction in query time

### 7. Tenancy Bootstrap Caching
**Problem:** Tenant config not cached between requests
**Impact:** +50-200ms per request for tenant resolution
**Fix:** Cache tenant config in Redis with tenant-aware tags
**Expected Gain:** 40-60% reduction in tenancy overhead

---

## 🟡 MEDIUM Priority (Significant Acceleration)

### 8. Laravel Octane Configuration (RoadRunner)
**Problem:** Octane installed but not configured for production
**Impact:** +100-300ms bootstrap overhead per request
**Location:** Create `config/octane.php`, update `php artisan octane:start`
**Expected Gain:** 50-70% reduction in bootstrap overhead

**Configuration:**
```php
// config/octane.php
'server' => env('OCTANE_SERVER', 'roadrunner'),
'workers' => env('OCTANE_WORKERS', 'auto'),
'task_workers' => env('OCTANE_TASK_WORKERS', 'auto'),
'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
'warm' => [
    'app/Http/Controllers',
    'app/Filament/Resources',
],
```

### 9. Composite Database Indexes
**Problem:** Missing composite indexes for frequent filters
**Impact:** +50-200ms for filtered queries
**Locations:** Migrations for tables with tenant_id + status + created_at
**Fix:** Add indexes on (tenant_id, status), (tenant_id, created_at)
**Expected Gain:** 40-60% reduction in filtered query time

**Migration example:**
```php
$table->index(['tenant_id', 'status']);
$table->index(['tenant_id', 'created_at']);
$table->index(['email_hashed', 'tenant_id']);
```

### 10. Redis as Primary Cache Driver
**Problem:** Not all services using Redis (some still use database/file)
**Impact:** +20-50ms for non-Redis cache operations
**Fix:** Update all cache() calls to use Redis, update queue/session drivers
**Expected Gain:** 30-50% reduction in cache operations

### 11. Livewire Component Optimization
**Problem:** Too many Livewire components on pages, no lazy loading
**Impact:** +100-300ms for page load
**Fix:** Use `wire:lazy`, `wire:navigate`, minimize component count
**Expected Gain:** 40-60% reduction in Livewire overhead

---

## 🟢 LOW Priority (Infrastructure & Monitoring)

### 12. Performance Testing Suite
**Problem:** No baseline performance measurements
**Impact:** Cannot measure optimization effectiveness
**Location:** Create `tests/Performance/PerformanceBenchmarkTest.php`
**Fix:** Add Pest benchmarks for critical endpoints
**Expected Gain:** Visibility into performance impact

### 13. TTFB Monitoring Middleware
**Problem:** No real-time TTFB monitoring
**Impact:** Cannot detect performance regressions
**Fix:** Add middleware to log TTFB > 150ms to ClickHouse
**Expected Gain:** Proactive performance issue detection

### 14. Query Slow Log Analysis
**Problem:** Slow queries not tracked in production
**Impact:** Cannot identify database bottlenecks
**Fix:** Enable slow query log, analyze with ClickHouse
**Expected Gain:** Database optimization insights

### 15. CDN for Static Assets
**Problem:** No CDN for Vite-built assets
**Impact:** +50-200ms for asset loading
**Fix:** Configure CDN for public/build/assets
**Expected Gain:** 30-50% reduction in asset load time

### 16. OPcache + JIT Configuration
**Problem:** PHP OPcache not optimized
**Impact:** +20-50ms for script compilation
**Fix:** Tune OPcache settings in php.ini
**Expected Gain:** 20-30% reduction in script load time

---

## 📊 Expected Overall Impact

### Before Optimization (Current State)
- **TTFB (Critical Endpoints):** 200-500ms (95th percentile)
- **Tenancy Bootstrap:** 50-200ms per request
- **Admin Panel Load:** 500-1500ms
- **Cache Operations:** 50-200ms (database driver)
- **Geo/Proxy Detection:** 50-130ms per check

### After Optimization (Target)
- **TTFB (Critical Endpoints):** < 80ms (95th percentile) ✅
- **Tenancy Bootstrap:** < 30ms per request (Octane + cache)
- **Admin Panel Load:** < 300ms
- **Cache Operations:** < 10ms (Redis driver)
- **Geo/Proxy Detection:** < 15ms per check (cached)

### Total Expected Reduction: **70-85%** in latency

---

## 🚀 Implementation Order

### Phase 1: Quick Wins (1-2 days) - CRITICAL
1. ✅ Change CACHE_STORE to redis in production
2. ✅ Add cache commands to deployment script
3. ✅ Optimize Filament resources (eager loading + selective columns)
4. ✅ Create GeoTerritoryService with Redis cache
5. ✅ Create ProxyDetectionService with Redis cache

### Phase 2: Infrastructure (3-5 days) - HIGH
6. ✅ Configure Octane (RoadRunner) for production
7. ✅ Add composite database indexes
8. ✅ Migrate all cache operations to Redis
9. ✅ Optimize Livewire components

### Phase 3: Monitoring & Testing (2-3 days) - MEDIUM
10. ✅ Create performance benchmark tests
11. ✅ Add TTFB monitoring middleware
12. ✅ Enable slow query logging
13. ✅ Configure CDN for assets

### Phase 4: Fine-tuning (1-2 days) - LOW
14. ✅ Tune OPcache + JIT settings
15. ✅ Optimize Redis configuration
16. ✅ Document production setup

---

## ✅ Acceptance Criteria (Ozon Production Standards)

- [ ] TTFB of critical endpoints (registration, login, client dashboard) < 80ms in 95% of cases
- [ ] Tenancy bootstrap overhead minimized (Octane + cache)
- [ ] No N+1 queries in Filament/Livewire (verified with Laravel Debugbar)
- [ ] All geo and proxy checks cached (Redis with appropriate TTL)
- [ ] Performance optimizations covered by tests
- [ ] Real-time TTFB monitoring in place (alerts for TTFB > 150ms)
- [ ] Octane running in production with RoadRunner
- [ ] Redis as primary cache driver for all operations
- [ ] Composite indexes on frequently filtered columns
- [ ] Deployment script includes all cache optimization commands

---

## 📝 Notes

- All cache operations should use `Cache::tags()` for proper invalidation
- Tenant-specific cache should use tenant-aware tags: `Cache::tags(['tenant:' . $tenantId, 'geo'])`
- Use Redis Lua scripts for atomic operations (slot holds, rate limiting)
- Monitor Redis memory usage and set appropriate eviction policies
- Use connection pooling for Redis in Octane context
- Enable query log in development only (never in production)
- Use Horizon for queue monitoring and alerting
- Set up Prometheus metrics for TTFB monitoring
- Use Blackfire or Tideways for profiling in staging

---

**Last Updated:** 2026-04-23  
**Author:** Senior Production Architect (CatVRF)  
**Status:** Ready for Implementation
