# Vertical Refactoring Migration Guide

**Date:** 2026-04-25
**Status:** In Progress
**Target:** 16 Super-Vertical Architecture (Pharmacy extracted as separate)

---

## Overview

Migration from flat vertical structure (50+ verticals) to 15 super-verticals with nested sub-verticals.

**Key Changes:**
- 16 product-focused super-verticals (Pharmacy extracted as separate)
- Technical domains separated (Delivery, GeoLogistics, Cart, Inventory, etc.)
- Medical/Veterinary excluded (compliance requirements)
- Pharmacy separated with medical compliance (152-ФЗ, ФЗ-323, Licensing)
- Common domain for shared product logic
- Sub-verticals as nested domains within super-verticals

---

## New Architecture

### Super-Verticals (16)

1. **Supermarket & Grocery** - MeatShops, FarmDirect, VeganProducts, Confectionery, GroceryAndDelivery, Food, OfficeCatering
2. **Beauty & Personal Care** - Beauty
3. **Pharmacy** - Pharmacy (отдельная вертикаль - медицинский compliance)
4. **Fashion & Luxury** - Fashion, Luxury, Jewelry/Collectibles
5. **Home & Living** - Furniture, HouseholdGoods, Gardening, HomeServices, CleaningServices
6. **Electronics & Gadgets** - Electronics, Photography, MusicAndInstruments
7. **Auto & Mobility** - Auto, CarRental, Taxi
8. **Health & Sports** - Fitness, SportsNutrition, Sports
9. **Food Services & Catering** - Restaurants, Catering
10. **Travel & Hospitality** - Hotels, ShortTermRentals, Travel
11. **Events & Entertainment** - EventPlanning, Tickets, PartySupplies, WeddingPlanning, ToysAndGames, HobbyAndCraft
12. **Real Estate & Property** - RealEstate, ConstructionAndRepair
13. **Services & Freelance** - Freelance, Consulting
14. **Art & Collectibles** - Art, Collectibles
15. **Flowers & Gifts** - Flowers
16. **Education & Development** - Education, PersonalDevelopment

### Technical Domains (Separated)

Delivery, GeoLogistics, Cart, Inventory, Payment, Wallet, FraudDetection, Analytics, Recommendation, AIConstructor, Communication, Notifications, Audit, Security, Compliance, Marketplace, B2B, CRM, Loyalty, Bonuses, Commissions, DemandForecast, PromoCampaigns, Media, Video, Geo, Search, Realtime, Webhooks, UserProfile, Staff, HR, Finances, Payout, Referral, ML, BigData, Advertising

### Excluded Verticals

Medical, Veterinary, Dental, Pet, VetGrooming (compliance requirements - separate domain)

---

## Directory Structure

```
app/Domains/
├── Supermarket/
│   ├── SubVerticals/
│   │   ├── Meat/
│   │   ├── Vegan/
│   │   └── ...
│   ├── Models/
│   ├── Services/
│   └── AI/SupermarketConstructorService.php
├── BeautyAndPersonalCare/
├── FashionAndLuxury/
├── HomeAndLiving/
├── ElectronicsAndGadgets/
├── AutoAndMobility/
├── HealthAndSports/
├── FoodServicesAndCatering/
├── TravelAndHospitality/
├── EventsAndEntertainment/
├── RealEstateAndProperty/
├── ServicesAndFreelance/
├── ArtAndCollectibles/
├── FlowersAndGifts/
├── EducationAndDevelopment/
└── Common/ (shared product logic)
```

---

## Migration Strategy
ompled
### Phase 1: Infrastructure (Current)
- [x] Create `config/verticals_v2.php` with new structure
- [x] Create `app/Domains/` directory structure
- [x] Create Common domain placeholder
- [ ] Create base classes in Common domain
- [ ] Update service providers

### Phase 2: Active Super-Verticals (Queue 2)
Migrate currently active verticals first:

**Priority Order:**
1. **Auto & Mobility** (Auto, Taxi, CarRental) - modules exist
2. **Pharmacy** - module exists (full implementation)
3. **Beauty & Personal Care** (BeautyMasters) - module exists
4. **Leisure & Entertainment** - new vertical (tickets-based)
5. **Fashion & Luxury** (Fashion) - module exists
6. **Health & Sports** (Fitness) - module exists
7. **Flowers & Gifts** (Flowers) - module exists
8. **Hotels** (Hotels) - module exists
9. **Real Estate** (RealEstate) - module exists
10. **Supermarket** (Confectionery, GroceryAndDelivery, Food) - partial modules exist
11. **Home & Living** (Furniture, HomeServices, CleaningServices) - partial modules exist
12. **Food Services & Catering** (Restaurant) - module exists

