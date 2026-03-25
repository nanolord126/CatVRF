# COPILOT CONFIGURATION FILES 2026 — MASTER INDEX

**Version:** 2.0  
**Status:** PRODUCTION MANDATORY  
**Date:** 25.03.2026

---

## 📚 ALL COPILOT CONFIGURATION FILES

This master index documents ALL Copilot configuration files created for the CatVRF project. Each file contains mission-critical rules and patterns.

---

## 🔗 FILE LOCATIONS AND PURPOSES

### 1. `.github/copilot-rules.md` (Primary Rules)
**Location:** `.github/copilot-rules.md`  
**Size:** 546 lines  
**Purpose:** Absolute prohibitions and mandatory requirements

**Contains:**
- ✅ 10 Absolute Prohibitions (no stubs, TODO, debug, Facades)
- ✅ 10 Mandatory Requirements (injection, transactions, logging, correlation_id, fraud-check)
- ✅ Validation checklist before commit
- ✅ Bash commands to verify code quality

**Key Rules:**
```
❌ FORBIDDEN:
- return null; throw new Exception("Not implemented");
- die(); dd(); dump(); var_dump();
- auth(); Cache::; Queue::; response();
- if (false) { ... };  // dead code
- TODO, FIXME, HACK comments
- Empty methods and properties
- Files < 60 lines (except migrations)

✅ MANDATORY:
- Constructor injection (readonly)
- DB::transaction() for all mutations
- FraudControlService::check() for critical ops
- Log::channel('audit') for all mutations
- correlation_id in every log
- Explicit error handling (try/catch)
- Concrete return types (Model, Collection, DTO)
- Tenant scoping on every query
```

**When to use:** Before writing ANY production code

---

### 2. `.github/copilot-vertical-architecture.md` (Architecture)
**Location:** `.github/copilot-vertical-architecture.md`  
**Size:** 1,247 lines  
**Purpose:** Comprehensive 9-layer vertical architecture

**Contains:**
- ✅ Complete 9-layer architecture diagram
- ✅ Detailed code examples for each layer:
  1. Models (data only, no logic)
  2. DTOs (data transfer, readonly)
  3. Events (dispatchable)
  4. Listeners (async handlers)
  5. Jobs (queueable tasks)
  6. Services (business logic)
  7. Policies (authorization)
  8. Enums (constants)
  9. Marketplace (public vitrine)

**For each layer:**
- Required properties and methods
- Code examples with full implementation
- Exception handling patterns
- Logging patterns
- Validation patterns

**Key Structure:**
```
app/Domains/{VerticalName}/
├── Models/           (Layer 1: Data)
├── DTOs/             (Layer 2: Transfer)
├── Events/           (Layer 3: Events)
├── Listeners/        (Layer 4: Async handlers)
├── Jobs/             (Layer 5: Queue jobs)
├── Services/         (Layer 6: Business logic)
├── Policies/         (Layer 7: Auth)
└── Enums/            (Layer 8: Constants)

modules/Marketplace/{VerticalName}/
└── ...               (Layer 9: Views & Livewire)
```

**When to use:** When creating/refactoring ANY domain vertical

---

### 3. `.github/copilot-cart-rules.md` (Cart System)
**Location:** `.github/copilot-cart-rules.md`  
**Size:** 862 lines  
**Purpose:** Complete cart system rules and implementation

**Contains:**
- ✅ Cart principles (1 seller = 1 cart, max 20 carts)
- ✅ 20-minute reservation system
- ✅ Price logic (higher on increase, unchanged on decrease)
- ✅ Unavailable item display (grayscale)
- ✅ Auto-cleanup job implementation
- ✅ Cart model and migrations
- ✅ CartService with all methods
- ✅ InventoryManagementService integration

