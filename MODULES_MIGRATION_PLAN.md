# Modules Migration Plan - Final Target Architecture

**Date:** 2026-04-30  
**Status:** Phase 1.2 - Define Final Target  
**Priority:** HIGH

---

## Executive Summary

**Current State:**
- 37 modules in `modules/` (Clean Architecture + DDD)
- 90+ domains in `app/Domains/` (Legacy flat architecture)
- Dual architecture causing code duplication and maintenance issues

**Target State:**
- Single source of truth: `modules/` only
- All verticals migrated to 9-layer Clean Architecture
- `app/Domains/` removed or archived
- Clear separation of concerns with proper DDD boundaries

---

## Final Target Structure

### modules/ Directory Structure

```
modules/
├── Core/                          # Shared core functionality
│   ├── Domain/                    # Shared domain primitives
│   ├── Application/               # Shared application services
│   └── Infrastructure/            # Shared infrastructure
│
├── Payment/                       # ✅ MIGRATED
├── Wallet/                        # ✅ MIGRATED
├── Auto/                          # ⚠️ PARTIAL
├── Fashion/                       # ⚠️ PARTIAL
├── BeautyMasters/                 # ✅ MIGRATED
├── BigData/                       # ✅ MIGRATED
├── CatCRM/                        # ✅ MIGRATED
├── Inventory/                     # ✅ MIGRATED
├── Restaurant/                    # ✅ MIGRATED
├── Taxi/                          # ⚠️ PARTIAL
│
├── Analytics/                     # ⚠️ PARTIAL
├── Bonuses/                       # ⚠️ PARTIAL
├── Cart/                          # ⚠️ PARTIAL
├── Commissions/                   # ⚠️ PARTIAL
├── Contraindications/             # ✅ MIGRATED
├── Dental/                        # ✅ MIGRATED
├── DemandForecast/                # ⚠️ PARTIAL
├── Fitness/                       # ✅ MIGRATED
├── Flowers/                       # ✅ MIGRATED
├── FraudDetection/                # ⚠️ PARTIAL
├── Geo/                           # ⚠️ PARTIAL
├── Hotels/                        # ✅ MIGRATED
├── Loyalty/                       # ✅ MIGRATED
├── Marketplace/                   # ⚠️ PARTIAL
├── Media/                         # ⚠️ PARTIAL
├── Promo/                         # ⚠️ PARTIAL
├── PromoCampaign/                 # ⚠️ PARTIAL
├── RealEstate/                    # 🔴 NOT MIGRATED
├── Recommendation/                # ⚠️ PARTIAL
├── Supermarket/                   # ✅ MIGRATED
├── VetGrooming/                   # ✅ MIGRATED
├── Veterinary/                    # ⚠️ PARTIAL
├── Video/                         # ⚠️ PARTIAL
├── Warehouse/                     # ✅ MIGRATED
│
├── AIConstructor/                 # ✅ MIGRATED
└── [Additional verticals as needed]
```

---

## 9-Layer Clean Architecture Template

Every module MUST follow this structure:

```
modules/{Vertical}/
├── Domain/                        # Layer 1: Business Logic
│   ├── Entities/                  # Domain entities (readonly)
│   ├── Enums/                     # Domain enums
│   ├── Exceptions/                # Domain exceptions
│   ├── Events/                    # Domain events
│   ├── Interfaces/                # Repository interfaces
│   ├── ValueObjects/              # Value objects (immutable)
│   ├── DTOs/                      # Data transfer objects (immutable)
│   └── Repositories/              # (empty - only interfaces)
│
├── Application/                   # Layer 2: Use Cases
│   ├── Services/                  # Application services
│   ├── Jobs/                      # Queue jobs
│   ├── Listeners/                 # Event listeners
│   ├── Policies/                  # Authorization policies
│   └── Commands/                  # Command handlers
│
├── Infrastructure/                # Layer 3: Technical Implementation
│   ├── Models/                    # Eloquent models
│   ├── Repositories/              # Repository implementations
│   ├── Providers/                 # Service providers
│   ├── Cache/                     # Cache implementations
│   ├── External/                  # External API clients
│   └── Database/                  # Migrations, seeders
│
├── Presentation/                  # Layer 4: HTTP/GraphQL/WebSocket
│   ├── Http/Controllers/          # Controllers
│   ├── Http/Requests/             # Form requests
│   ├── Http/Resources/            # API resources
│   ├── GraphQL/                   # GraphQL types
│   └── Routes/                    # Route definitions
│
├── Filament/                      # Layer 5: Admin Panel
│   ├── Resources/                 # Filament resources
│   ├── Pages/                     # Filament pages
│   └── Widgets/                   # Filament widgets
│
├── Tests/                         # Layer 6: Tests
│   ├── Unit/                      # Unit tests
│   ├── Feature/                   # Feature tests
│   └── Integration/               # Integration tests
│
├── Config/                        # Layer 7: Configuration
│   └── {vertical}.php             # Module config
│
├── README.md                      # Layer 8: Documentation
└── {Vertical}ServiceProvider.php  # Layer 9: Service Provider
```

