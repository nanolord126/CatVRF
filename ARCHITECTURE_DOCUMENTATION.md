# CatVRF - Multi-Tenant Marketplace Platform

## Architecture Documentation

**Status**: Production Ready ✅  
**Last Updated**: 15 March 2026  
**Framework**: Laravel 12 + Filament 3.2 + Stancl Tenancy

---

## 📋 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Project Structure](#project-structure)
3. [Authorization Framework](#authorization-framework)
4. [Core Services](#core-services)
5. [Database Design](#database-design)
6. [Testing](#testing)
7. [Deployment](#deployment)

---

## Architecture Overview

### Technology Stack

```
Frontend: Filament 3.2 (Admin Panel + Resources)
Backend: Laravel 12 with PHP 8.3
Database: PostgreSQL / MySQL
Queue: Redis (async jobs)
Cache: Redis
Search: Typesense (full-text) + OpenAI Embeddings (vector)
Auth: Laravel Sanctum (API) + Session (web)
Multi-tenancy: Stancl/Tenancy (schema-per-tenant)
```

### Core Principles

1. **Multi-Tenancy First**: Every model and query is tenant-scoped
2. **Authorization via Policies**: All actions protected by 68 policy classes
3. **Audit Trail Everything**: All mutations tracked with correlation IDs
4. **AI-Native**: Vector search, recommendations, and fraud detection built-in
5. **Production Quality**: Error handling, logging, caching on all services

---

## Project Structure

```
app/
├── Models/                              # 146 models
│   ├── BaseModel.php                   # ← All models extend this
│   ├── User.php
│   ├── Tenant.php
│   ├── Flower.php
│   ├── Concert.php
│   ├── Clinic.php
│   └── Tenants/                        # Schema-per-tenant models
│       ├── MarketplaceProduct.php
│       ├── Concert.php
│       ├── Clinic.php
│       └── ... (50+ models)
│
├── Policies/                            # 68 authorization policies
│   ├── BaseSecurityPolicy.php           # ← All policies extend this
│   ├── AchievementPolicy.php
│   ├── AlertPolicy.php
│   └── Marketplace/
│       ├── ConcertPolicy.php
│       ├── FlowerPolicy.php
│       ├── ClinicPolicy.php
│       └── ... (31 marketplace policies)
│
├── Services/                            # Business Logic
│   ├── GlobalAIBusinessForecastingService.php (140 lines)
│   ├── Common/
│   │   └── MarketplaceAISearchService.php (150 lines)
│   ├── AI/
│   │   └── RecommendationEngine.php (150 lines)
│   └── Automation/
│       ├── FraudDetectionService.php (120 lines)
│       └── FinancialAutomationService.php (130 lines)
│
├── Http/Controllers/Tenant/             # 300+ API Controllers
│   ├── ConcertController.php
│   ├── FlowerController.php
│   ├── ClinicController.php
│   └── ... (all verticals)
│
└── Filament/Tenant/Resources/           # 643 Filament Resources
    ├── Marketplace/
    │   ├── ConcertResource/
    │   │   ├── Pages/
    │   │   │   ├── ListConcerts.php
    │   │   │   ├── CreateConcert.php
    │   │   │   ├── EditConcert.php
    │   │   │   └── ShowConcert.php
    │   │   └── ConcertResource.php
    │   └── ... (31 marketplace resources)
    └── ... (other resources)

database/
├── migrations/                          # 66 migration files
│   ├── create_flowers_table.php
│   ├── create_concerts_table.php
│   └── ... (all model tables)
│
└── seeders/                             # 100+ seeder files
    ├── ConcertEnhancedSeeder.php       # ← Quality test data
    ├── FlowerSeeder.php
    └── ... (all verticals)

tests/
├── Feature/
│   ├── Authorization/
│   │   └── ConcertPolicyTest.php       # Policy authorization tests
│   └── Controllers/
│       └── ConcertControllerTest.php   # API endpoint tests
│
└── Unit/
    └── Services/
        ├── FraudDetectionServiceTest.php
        └── RecommendationEngineTest.php
```

---

## Authorization Framework

### BaseSecurityPolicy

All 68 policy classes extend `BaseSecurityPolicy` which provides:

```php
abstract class BaseSecurityPolicy
{
    protected function isFromThisTenant($model): bool
    {
        return $model->tenant_id === tenant('id');
    }
    
    protected function checkUserActive(User $user): bool
    {
        return $user->active === true;
    }
}
```

### Standard 7 Methods

Every policy implements:

```php
final class ConcertPolicy extends BaseSecurityPolicy
{
    public function viewAny(User $user): bool
    public function view(User $user, Concert $model): bool
    public function create(User $user): bool
    public function update(User $user, Concert $model): bool
    public function delete(User $user, Concert $model): bool
    public function restore(User $user, Concert $model): bool
    public function forceDelete(User $user, Concert $model): bool
}
```

### Role-Based Access Control

```
Admin       → Full access (all actions)
Manager     → CRUD operations
Viewer      → Read-only access
Operator    → Limited CRUD (depends on context)
```

### Multi-Tenant Isolation

Every policy check includes:

```php
if (!$this->isFromThisTenant($model)) {
    return false;  // ← Prevent cross-tenant access
}
```

---

## Core Services

### 1. GlobalAIBusinessForecastingService (140 lines)

**Purpose**: AI-powered business intelligence

```php
$service = app(GlobalAIBusinessForecastingService::class);

// Revenue predictions with confidence intervals
$forecast = $service->getGlobalForecast();

// AI-generated business recommendations
$recommendations = $service->getBusinessRecommendations();

// Cost optimization opportunities
$savings = $service->identifyCostOptimizations();

// Vertical-specific forecasts
$vertical = $service->getVerticalForecast('flowers');

// Heatmaps for geographical analysis
$heatmap = $service->getProfitHeatmapData();
```

### 2. MarketplaceAISearchService (150 lines)

**Purpose**: Hybrid AI search with vector + full-text

```php
$search = app(MarketplaceAISearchService::class);

// Vector + Full-text + Geo search
$results = $search->unifiedSearch('red flowers', [
    'category' => 'flowers',
    'in_stock' => true,
], ['lat' => 55.75, 'lng' => 37.62]);

// Quick text search
$quick = $search->quickSearch('roses', limit: 10);

// Advanced filtering with geo
$filtered = $search->filterSearch([
    'category' => 'flowers',
    'min_price' => 100,
    'max_price' => 500,
]);

// Auto-sync when model changes
$search->syncProductToIndex($flower);

// Popular searches trending
$trending = $search->getPopularSearches();
```

### 3. RecommendationEngine (150 lines)

**Purpose**: AI recommendations using collaborative + content-based filtering

```php
$engine = app(RecommendationEngine::class);

// Get personalized suggestions
$suggestions = $engine->getPersonalizedSuggestions($user, 'education');

// Content-based for products
$products = $engine->getProductRecommendations($user);

// Collaborative filtering for similar users
$similar = $engine->findSimilarUsers($user, limit: 5);

// Vector similarity calculation
$similarity = $engine->cosineSimilarity($vec1, $vec2);
```

### 4. FraudDetectionService (120 lines)

**Purpose**: Real-time fraud detection

```php
$fraud = app(FraudDetectionService::class);

// Analyze transaction for risk
$analysis = $fraud->analyzeTransaction([
    'id' => 'txn_123',
    'user_id' => 'user_456',
    'amount' => 50000,
    'location' => 'Moscow',
]);
// Returns: ['risk_score' => 0.65, 'status' => 'APPROVED', 'flags' => [...]]

// Detect anomalies in user behavior
$anomalies = $fraud->detectAnomalies();

// Block suspicious transactions
$fraud->blockSuspicious($transaction);

// Log security events for audit
$fraud->logSecurityEvent('FRAUD_DETECTED', $data);
```

### 5. FinancialAutomationService (130 lines)

**Purpose**: Financial automation (payroll, tax, reconciliation)

```php
$finance = app(FinancialAutomationService::class);

// Auto-process payroll from Wallet
$result = $finance->processPayroll($payrollRun);
// Returns: ['paid' => 50, 'failed' => 2, 'total_amount' => 125000]

// Auto-reconcile accounts
$reconciliation = $finance->reconcileAccounts($tenantId);

// Calculate taxes (VAT, income tax)
$taxes = $finance->calculateTaxes($tenantId, '2026-Q1');

// Generate financial reports
$report = $finance->generateReports($tenantId, '2026-03');
```

---

## Database Design

### Base Schema Elements

All tenant models include:

```sql
-- Tenant isolation
tenant_id VARCHAR(36) NOT NULL
FOREIGN KEY (tenant_id) REFERENCES tenants(id)

-- Audit trail
correlation_id UUID NOT NULL UNIQUE
created_at TIMESTAMP
updated_at TIMESTAMP
deleted_at TIMESTAMP (soft deletes)

-- Indexes for performance
INDEX idx_tenant_id
INDEX idx_status
INDEX idx_created_at
```

### Key Tables

```
concerts (flowers, clinics, restaurants, etc.)
├── id (UUID primary key)
├── tenant_id (multi-tenant)
├── name, description
├── date, time, location
├── price, capacity
├── status, rating
└── ... (vertical-specific fields)

users
├── id (UUID)
├── email, password (hashed)
├── active, roles
├── last_login_at
└── ... (user-specific fields)

audit_logs
├── id
├── tenant_id
├── user_id
├── action (VIEW, CREATE, UPDATE, DELETE)
├── description (JSON)
├── correlation_id (for tracing)
├── ip_address, user_agent
└── created_at
```

---

## Testing

### Test Structure

```
tests/
├── Feature/
│   ├── Authorization/ConcertPolicyTest.php
│   └── Controllers/ConcertControllerTest.php
└── Unit/
    └── Services/
        ├── FraudDetectionServiceTest.php
        └── RecommendationEngineTest.php
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test class
php artisan test tests/Feature/Authorization/ConcertPolicyTest.php

# Run tests in parallel
php artisan test --parallel
```

### Test Examples

#### Policy Authorization

```php
public function test_admin_can_view_any_concert(): void
{
    $this->assertTrue($admin->can('view', $concert));
}

public function test_user_cannot_update_concert_from_different_tenant(): void
{
    $this->assertFalse($user->can('update', $otherConcert));
}
```

#### Service Behavior

```php
public function test_high_amount_increases_risk_score(): void
{
    $result = $fraud->analyzeTransaction(['amount' => 200000]);
    $this->assertGreater($result['risk_score'], 0.2);
}
```

#### API Endpoints

```php
public function test_user_can_create_concert(): void
{
    $response = $this->postJson('/api/concerts', $data);
    $response->assertStatus(201);
}

public function test_unauthorized_user_cannot_access(): void
{
    $response = $this->actingAs(null)->getJson('/api/concerts');
    $response->assertStatus(401);
}
```

---

## Deployment

### Prerequisites

```bash
# PHP 8.3+
php -v

# Composer
composer --version

# PostgreSQL or MySQL
psql --version

# Redis (optional but recommended)
redis-cli --version
```

### Installation

```bash
# Clone repository
git clone <repo-url>
cd catvrf

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build frontend assets
npm install
npm run build

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=catvrf
DB_USERNAME=postgres
DB_PASSWORD=secret

# Tenancy
TENANCY_DB_HOST=127.0.0.1

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# AI/ML
OPENAI_API_KEY=sk-...

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
```

### Running the Application

```bash
# Development server
php artisan serve

# Queue worker
php artisan queue:work

# Schedule runner (every minute)
* * * * * cd /path && php artisan schedule:run
```

### Production Checklist

- ✅ Enable query caching
- ✅ Configure Redis for sessions
- ✅ Set up background queue processing
- ✅ Configure CORS properly
- ✅ Enable rate limiting
- ✅ Set up log rotation
- ✅ Configure error tracking (Sentry)
- ✅ Enable database backups
- ✅ Set up CDN for static assets
- ✅ Configure SSL/TLS certificates

---

## Performance Optimization

### Caching Strategy

```php
// Services implement caching
$cacheKey = "recommendations:{$userId}:education";
if (Cache::has($cacheKey)) {
    return Cache::get($cacheKey);
}
// ... compute recommendations
Cache::put($cacheKey, $data, 600); // 10 minutes
```

### Database Optimization

```sql
-- Indexes on frequently queried columns
INDEX idx_tenant_status ON concerts(tenant_id, status)
INDEX idx_created_at ON concerts(created_at DESC)

-- Fulltext index for search
FULLTEXT INDEX idx_search ON concerts(name, description)
```

### Query Optimization

```php
// Eager load relationships
Concert::with(['reviews', 'ratings'])
    ->forCurrentTenant()
    ->paginate(15);

// Use scopes for common filters
$active = Concert::active()->recent()->paginate();
```

---

## Security Best Practices

### Authentication

- ✅ Password hashing with bcrypt
- ✅ Rate limiting on login
- ✅ Two-factor authentication available
- ✅ Session timeout

### Authorization

- ✅ Policy-based authorization
- ✅ Multi-tenant isolation enforced
- ✅ Soft deletes for data protection
- ✅ Audit trail for all mutations

### Data Protection

- ✅ Input validation on all forms
- ✅ SQL injection prevention (parameterized queries)
- ✅ XSS protection (CSRF tokens)
- ✅ CORS configured

---

## Support & Documentation

### Key Files

- `PROJECT_COMPLETION_REPORT.md` - Implementation summary
- `PHASE_1_COMPLETION_REPORT.md` - Phase 1 details
- `ARCHITECTURE_FINAL_STATUS.md` - Architecture overview

### Contact

For questions or support, refer to the project documentation or contact the development team.

---

**Last Generated**: 15 March 2026  
**Status**: Production Ready ✅