**Key Rules:**
```
Rules:
1. One seller = One cart
2. Max 20 carts per user (enforced)
3. Items reserved for 20 minutes
4. Auto-cleanup of expired carts
5. Price: if UP → show new; if DOWN → keep old
6. Unavailable items: grayscale + no "Add to cart"
7. Open cart → verify current stock
8. Item count mismatch → reduce to available

Implementation:
- carts table (user, seller, mode, reserved_until)
- cart_items table (product, price_when_added)
- CartService (add, remove, reserve, release)
- CartCleanupJob (every minute)
```

**When to use:** When implementing marketplace checkout functionality

---

### 4. `.github/copilot-b2c-b2b-stacks.md` (B2C/B2B)
**Location:** `.github/copilot-b2c-b2b-stacks.md`  
**Size:** 721 lines  
**Purpose:** Complete B2C and B2B implementation specs

**Contains:**
- ✅ B2C mode rules (consumer mode)
- ✅ B2B mode rules (business mode)
- ✅ Mode detection logic
- ✅ Pricing differentiation
- ✅ Different commission structures
- ✅ Business credit and payment terms
- ✅ Comparison table (B2C vs B2B)
- ✅ Database migrations and models

**Key Differences:**
```
B2C (Consumer):
- Retail prices
- 1 cart per seller (max 20 total)
- 20-minute reservation
- Full prepayment required
- 14% commission
- No credit

B2B (Business):
- Wholesale prices (10-30% lower)
- Minimum order quantities
- Credit available (with limits)
- Payment terms (7-30 days)
- Different commission (8-12%)
- API access
- Business reports
- Bulk discounts

Determination:
$isB2B = $request->has('inn') && $request->has('business_card_id');
```

**When to use:** When handling marketplace pricing, payments, and user types

---

### 5. `.github/copilot-ai-constructors-ml.md` (AI & ML)
**Location:** `.github/copilot-ai-constructors-ml.md`  
**Size:** 1,089 lines  
**Purpose:** AI constructors, ML analysis, and personalization

**Contains:**
- ✅ AI Constructors for 5+ verticals:
  * Beauty (image analysis + AR try-on)
  * Furniture (room design + 3D visualization)
  * Food (recipe generator)
  * Fashion (style picker)
  * RealEstate (apartment designer)
- ✅ UserTasteProfile ML analysis (static)
- ✅ User address history (max 5)
- ✅ AI price calculators
- ✅ Integration with OpenAI Vision

**Key Features:**
```
AI Constructors:
1. Upload photo → AI analyzes → Recommendations
2. Save design to profile
3. Get products with pricing
4. 3D visualization / AR try-on

ML Analysis:
- Favorite categories (from purchase history)
- Price range (budget/mid/premium/luxury)
- Favorite brands (top 5)
- Favorite colors (for fashion)
- Favorite sizes (for fashion)
- Dietary preferences (for food)
- Style profile (for beauty/fashion)

Address Memory:
- Max 5 addresses (home, work, other)
- Auto-delete least used when > 5
- Usage count tracking
- Last used timestamp

Price Calculators:
- Furniture: repair cost estimation
- Food: menu cost by diet
- Beauty: service bundling
- RealEstate: renovation budgets
```

**When to use:** When building personalization features, AI-driven recommendations

---

## 📊 CONFIGURATION FILE STATISTICS

| File | Lines | Topics | Code Examples |
|------|-------|--------|---|
| copilot-rules.md | 546 | 10 rules + checklist | 20+ |
| copilot-vertical-architecture.md | 1,247 | 9 layers | 50+ |
| copilot-cart-rules.md | 862 | Cart system | 35+ |
| copilot-b2c-b2b-stacks.md | 721 | B2C/B2B | 40+ |
| copilot-ai-constructors-ml.md | 1,089 | AI/ML | 45+ |
| **TOTAL** | **4,465** | **Essential patterns** | **190+** |

---

## 🎯 HOW TO USE THESE FILES