---

## Migration Priority Matrix

### Priority 1: Critical Business Logic (Week 1-2)

These modules handle core business operations and have highest impact:

| Module | Current Status | Effort | Risk | Priority |
|--------|----------------|--------|------|----------|
| Payment | ⚠️ Partial | Medium | High | P0 |
| Wallet | ⚠️ Partial | Medium | High | P0 |
| Auto | ⚠️ Partial | High | Medium | P0 |
| Fashion | ⚠️ Partial | Medium | Medium | P0 |

**Action Items:**
1. Complete migration of Payment from app/Domains/Payment/
2. Complete migration of Wallet from app/Domains/Wallet/
3. Complete migration of Auto from app/Domains/Auto/
4. Complete migration of Fashion from app/Domains/Fashion/
5. Remove duplicate services from app/Services/
6. Update all references across codebase

### Priority 2: Active Verticals (Week 3-4)

Verticals with active development and high usage:

| Module | Current Status | Effort | Risk | Priority |
|--------|----------------|--------|------|----------|
| RealEstate | 🔴 Not Migrated | High | Medium | P1 |
| Taxi | ⚠️ Partial | Medium | Low | P1 |
| Travel | 🔴 Not Migrated | High | Medium | P1 |
| Education | 🔴 Not Migrated | Very High | High | P1 |

**Action Items:**
1. Migrate RealEstate from app/Domains/RealEstate/
2. Complete Taxi migration
3. Migrate Travel from app/Domains/Travel/
4. Migrate Education from app/Domains/Education/

### Priority 3: Supporting Services (Week 5-6)

Cross-cutting concerns and shared services:

| Module | Current Status | Effort | Risk | Priority |
|--------|----------------|--------|------|----------|
| Analytics | ⚠️ Partial | Medium | Low | P2 |
| Bonuses | ⚠️ Partial | Low | Low | P2 |
| Cart | ⚠️ Partial | Low | Low | P2 |
| Loyalty | ✅ Migrated | - | - | P2 (verify) |
| Marketplace | ⚠️ Partial | Medium | Low | P2 |

**Action Items:**
1. Complete Analytics migration
2. Complete Bonuses migration
3. Complete Cart migration
4. Verify Loyalty is fully migrated
5. Complete Marketplace migration

### Priority 4: Legacy Cleanup (Week 7-8)

Remaining domains with lower priority:

| Module | Current Status | Effort | Risk | Priority |
|--------|----------------|--------|------|----------|
| Medical | 🔴 Not Migrated | High | Low | P3 |
| Sports | 🔴 Not Migrated | Medium | Low | P3 |
| Leisure | 🔴 Not Migrated | High | Low | P3 |
| Construction | 🔴 Not Migrated | Medium | Low | P3 |

**Action Items:**
1. Migrate remaining domains as needed
2. Archive app/Domains/ to app/Domains/Archived/
3. Update composer.json autoload
4. Remove app/Domains/ from codebase

---

## Migration Strategy

### Step 1: Analysis (1 day per module)

For each module:
1. List all files in app/Domains/{Domain}/
2. Identify dependencies on other domains
3. Identify services, entities, repositories
4. Create migration checklist

### Step 2: Domain Layer Migration (2-3 days per module)

1. Create Domain/Entities/ with readonly classes
2. Create Domain/Enums/ for all enums
3. Create Domain/ValueObjects/ for VOs
4. Create Domain/Interfaces/ for repositories
5. Create Domain/Events/ for domain events
6. Create Domain/DTOs/ for DTOs

### Step 3: Application Layer Migration (2-3 days per module)

1. Create Application/Services/ with application services
2. Create Application/Jobs/ for async operations
3. Create Application/Listeners/ for event handling
4. Move business logic from legacy services
5. Ensure FraudControlService::check() is first action