### Phase 3: Inactive Super-Verticals (Queue 3)
Migrate remaining sub-verticals when activated.

### Phase 4: Technical Domains
Ensure technical domains remain separate and accessible as services.

---

## Configuration Changes

### New Config File
`config/verticals_v2.php` replaces `config/verticals.php`

**Key Differences:**
- Hierarchical structure (super-verticals → sub-verticals)
- Technical domains separated
- Excluded verticals documented
- `uses_common` array for DRY compliance

### Backward Compatibility
Keep `config/verticals.php` during migration period. Update gradually:

```php
// In services, use new config:
$config = config('verticals_v2.super_verticals.supermarket');
```

---

## Module Migration Pattern

### From modules/ to app/Domains/

**Example: Auto Module**

**Before:**
```
modules/Auto/
├── Application/
├── Domain/
├── Database/
└── Infrastructure/
```

**After:**
```
app/Domains/AutoAndMobility/
├── SubVerticals/
│   ├── Auto/
│   │   ├── Application/
│   │   ├── Domain/
│   │   └── Infrastructure/
│   ├── CarRental/
│   └── Taxi/
├── Services/
│   └── AutoAndMobilityService.php
└── AI/AutoConstructorService.php
```

**Migration Steps:**
1. Create super-vertical service (orchestration)
2. Move existing module to sub-vertical
3. Update namespace references
4. Update config references
5. Update routes
6. Update Filament resources
7. Run tests
8. Remove old module directory

---

## Service Provider Updates

### New Provider Structure

```php
// app/Providers/DomainServiceProvider.php

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register super-vertical services
        $this->app->singleton(SupermarketService::class);
        $this->app->singleton(BeautyAndPersonalCareService::class);
        // ... all 15 super-verticals
        
        // Register technical domain services
        $this->app->singleton(DeliveryService::class);
        $this->app->singleton(CartService::class);
        // ... all technical domains
    }
}
```

---

## Database Migration

### No Schema Changes Required
The refactoring is architectural only. Existing tables remain unchanged.

### Model Namespace Updates
Update model namespaces from `Modules\VerticalName\Models\` to `App\Domains\SuperVertical\SubVerticals\Infrastructure\Models\`

---

## API Changes

### Route Updates
Update route files to use new service names:

```php
// Before
Route::apiResource('auto', Modules\Auto\Controllers\AutoController::class);

// After
Route::apiResource('auto', App\Domains\AutoAndMobility\SubVerticals\Auto\Controllers\AutoController::class);
```

### Response Structure
No changes to API response structure.

---

## Testing Strategy

### Unit Tests
- Test each super-vertical service independently
- Test sub-vertical services
- Test Common domain services

### Integration Tests
- Test super-vertical orchestration
- Test technical domain integration
- Test cross-vertical interactions

### E2E Tests
- Update existing E2E tests to use new structure
- Add tests for new super-vertical workflows

---

## Rollback Plan

### If Migration Fails
1. Revert `config/verticals.php` from git
2. Delete `app/Domains/` directory
3. Restore `modules/` from backup
4. Revert service provider changes
5. Clear cache: `php artisan config:clear`

### Rollback Command
```bash
git checkout HEAD -- config/verticals.php
rm -rf app/Domains/
git checkout HEAD -- modules/
php artisan config:clear
php artisan route:clear
```

---

## Timeline Estimate

- **Phase 1:** 1-2 days (infrastructure)
- **Phase 2:** 5-7 days (active verticals)
- **Phase 3:** Ongoing (as verticals activated)
- **Phase 4:** 1-2 days (technical domains verification)
- **Testing:** 2-3 days

**Total:** 9-14 days for full migration

---

## Success Criteria

- [ ] All 15 super-vertical directories created
- [ ] Common domain with base classes implemented
- [ ] All active verticals migrated to new structure
- [ ] Config updated to use `verticals_v2.php`
- [ ] All tests passing
- [ ] No breaking changes to API
- [ ] Documentation updated
- [ ] Team trained on new structure

---

## Open Questions

1. **Tenant Structure:** How should tenants map to super-verticals? One tenant = one super-vertical with multiple sub-verticals?
2. **AI Constructor:** Should AI constructors be per super-vertical or per sub-vertical?
3. **Filament Resources:** Update Filament resources to reflect super-vertical hierarchy?
4. **Legacy Support:** How long to keep `modules/` directory during transition?

---

## Next Steps

1. Review this migration plan
2. Approve super-vertical hierarchy
3. Begin Phase 1 implementation
4. Set up feature branch for migration
5. Create backup of current state