### For Code Review:
```
1. Open `.github/copilot-rules.md`
2. Check 10 prohibitions against PR changes
3. Check 10 requirements against PR changes
4. Use checklist before approving
```

### For Creating New Vertical:
```
1. Open `.github/copilot-vertical-architecture.md`
2. Create 9 directories: Models, DTOs, Events, etc.
3. Copy code patterns from corresponding layer section
4. Fill in business logic
5. Verify against 9-layer checklist
```

### For Shopping Cart:
```
1. Open `.github/copilot-cart-rules.md`
2. Implement CartService methods
3. Create CartCleanupJob
4. Apply price logic (UP/DOWN rules)
5. Style unavailable items as grayscale
```

### For Multi-Tenant Payment:
```
1. Open `.github/copilot-b2c-b2b-stacks.md`
2. Determine mode: $isB2B = has('inn') && has('business_card_id')
3. Apply correct pricing (B2C 14% vs B2B 8-12%)
4. Set payment terms (B2C instant vs B2B 7-30 days)
5. Show correct vitrine (merged B2C vs separate B2B)
```

### For Personalization:
```
1. Open `.github/copilot-ai-constructors-ml.md`
2. Implement UserTasteAnalyzerService
3. Add AI Constructor for your vertical
4. Cache taste profile (update weekly)
5. Use for recommendations
```

---

## ✅ VALIDATION CHECKLIST

Before deploying ANY code:

```bash
# 1. Check against rules
grep -r "return null;" app/ modules/
grep -r "TODO\|FIXME\|HACK" app/ modules/
grep -r "die(\|dd(\|dump(" app/ modules/
grep -r "auth()\|Cache::\|Queue::" app/ modules/

# 2. Verify vertical structure
ls -la app/Domains/{VerticalName}/{Models,DTOs,Events,Listeners,Jobs,Services,Policies,Enums}

# 3. Validate cart implementation
grep -r "CartService" app/
grep -r "CartCleanupJob" app/

# 4. Check B2C/B2B logic
grep -r "isB2B\|mode.*B2C\|mode.*B2B" app/

# 5. Confirm AI/ML presence
grep -r "UserTasteAnalyzer\|BeautyImageConstructor\|RecommendationService" app/
```

---

## 📖 READING ORDER (Recommended)

If you're new to this project:

1. **First:** `copilot-rules.md` (understand prohibitions)
2. **Second:** `copilot-vertical-architecture.md` (understand structure)
3. **Third:** `copilot-cart-rules.md` (understand commerce)
4. **Fourth:** `copilot-b2c-b2b-stacks.md` (understand pricing)
5. **Fifth:** `copilot-ai-constructors-ml.md` (understand personalization)

---

## 🔄 UPDATING THESE FILES

If you discover issues or improvements:

1. Update the relevant `.md` file
2. Add examples if pattern is new
3. Update checklist if adding requirement
4. Document in git commit message
5. Notify team in Slack/Discord

Example:
```
git commit -m "docs: Update copilot-rules.md with new fraud-check pattern"
```

---

## 🚀 INTEGRATION WITH CI/CD

These files should be:
- ✅ Part of code review process (PR template references them)
- ✅ Used in linting (pre-commit hooks)
- ✅ Referenced in documentation
- ✅ Updated with every new pattern

---

## 📞 CONTACT & QUESTIONS

If questions arise about any configuration file:

1. Check the specific `.md` file
2. Search for relevant code examples
3. Run the validation checklist
4. Consult team lead if pattern is unclear

---

**Last Updated:** 25.03.2026  
**Version:** 2.0  
**Status:** PRODUCTION MANDATORY  
**Maintenance:** Review quarterly or when new patterns emerge

╔═══════════════════════════════════════════════════════════════════════════════╗
║  These configuration files are the SOURCE OF TRUTH for all code patterns     ║
║  Follow them religiously. Deviations require team consensus and documentation ║
╚═══════════════════════════════════════════════════════════════════════════════╝
