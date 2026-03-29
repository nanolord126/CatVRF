# ETAP 1 MIDDLEWARE REFACTOR - EXECUTION INSTRUCTIONS

## 📋 Overview

ETAP 1 исправляет критическую архитектурную ошибку: middleware логика была дублирована в контроллерах вместо того, чтобы быть в отдельных middleware классах.

**Current Status**: 70% Complete  
**Phase**: Middleware refactoring in progress  
**Project**: CatVRF (Laravel 11)

---

## ✅ What's Already Done

### Part 1: Middleware Classes - ✅ COMPLETE & ENHANCED

5 core middleware classes have been created, verified, and enhanced with production-ready code:

1. **CorrelationIdMiddleware** (v2026.03.28)
   - Generates/validates correlation_id
   - Location: `app/Http/Middleware/CorrelationIdMiddleware.php`
   - Status: ✅ Enhanced with audit logging

2. **B2CB2BMiddleware** (v2026.03.28)
   - Determines B2C (consumer) vs B2B (business) mode
   - Location: `app/Http/Middleware/B2CB2BMiddleware.php`
   - Status: ✅ Enhanced with request attribute handling

3. **FraudCheckMiddleware** (v2026.03.28)
   - ML-based fraud detection
   - Location: `app/Http/Middleware/FraudCheckMiddleware.php`
   - Status: ✅ Enhanced with result storage

4. **RateLimitingMiddleware** (v2026.03.28)
   - Tenant-aware rate limiting
   - Location: `app/Http/Middleware/RateLimitingMiddleware.php`
   - Status: ✅ Enhanced with proper headers

5. **AgeVerificationMiddleware** (v2026.03.28)
   - Age restrictions for sensitive verticals
   - Location: `app/Http/Middleware/AgeVerificationMiddleware.php`
   - Status: ✅ Enhanced with trace logging

### Part 2: BaseApiController - ✅ VERIFIED

- **Status**: Already production-ready (NO CHANGES NEEDED)
- **Contains**: Only 8 helper methods (no middleware logic)
- **Location**: `app/Http/Controllers/Api/BaseApiController.php`

### Part 3: Kernel.php - ✅ VERIFIED

- **Status**: All 5 middleware aliases already registered
- **Location**: `app/Http/Kernel.php`
- **Aliases**: All configured correctly

---

## 🚀 How to Complete ETAP 1

### Step 1: Verify Current Architecture

```bash
cd c:\opt\kotvrf\CatVRF
php middleware_architecture_verification.php
```

**What it checks**:
- All 5 middleware classes exist
- BaseApiController is clean
- Kernel.php has correct registrations
- Controllers for duplicate patterns
- Routes for middleware order

**Output**: `MIDDLEWARE_VERIFICATION_REPORT.json`

### Step 2: Run Diagnostics (Optional but Recommended)

```bash
php audit_middleware_refactor.php
php middleware_cleanup_analysis.php
```

**What they do**:
- Analyze middleware implementations
- Identify duplicate patterns in controllers
- Generate analysis reports

### Step 3: Clean Controllers

```bash
php full_controller_refactor.php
```

**What it does**:
- Scans all 40+ controllers
- Removes duplicate middleware logic (SAFE patterns only)
- Removes unnecessary service injections
- Saves results to `MIDDLEWARE_REFACTOR_COMPLETE.json`

**Expected Results**:
- ~200+ lines of duplicate code removed
- ~40 controllers processed
- Controller file sizes reduced by 30-40%

### Step 4: Update Routes Files

Manually update the following files to include correct middleware order:

#### File: `routes/api.php`

Find this section:
```php
Route::middleware(['correlation-id', 'enrich-context'])
    ->group(function () {
        // routes
    });
```

Replace with:
```php
Route::middleware([
    'correlation-id',
    'auth:sanctum',
    'tenant',
    'b2c-b2b',
    'rate-limit',
    'fraud-check',
    'age-verify',
])->group(function () {
    // routes
});
```

**Repeat for**:
- `routes/api-v1.php`
- `routes/api-v2.php` (if exists)
- All `routes/[vertical].api.php` files

**Order is CRITICAL**:
1. correlation-id (generate correlation_id)
2. auth:sanctum (authenticate user)
3. tenant (tenant scoping)
4. b2c-b2b (determine mode)
5. rate-limit (rate limiting)
6. fraud-check (fraud detection)
7. age-verify (age restrictions)

### Step 5: Generate Final Report

```bash
php generate_final_report.php
```

**What it produces**:
- Comprehensive final report: `ETAP1_MIDDLEWARE_REFACTOR_FINAL_REPORT.json`
- Before/after metrics
- Testing checklist
- Files updated documentation

### Step 6: Test

#### Test Correlation ID

```bash
curl -X GET http://localhost:8000/api/health \
  -H "X-Correlation-ID: 123e4567-e89b-12d3-a456-426614174000"
```

Response should include: `X-Correlation-ID` header

#### Test Rate Limiting

```bash
for i in {1..35}; do
  curl -X GET http://localhost:8000/api/v1/promo/list \
    -H "Authorization: Bearer YOUR_TOKEN"
done
```