### Step 4: Infrastructure Layer Migration (2-3 days per module)

1. Create Infrastructure/Models/ with Eloquent models
2. Create Infrastructure/Repositories/ with implementations
3. Create Infrastructure/Providers/ for DI
4. Create Database/Migrations/ for migrations
5. Ensure tenant_id is on all models

### Step 5: Presentation Layer Migration (1-2 days per module)

1. Create Presentation/Http/Controllers/
2. Create Presentation/Http/Requests/
3. Create Presentation/Http/Resources/
4. Create Presentation/Routes/
5. Register routes in RouteServiceProvider

### Step 6: Testing (1-2 days per module)

1. Create Tests/Unit/ for domain logic
2. Create Tests/Feature/ for API endpoints
3. Create Tests/Integration/ for cross-module tests
4. Ensure 95%+ coverage

### Step 7: Integration (1 day per module)

1. Update service providers
2. Update event listeners
3. Update all references across codebase
4. Run integration tests

### Step 8: Cleanup (1 day per module)

1. Remove app/Domains/{Domain}/
2. Remove duplicate services from app/Services/
3. Update composer.json autoload
4. Run composer dump-autoload
5. Verify all tests pass

---

## Validation Checklist

For each migrated module, verify:

- [ ] All entities are readonly classes
- [ ] All VOs are immutable
- [ ] All DTOs are immutable with fromJson/toArray
- [ ] Repository interfaces in Domain, implementations in Infrastructure
- [ ] Service provider binds interfaces to implementations
- [ ] FraudControlService::check() is first action in public methods
- [ ] DB::transaction() used for financial operations
- [ ] AuditService integrated via WithAuditLogging trait
- [ ] tenant_id on all models
- [ ] Cache uses tags for invalidation
- [ ] LLM calls are async via Jobs
- [ ] Tests have 95%+ coverage
- [ ] No references to app/Domains/{Domain}/ remain
- [ ] No duplicate services in app/Services/

---

## Risk Mitigation

### Technical Risks

**Risk:** Breaking changes during migration
**Mitigation:**
- Feature flags for gradual rollout
- Comprehensive integration tests
- Rollback plan for each module
- Blue-green deployment strategy

**Risk:** Dependency hell between modules
**Mitigation:**
- Use interfaces for all inter-module communication
- Event-driven architecture for loose coupling
- Dependency injection container
- Clear module boundaries

### Operational Risks

**Risk:** Downtime during migration
**Mitigation:**
- Migrate during low-traffic periods
- Database migrations with zero downtime
- Load testing before rollout
- Monitoring and alerting

**Risk:** Team productivity impact
**Mitigation:**
- Clear documentation and training
- Pair programming for complex migrations
- Regular sync meetings
- Incremental delivery

---

## Success Metrics

### Technical Metrics

- **Module Coverage:** 100% of active verticals in modules/
- **Code Duplication:** <5% (measured by duplicate code detection)
- **Test Coverage:** >95% for all migrated modules
- **PHPStan Errors:** 0 errors at level 5
- **Build Time:** <5 minutes for full test suite

### Business Metrics

- **Migration Velocity:** 1-2 modules per week
- **Bug Rate:** <2 bugs per module post-migration
- **Feature Delivery Time:** <20% increase during migration
- **Team Satisfaction:** >4/5 score

---

## Timeline

### Phase 1: Critical Services (Week 1-2)
- Payment, Wallet, Auto, Fashion
- Expected completion: 2026-05-14

### Phase 2: Active Verticals (Week 3-4)
- RealEstate, Taxi, Travel, Education
- Expected completion: 2026-05-28

### Phase 3: Supporting Services (Week 5-6)
- Analytics, Bonuses, Cart, Marketplace
- Expected completion: 2026-06-11

### Phase 4: Legacy Cleanup (Week 7-8)
- Remaining domains, archive app/Domains/
- Expected completion: 2026-06-25

---

## Next Steps

1. **Immediate (Today):**
   - Review and approve this plan
   - Create migration branches for Priority 1 modules
   - Set up tracking board

2. **This Week:**
   - Start Payment migration (Phase 1.3)
   - Complete Auto migration
   - Begin Wallet migration

3. **Next Week:**
   - Complete Wallet migration
   - Complete Fashion migration
   - Begin Priority 2 modules

---

**Document Owner:** CatVRF Architecture Team  
**Review Date:** 2026-05-07  
**Approval:** Pending
