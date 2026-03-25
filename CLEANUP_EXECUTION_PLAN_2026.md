╔════════════════════════════════════════════════════════════════════════════╗
║          COMPREHENSIVE CLEANUP & REFACTOR PLAN 2026 v11.0                  ║
║                    PHASED APPROACH WITH TIMELINE                           ║
╚════════════════════════════════════════════════════════════════════════════╝

## 📊 CURRENT PROJECT STATE (After Auto-Fix Phase 1)

Total Files Scanned: ~3,000 PHP files
Total Violations Found: 3,453
Violations Fixed (Phase 1): 501 (CRLF conversion)
Remaining Violations: 2,952

---

## 🎯 PHASED EXECUTION PLAN

### PHASE 1: COMPLETED ✅
- [x] Auto-fix CRLF line endings (501 files)
- [x] Create Copilot configuration files:
  - [x] .github/copilot-rules.md (запреты и требования)
  - [x] .github/copilot-vertical-architecture.md (9-слойная архитектура)
  - [x] .github/copilot-cart-rules.md (правила корзин)
  - [x] .github/copilot-b2c-b2b-stacks.md (B2C/B2B режимы)
  - [x] .github/copilot-ai-constructors-ml.md (AI и ML)

**Status:** ✅ COMPLETE

---

### PHASE 2: AUTOMATED FIXES (3–5 часов)
**Задачи:** Исправить простые нарушения автоматически

#### 2.1: Fix Facades and Static Calls (1,185 violations)
```bash
# Стратегия:
1. Найти все вызовы: auth(), Cache::, Queue::, response()
2. Заменить на DI в конструкторе
3. Генерировать шаблоны из исходных классов
```

**Script:** fix_facades.php
**Expected Time:** 1 hour
**Expected Fixes:** ~1,100 files

#### 2.2: Fix Short Files (1,619 violations)
```bash
# Стратегия:
1. Анализ каждого файла < 60 строк
2. Расширить пустой файл реальной логикой ИЛИ удалить
3. Объединить связанные файлы
```

**Script:** expand_short_files.php
**Expected Time:** 2 hours
**Expected Fixes:** ~1,000 files (удалено/расширено)

#### 2.3: Fix Null Returns and Empty Methods (26 violations)
```bash
# Стратегия:
1. Заменить return null на throw new Exception
2. Удалить пустые методы
3. Реализовать простые методы (getters, helpers)
```

**Script:** fix_null_returns.php
**Expected Time:** 1 hour
**Expected Fixes:** ~26 files

#### 2.4: Fix BOM and Encoding (if any remain)
```bash
# Стратегия:
1. Удалить BOM из всех файлов
2. Убедиться UTF-8 кодировка везде
```

**Script:** fix_encoding.php
**Expected Time:** 30 min
**Expected Fixes:** Remaining BOM files

---

### PHASE 3: MANUAL CRITICAL FIXES (8–12 часов)
**Задачи:** Исправить критические нарушения, требующие анализа

#### 3.1: Add Missing Correlation IDs (18 violations)
```bash
# Files affected:
- Controllers: Add correlation_id to all mutations
- Services: Pass correlation_id through methods
- Jobs: Add correlation_id to constructor
```

**Expected Time:** 2 hours
**Strategy:** Script-assisted with manual review

#### 3.2: Add Missing Tenant Scoping
```bash
# Models without booted() and TenantScope:
- Add to all Domain Models
- Verify global scope correctness
```

**Expected Time:** 2 hours

#### 3.3: Add Missing Fraud-Check (18 violations)
```bash
# Services with DB::transaction but no fraud check:
- Add FraudControlService::check() call
- Validate operation type and DTO
```

**Expected Time:** 2 hours

#### 3.4: Add Missing Audit-Log (56 violations)
```bash
# Mutations without Log::channel('audit'):
- Add audit logging to all DB writes
- Include correlation_id and trace
```

**Expected Time:** 2 hours

#### 3.5: Fix TODO/FIXME Comments (9 violations)
```bash
# Handle 9 files with TODO/FIXME:
- Either implement feature OR create GitHub issue
- Remove comment
```

**Expected Time:** 1 hour

---

### PHASE 4: STRUCTURAL IMPROVEMENTS (5–8 часов)
**Задачи:** Улучшить архитектуру и структуру проекта

#### 4.1: Ensure Vertical Architecture Compliance
```bash
# For each vertical domain:
- [ ] Layer 1: Models (with uuid, tenant_id, correlation_id)
- [ ] Layer 2: DTOs (readonly, immutable)
- [ ] Layer 3: Events (Dispatchable, SerializesModels)
- [ ] Layer 4: Listeners (try/catch, audit log)
- [ ] Layer 5: Jobs (Queueable, retryUntil, tags)
- [ ] Layer 6: Services (constructor injection, transactions)
- [ ] Layer 7: Policies (authorization)
- [ ] Layer 8: Enums (string/int backed)
- [ ] Layer 9: Marketplace (views, Livewire)
```

**Expected Time:** 4–5 hours

#### 4.2: Implement Cart System (if not present)
```php
# Required:
- carts table (user, seller, mode, reserved_until)
- cart_items table (product, quantity, price_when_added)
- CartService (add, remove, reserve, release)
- CartCleanupJob (auto-cleanup expired carts)
```

**Expected Time:** 1–2 hours

#### 4.3: Implement B2C/B2B System (if not present)
```php
# Required:
- business_groups table
- Mode detection logic
- Pricing differentiation
- Separate B2B vitrine
```

**Expected Time:** 1–2 hours

---

### PHASE 5: AI & ML IMPLEMENTATION (10–15 часов)
**Задачи:** Добавить AI-конструкторы и ML-анализ

