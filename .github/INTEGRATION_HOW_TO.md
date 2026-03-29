# HOW TO ADD MIDDLEWARE SECTION TO copilot-instructions.md

**Quick Integration Guide**

---

## 📍 LOCATION

**File:** `.github/copilot-instructions.md`  
**Current size:** 3160 lines  
**Add after:** Line 3160 (end of current file)

---

## ✂️ CONTENT TO ADD

After the current ending:
```
---

**Версия:** 1.3 (AI Constructor Framework)  
**Дата:** 25.03.2026  
**Статус:** PRODUCTION READY

```

**INSERT THE FOLLOWING:**

```markdown

---

# ⭐ MIDDLEWARE, КЭШИРОВАНИЕ И ТЕСТИРОВАНИЕ (ETAP 1 РАСШИРЕНИЕ)

## 🔗 Reference Materials
- **Complete guide:** `.github/MIDDLEWARE_CACHING_TESTING_CANON2026.md` (1500+ lines)
- **Integration guide:** `.github/ETAP1_MIDDLEWARE_INTEGRATION_GUIDE.md`
- **Full index:** `.github/ETAP1_COMPLETE_MATERIALS_INDEX.md`

## Обязательная Middleware Pipeline

```
correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify → controller
```

## 5 Основных Middleware (v2026.03.28)

| Middleware | Назначение | Статус |
|-----------|-----------|--------|
| CorrelationIdMiddleware | Генерация correlation_id | ✅ |
| B2CB2BMiddleware | B2C vs B2B режим | ✅ |
| FraudCheckMiddleware | ML-фрод детекция | ✅ |
| RateLimitingMiddleware | Tenant-aware rate limit | ✅ |
| AgeVerificationMiddleware | Возрастная проверка | ✅ |

## Регистрация в Kernel.php

```php
protected $middlewareAliases = [
    'correlation-id'   => \App\Http\Middleware\CorrelationIdMiddleware::class,
    'b2c-b2b'          => \App\Http\Middleware\B2CB2BMiddleware::class,
    'fraud-check'      => \App\Http\Middleware\FraudCheckMiddleware::class,
    'rate-limit'       => \App\Http\Middleware\RateLimitingMiddleware::class,
    'age-verify'       => \App\Http\Middleware\AgeVerificationMiddleware::class,
];
```

## Применение в Routes

```php
Route::middleware([
    'correlation-id',  'auth:sanctum',  'tenant',
    'b2c-b2b',        'rate-limit',    'fraud-check', 'age-verify'
])->group(function () {
    // Все API маршруты получают полную middleware цепь
    Route::apiResource('beauty/salons', BeautySalonController::class);
    // ... остальные routes
});
```

## Кэширование Middleware

- **B2CB2BCacheMiddleware** (Redis, 1 час)
- **ResponseCacheMiddleware** (Redis, 5-15 мин)
- **UserTasteCacheMiddleware** (Redis, 30 мин)
- **MasterAvailabilityCache** (Redis, 5 мин)

## Тестирование

```php
// tests/Feature/Middleware/B2CB2BMiddlewareTest.php
it('определяет B2C режим', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson('/api/v1/beauty/salons');
    $response->assertOk();
    expect(request()->get('is_b2b'))->toBeFalse();
});
```

## BaseApiController (ONLY 8 methods)

```php
protected function getCorrelationId(): string;
protected function isB2C(): bool;
protected function isB2B(): bool;
protected function getModeType(): string;
protected function auditLog(string $action, array $data): void;
protected function fraudLog(float $score, bool $blocked): void;
protected function successResponse(mixed $data, string $correlationId): JsonResponse;
protected function errorResponse(string $message, int $code): JsonResponse;
```

**ЗАПРЕЩЕНО:** Никакой бизнес-логики в контроллерах!

## CANON 2026 Compliance

✅ **MUST DO:**
- Middleware pipeline order никогда не менять
- Не дублировать middleware логику в контроллерах
- Кэшировать expensive операции
- Тестировать весь middleware chain

❌ **NEVER DO:**
- Не вставлять services в контроллеры напрямую
- Не генерировать correlation_id вручную
- Не проверять B2C/B2B в контроллерах
- Не обходить rate limiting

---

**ETAP 1 Status:** 75% → Pending team execution (2-3 hours)

**Next:** See `.github/ETAP1_ENTRY_POINT.md` for complete execution plan

```

---

## 🔧 INTEGRATION METHOD

### Option 1: Manual (Recommended for Verification)
1. Open `.github/copilot-instructions.md` in editor
2. Go to end of file (line 3160)
3. Delete closing triple backticks
4. Copy-paste the content above
5. Add closing triple backticks at end
6. Save file

### Option 2: Command Line
```bash
cd c:\opt\kotvrf\CatVRF\.github
cat MIDDLEWARE_CACHING_TESTING_CANON2026.md >> copilot-instructions.md
```

### Option 3: PHP Script (Most Reliable)
```php
<?php
$file = 'c:\opt\kotvrf\CatVRF\.github\copilot-instructions.md';

// Remove closing ``` if exists
$content = file_get_contents($file);
if (str_ends_with($content, "```\n")) {
    $content = substr($content, 0, -4);
}

// Add new sections
$middleware_section = file_get_contents('MIDDLEWARE_CACHING_TESTING_CANON2026.md');
$new_content = $content . "\n\n---\n\n" . $middleware_section;

// Add closing ```
$new_content .= "\n```";

file_put_contents($file, $new_content);
echo "Integration complete!\n";
```

---

## ✅ VERIFICATION

After integration, verify:

1. **File opens without errors**
   ```bash
   php -l c:\opt\kotvrf\CatVRF\.github\copilot-instructions.md
   ```

2. **File ends properly**
   ```bash
   tail -c 100 c:\opt\kotvrf\CatVRF\.github\copilot-instructions.md
   ```

3. **Middleware sections appear**
   ```bash
   grep -n "MIDDLEWARE" c:\opt\kotvrf\CatVRF\.github\copilot-instructions.md
   grep -n "Pipeline" c:\opt\kotvrf\CatVRF\.github\copilot-instructions.md
   ```

---

## 📊 FINAL STATISTICS

After integration:
- **File size:** 3160 → ~5500 lines
- **New sections:** 5 major sections
- **Code examples:** 10+ new examples
- **Markdown size increase:** 2340 lines
- **Time to read:** 15 minutes

---

## 📝 CHECKLIST

After adding to copilot-instructions.md:

- [ ] File opens without errors
- [ ] Middleware section appears
- [ ] All code examples formatted correctly
- [ ] Links to other docs work
- [ ] File ends with triple backticks
- [ ] Character encoding: UTF-8
- [ ] Line endings: CRLF
- [ ] Tested: Copilot can read sections

---

## 🎯 SUCCESS CRITERIA

✅ Integration successful when:
1. copilot-instructions.md opens without errors
2. New middleware sections visible
3. All code examples properly formatted
4. File size increased to ~5500 lines
5. All references/links work
6. File still ends with triple backticks

---

**Recommended:** Do integration NOW to finalize copilot-instructions.md

After integration → ETAP 1 = 100% documentation complete ✅

Team can then proceed with execution phase (2-3 hours)