Should get 429 (Too Many Requests) on request #35

#### Test Fraud Detection

```bash
curl -X POST http://localhost:8000/api/payments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 9999999}'
```

Should be blocked (403 Forbidden) if fraud score > threshold

#### Test Age Verification

```bash
curl -X POST http://localhost:8000/api/pharmacy/orders \
  -H "Authorization: Bearer YOUR_TOKEN_FOR_YOUNG_USER" \
  -d '{...}'
```

Should be blocked (403 Forbidden) if user age < 18

---

## 📁 Files Generated/Modified

### Generated Files

✅ **Documentation**:
- `ETAP1_MIDDLEWARE_REFACTOR_GUIDE.md` - Complete usage guide
- `ETAP1_COMPLETION_STATUS.md` - Detailed status
- `README_ETAP1_INSTRUCTIONS.md` - This file

✅ **Scripts**:
- `middleware_architecture_verification.php` - Verification script
- `audit_middleware_refactor.php` - Diagnostic analysis
- `middleware_cleanup_analysis.php` - Pattern detection  
- `full_controller_refactor.php` - Main cleanup tool
- `generate_final_report.php` - Final reporting
- `etap1_completion_executor.php` - Completion coordinator

✅ **Reports** (Generated by scripts):
- `MIDDLEWARE_VERIFICATION_REPORT.json` - Architecture verification
- `MIDDLEWARE_REFACTOR_COMPLETE.json` - Cleanup results
- `ETAP1_MIDDLEWARE_REFACTOR_FINAL_REPORT.json` - Final comprehensive report

### Modified Files (Already Done ✅)

✅ **Middleware Classes** (Enhanced):
- `app/Http/Middleware/CorrelationIdMiddleware.php`
- `app/Http/Middleware/B2CB2BMiddleware.php`
- `app/Http/Middleware/FraudCheckMiddleware.php`
- `app/Http/Middleware/RateLimitingMiddleware.php`
- `app/Http/Middleware/AgeVerificationMiddleware.php`

### To Be Modified (Next Steps ⏳)

⏳ **Route Files** (Need middleware order update):
- `routes/api.php`
- `routes/api-v1.php`
- `routes/api-v2.php` (if exists)
- `routes/[vertical].api.php` (all 48 vertical routes)

⏳ **Controllers** (Need cleanup via script):
- ~40 controllers in `app/Http/Controllers/Api/`
- Will be processed by `full_controller_refactor.php`

---

## 📊 Success Metrics

When ETAP 1 is fully complete:

| Metric | Expected | Current |
|--------|----------|---------|
| Middleware Classes | 5 | ✅ 5 |
| Middleware Registered | 5 | ✅ 5 |
| BaseApiController Logic | Only helpers | ✅ Verified |
| Controller Duplicates | 0 | ⏳ 40 (cleanup needed) |
| Code Lines Removed | 200+ | ⏳ Pending cleanup |
| Routes with Middleware | All | ⏳ Partial |
| Tests Passing | 100% | ⏳ In progress |

---

## 🔧 Troubleshooting

### Issue: "Middleware not found"

**Solution**:
1. Verify middleware file exists: `app/Http/Middleware/[Name].php`
2. Verify Kernel.php has correct alias registration
3. Run `middleware_architecture_verification.php`
4. Check for typos in middleware names

### Issue: "Rate limiting not working"

**Solution**:
1. Verify RateLimitingMiddleware is in middleware pipeline
2. Check Kernel.php registration
3. Verify routes apply middleware in correct order
4. Check TenantAwareRateLimiter service exists

### Issue: "Fraud detection not blocking"

**Solution**:
1. Verify FraudCheckMiddleware exists
2. Check FraudControlService is injected
3. Verify ML model is trained (check config/fraud.php)
4. Check fraud score threshold in middleware

### Issue: "Age verification not working"

**Solution**:
1. Verify AgeVerificationMiddleware exists
2. Check user has birthdate in database
3. Verify vertical is in age restrictions list
4. Check middleware order (should be last)

---

## 📝 Checklist

- [ ] Review `ETAP1_MIDDLEWARE_REFACTOR_GUIDE.md`
- [ ] Read `ETAP1_COMPLETION_STATUS.md`
- [ ] Run `middleware_architecture_verification.php`
- [ ] Run diagnostic scripts
- [ ] Execute `full_controller_refactor.php`
- [ ] Review cleanup report
- [ ] Update all route files
- [ ] Execute `generate_final_report.php`
- [ ] Test all endpoints
- [ ] Verify no errors in logs
- [ ] Deploy to staging
- [ ] Final testing on staging
- [ ] Deploy to production

---

## 🎯 Next Action

**Run this command now**:

```bash
php middleware_architecture_verification.php
```

This will verify the current state and generate a report showing what's ready and what needs to be done.

---

**Questions?** Review the guide files or check the generated reports.

**Ready to deploy?** Follow the checklist above and execute the scripts in order.

---

**Version**: 1.0  
**Status**: ETAP 1 - 70% Complete  
**Last Updated**: 2026-03-28