#### 5.1: User Taste Profile Analysis
```php
# Implementation:
- user_taste_profiles table
- UserTasteAnalyzerService
- Periodic analysis job
```

**Expected Time:** 2 hours

#### 5.2: User Address History
```php
# Implementation:
- user_addresses table (max 5)
- UserAddressService
```

**Expected Time:** 1 hour

#### 5.3: AI Constructors for Core Verticals
```php
# Priority verticals:
1. Beauty (image analysis + AR)
2. Furniture (room design + 3D)
3. Food (recipe generator)
4. Fashion (style picker)
5. RealEstate (design planner)

# For each:
- AI service class
- Integration with OpenAI Vision
- Recommendations service
```

**Expected Time:** 6–10 hours

#### 5.4: AI Price Calculators
```php
# Implementation:
- Furniture repair cost calculator
- Food menu cost calculator
- Beauty service bundler
- RealEstate renovation estimator
```

**Expected Time:** 2 hours

---

## 📈 EXECUTION TIMELINE

| Phase | Description | Time | Dependencies |
|-------|-------------|------|--------------|
| **1** | Config files + auto-fix | 2 h | None |
| **2** | Automated fixes (facades, short files) | 3–5 h | Phase 1 |
| **3** | Manual critical fixes | 8–12 h | Phase 2 |
| **4** | Structural improvements | 5–8 h | Phase 2, 3 |
| **5** | AI & ML | 10–15 h | Phase 3, 4 |
| **TOTAL** | Full cleanup & refactor | **28–42 hours** | - |

---

## 💻 AUTOMATION SCRIPTS NEEDED

### Create these files for automation:

1. **fix_facades.php** (1 h)
   - Find: auth(), Cache::, Queue::, response(), Auth::, Response::
   - Replace: Constructor injection
   - Output: List of changes

2. **expand_short_files.php** (2 h)
   - Find: Files < 60 lines (except migrations, configs)
   - Action: Expand with logic OR mark for deletion
   - Output: Report of actions

3. **fix_null_returns.php** (1 h)
   - Find: return null; return [];
   - Replace: throw new DomainException()
   - Output: Changed files

4. **add_correlation_ids.php** (2 h)
   - Find: DB::transaction without correlation_id
   - Add: correlation_id parameter
   - Output: Changed files

5. **add_fraud_checks.php** (2 h)
   - Find: Services with DB::transaction
   - Add: FraudControlService::check()
   - Output: Changed files

6. **add_audit_logs.php** (2 h)
   - Find: Mutations without Log::channel('audit')
   - Add: Comprehensive audit logging
   - Output: Changed files

---

## ✅ FINAL VALIDATION CHECKLIST

After all phases complete:

- [ ] 0 return null in production code
- [ ] 0 TODO/FIXME/HACK comments
- [ ] 0 die()/dd()/dump()/var_dump() calls
- [ ] 0 static Facade calls (auth(), Cache::, etc.)
- [ ] 0 files < 60 lines (except migrations, configs)
- [ ] All mutable operations in DB::transaction()
- [ ] All mutations with FraudControlService::check()
- [ ] All mutations with Log::channel('audit')
- [ ] All mutations with correlation_id
- [ ] All models with tenant_id scoping
- [ ] All services with constructor injection
- [ ] All models with uuid, tenant_id, correlation_id, tags
- [ ] Cart system fully implemented (20 carts, 20 min reserve)
- [ ] B2C/B2B modes fully implemented
- [ ] AI constructors for 5+ verticals
- [ ] ML taste profile analysis implemented
- [ ] Address history system (max 5 addresses)
- [ ] 100% UTF-8 encoding, CRLF line endings

---

## 📊 EXPECTED FINAL METRICS

```
Before Cleanup:
- Total PHP Files: ~3,000
- Total Violations: 3,453
- Production Readiness: 15–20%
- Critical Issues: CRITICAL

After Cleanup (Phase 5 Complete):
- Total PHP Files: ~2,400 (600 deleted/merged)
- Total Violations: 0 (or < 5 ignorable)
- Production Readiness: 95–98%
- Critical Issues: NONE

Time Investment: 28–42 hours
Team Size: 1–2 senior developers
Git Commits: ~50–100
LOC Changes: ~200k–300k
```

---

## 🚀 NEXT STEPS

1. **Run Phase 1** (Already done ✅)
   - Config files created
   - CRLF fixes applied

2. **Create Phase 2 Scripts**
   - fix_facades.php
   - expand_short_files.php
   - fix_null_returns.php

3. **Run Phase 2** (Automated, 3–5 hours)
   - Execute scripts
   - Review output
   - Commit changes

4. **Run Phase 3** (Manual, 8–12 hours)
   - Add correlation_ids
   - Add fraud checks
   - Add audit logs
   - Add tenant scoping
   - Fix TODOs

5. **Run Phase 4** (Structural, 5–8 hours)
   - Verify vertical architecture
   - Implement cart system
   - Implement B2C/B2B

6. **Run Phase 5** (AI/ML, 10–15 hours)
   - Add taste profile
   - Add address history
   - Add AI constructors
   - Add calculators

7. **Final Validation**
   - Run audit_violations.php
   - Verify all 18 checklist items
   - Create final report

---

**Status:** Phase 1 Complete ✅  
**Ready for:** Phase 2 (Automated Fixes)  
**Estimated Total Duration:** 28–42 hours  
**Target Completion Date:** ~3–5 days (continuous work)  
**Production Readiness Target:** 95–98%

╔════════════════════════════════════════════════════════════════════════════╗
║                    READY FOR NEXT PHASE EXECUTION                          ║
╚════════════════════════════════════════════════════════════════════════════╝
