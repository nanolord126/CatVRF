# Canonical Services Reference

This document defines the canonical (official) versions of services to avoid duplication.

**Last Updated:** 2026-04-30  
**Status:** Migration in Progress

---

## PaymentService

**Canonical:** `modules/Payment/Application/Services/PaymentService.php`
- Part of the Payment module with proper 9-layer architecture
- Includes fraud checks, audit logging, domain events
- Smart routing, escrow, split payments, payouts

**Status:** ✅ MIGRATED - Canonical version exists

**Duplicates found (2026-04-30):**
- ❌ `app/Services/Payment/PaymentService.php` - NOT FOUND (already removed)
- ⚠️ `app/Domains/Shared/Payment/Application/Services/PaymentService.php` - EXISTS
- ⚠️ `app/Domains/Consulting/Finances/PaymentService.php` - EXISTS
- ⚠️ `app/Domains/Education/EducationMilestonePaymentService.php` - EXISTS
- ⚠️ `app/Domains/Shared/Education/EducationMilestonePaymentService.php` - EXISTS
- ⚠️ `app/Domains/Supermarket/SubscriptionPaymentService.php` - EXISTS
- ⚠️ `app/Domains/Payment/SplitPaymentService.php` - EXISTS
- ⚠️ `app/Domains/Payment/RecurringPaymentService.php` - EXISTS

**Action Required:** Remove or migrate vertical-specific payment services to use canonical PaymentService

---

## WalletService

**Canonical:** `modules/Wallet/Application/Services/WalletService.php`
- Part of the Wallet module with proper 9-layer architecture
- Includes fraud checks, audit logging, domain events
- Deposit, withdrawal, balance, transaction history

**Status:** ✅ MIGRATED - Canonical version exists

**Duplicates found (2026-04-30):**
- ⚠️ `app/Services/Wallet/WalletService.php` - EXISTS
- ⚠️ `app/Services/WalletService.php` - EXISTS
- ⚠️ `app/Domains/Wallet/Services/WalletService.php` - EXISTS (353 lines)
- ⚠️ `app/Domains/Wallet/Domain/Services/WalletService.php` - EXISTS

**Action Required:** Remove all duplicate WalletService implementations

---

## FraudControlService

**Canonical:** `app/Services/FraudControlService.php` (432 lines)
- Full implementation with hard rules + ML scoring
- Uses FraudMLService, RateLimiterService, FraudDataAnonymizer
- Logs to fraud_alert channel
- Throws FraudBlockedException for high-risk operations

**Status:** ✅ STABLE - No migration needed (stays in app/Services/)

**Potential Duplicates:** (to be verified)
- ⚠️ `app/Domains/FraudML/FraudControlService.php` - needs verification
- ⚠️ `app/Services/Fraud/FraudControlService.php` - needs verification
- ⚠️ `app/Services/Auth/FraudControlService.php` - needs verification
- ⚠️ `app/Services/Insurance/FraudControlService.php` - needs verification

**Action Required:** Verify and remove non-canonical FraudControlService copies

---

## Other Services with Potential Duplicates

### OrderService
**Found in:**
- modules/Restaurant/Application/Services/OrderService.php
- modules/Flowers/Application/Services/OrderService.php
- app/Domains/Veterinary/Services/OrderService.php
- app/Domains/Education/Services/OrderService.php
- app/Domains/Wallet/Services/OrderService.php

**Action:** These are vertical-specific and should stay separate

---

## Architecture Strategy

### modules/ vs app/Domains/

**Decision:** Keep `modules/` as the canonical structure for verticals.

**Reason:**
- `modules/` has proper 9-layer architecture (Domain, Application, Infrastructure, Presentation)
- `app/Domains/` is legacy structure with inconsistent architecture
- 90+ domains in `app/Domains/` duplicate functionality in `modules/`

**Migration Status (2026-04-30):**

**Fully Migrated to modules/:**
- ✅ Payment (modules/Payment/)
- ✅ Wallet (modules/Wallet/)
- ✅ BeautyMasters (modules/BeautyMasters/)
- ✅ BigData (modules/BigData/)
- ✅ CatCRM (modules/CatCRM/)
- ✅ Inventory (modules/Inventory/)

**Partially Migrated:**
- ⚠️ Auto (modules/Auto/ - 26/55 files)
- ⚠️ Fashion (modules/Fashion/ - 47/50 files)
- ⚠️ Restaurant (modules/Restaurant/ exists, app/Domains/Restaurant/ still exists)

**Not Migrated (Legacy in app/Domains/):**
- 🔴 Education (255 files)
- 🔴 Travel (301 files)
- 🔴 RealEstate (240 files)
- 🔴 Taxi (183 files)
- 🔴 And 80+ more domains

**Migration Plan:**
1. **Phase 1 (Critical):** Remove duplicate PaymentService and WalletService
2. **Phase 2 (Active Verticals):** Complete migration of Auto, Fashion, Restaurant
3. **Phase 3 (Cleanup):** Gradually migrate remaining domains or deprecate unused ones

---

### 9-Layer Architecture Compliance

**Modules requiring Presentation layer:**
- Payment, Fitness, BeautyMasters, Dental, Flowers, Inventory, Loyalty

**Modules requiring refactoring (Services/Models outside Application):**
- Analytics, Restaurant, Wallet, Supermarket, RealEstate, Hotels, Taxi, Fashion, Auto

**Modules with Repository implementations needed:**
- Inventory (interfaces exist, implementations missing)
- Payment (partial)
- Wallet (partial)
- Most other modules

---

## Immediate Actions Required

### Priority 1: Remove WalletService Duplicates
```bash
# Remove these files:
app/Services/Wallet/WalletService.php
app/Services/WalletService.php
app/Domains/Wallet/Services/WalletService.php
app/Domains/Wallet/Domain/Services/WalletService.php
```

### Priority 2: Remove/Consolidate PaymentService Duplicates
- Keep vertical-specific services (SubscriptionPaymentService, EducationMilestonePaymentService)
- Remove generic PaymentService duplicates
- Update references to use canonical modules/Payment/Application/Services/PaymentService.php

### Priority 3: Verify FraudControlService Duplicates
- Search for all FraudControlService files
- Verify which are duplicates vs vertical-specific implementations
- Remove true duplicates

---

## References

- [ARCHITECTURE.md](ARCHITECTURE.md) - Full architecture documentation
- [VERTICAL_REFACTORING_MIGRATION.md](VERTICAL_REFACTORING_MIGRATION.md) - Migration plan
- [MIGRATION_REPORT.md](MIGRATION_REPORT.md) - Migration status
