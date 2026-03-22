declare(strict_types=1);

# SECURITY AUDIT & REMEDIATION PLAN — CatVRF 2026-03-17

## Executive Summary

Выявлены **6 критических** и **6+ высокорисковых** уязвимостей безопасности.
Требуется комплексное расширение архитектуры безопасности перед production.

---

## УЯЗВИМОСТЬ #1: Отсутствие полноценного API Authentication

### Текущее состояние

- routes/api.php: только базовый `auth:sanctum`
- Нет API Key mechanism для B2B интеграций
- Нет отдельного token scoping (чтение vs запись)
- Нет rate limiting на уровне middleware

### Решение

1. **Расширить Sanctum конфиг** (config/sanctum.php):
   - Добавить `api_token_table`
   - Добавить абилити-based токены

2. **Создать API Key Service**:
   - app/Services/Security/ApiKeyService.php
   - Генерация, ротация, ревокация ключей
   - Хранение hash в БД

3. **Создать Middleware**:
   - app/Http/Middleware/ApiKeyAuthentication.php
   - Проверка API Key из заголовка X-API-Key или Authorization: Bearer

4. **Таблица:**

   ```sql
   CREATE TABLE api_keys (
       id BIGINT PRIMARY KEY,
       tenant_id BIGINT NOT NULL,
       user_id BIGINT NULLABLE,
       name VARCHAR(255),
       key_hash VARCHAR(255) UNIQUE,
       key_preview CHAR(8), -- последние 8 символов для UI
       abilities JSON, -- ['read', 'write', 'payments', ...]
       expires_at TIMESTAMP NULLABLE,
       last_used_at TIMESTAMP NULLABLE,
       revoked_at TIMESTAMP NULLABLE,
       created_at TIMESTAMP,
       FOREIGN KEY (tenant_id) REFERENCES tenants(id),
       FOREIGN KEY (user_id) REFERENCES users(id)
   );
   ```

**Статус**: ❌ **Требует реализации**

---

## УЯЗВИМОСТЬ #2: Слабый Rate Limiting

### Текущее состояние

- RateLimiterService существует, но использует базовый Laravel throttle
- Нет sliding window алгоритма (только fixed window)
- Нет burst protection
- Нет применения на критичных эндпоинтах (webhooks, платежи, поиск)

### Решение

1. **Расширить RateLimiterService** (app/Services/Security/RateLimiterService.php):

   ```php
   - checkPaymentInit() — 10 попыток/мин на пользователя
   - checkPromoApply() — 50 попыток/мин на тенант
   - checkWishlistPay() — 20 попыток/мин на пользователя
   - checkWebhookRetry() — 100 попыток/час на тенант
   - checkSearch() — 1000 запросов/час на IP с ML-запросами
   - checkReferralClaim() — 5 попыток/час на пользователя
   ```

2. **Реализовать Sliding Window** в Redis:

   ```
   ключ: rate_limit:{tenant}:{endpoint}:{user|ip}
   TTL: 60 сек
   Значение: JSON array с timestamp всех запросов за окно
   ```

3. **Добавить Burst Protection**:

   ```
   Если >3 отказов подряд → добавить exponential backoff
   После 5 отказов → temp ban на 5 минут
   Лог в FraudML для обнаружения DDoS
   ```

4. **Middleware**:
   - app/Http/Middleware/RateLimitMiddleware.php
   - Применить к payment, promo, wishlist, search, webhook routes

**Статус**: ⚠️ **Частично реализовано, требует расширения**

---

## УЯЗВИМОСТЬ #3: Нет защиты от Replay Attack в платежах

### Текущее состояние

- `payment_idempotency_records` таблица создана ✅
- Но проверка `payload_hash` **не реализована** в PaymentService
- Нет сравнения hash при повторном запросе

### Решение

1. **Создать IdempotencyService** (app/Services/Security/IdempotencyService.php):

   ```php
   public function check(string $operation, string $idempotencyKey, array $payload): ?array
       - Генерировать payload_hash = hash('sha256', json_encode($payload))
       - Проверить duplicate по idempotency_key
       - Если найден и hash совпадает → вернуть cached response
       - Если найден и hash НЕ совпадает → бросить exception
       - Если не найден → создать запись + вернуть null (proceed)
   ```

2. **Интегрировать в PaymentService**:

   ```php
   public function initPayment(string $idempotencyKey, array $data): PaymentResult
   {
       $cached = IdempotencyService::check('payment_init', $idempotencyKey, $data);
       if ($cached) {
           Log::channel('audit')->info('Payment idempotency cache hit', [
               'correlation_id' => $data['correlation_id'],
               'idempotency_key' => $idempotencyKey
           ]);
           return PaymentResult::fromArray($cached);
       }
       
       // Proceed with actual payment init
       $result = $this->gateway->initPayment($data);
       
       IdempotencyService::record('payment_init', $idempotencyKey, $data, $result->toArray());
       return $result;
   }
   ```

3. **Миграция update**:
   - Добавить индекс: `UNIQUE (idempotency_key, expires_at)` для поддержки TTL

**Статус**: ⚠️ **Таблица есть, сервис не создан, логика не интегрирована**

---

## УЯЗВИМОСТЬ #4: Отсутствие Webhook Signature Validation

### Текущее состояние

- Internal/WebhookController существует
- Но **нет проверки подписи** от Tinkoff/Sber/СБП
- Любой может отправить фальшивый webhook и изменить статус платежа!

### Решение

1. **Создать WebhookSignatureService** (app/Services/Security/WebhookSignatureService.php):

   ```php
   public function verify(string $provider, string $payload, string $signature): bool
       - Для Tinkoff: HMAC-SHA256 с SECRET_KEY
       - Для Sber: HMAC-SHA256 с сертификатом
       - Для СБП: проверка по IP из whitelist + HMAC
       - Логирование всех попыток (audit log)
   ```

2. **Обновить Internal/WebhookController**:

   ```php
   public function handle(Request $request, string $provider)
   {
       $payload = $request->getContent();
       $signature = $request->header('X-Signature') ?? $request->header('Signature');
       
       if (!WebhookSignatureService::verify($provider, $payload, $signature)) {
           Log::channel('fraud_alert')->warning('Webhook signature verification failed', [
               'provider' => $provider,
               'ip' => $request->ip(),
               'correlation_id' => $request->header('X-Correlation-ID')
           ]);
           return response()->json(['error' => 'Invalid signature'], 401);
       }
       
       // Proceed with webhook processing
   }
   ```

3. **Config**: app/Config/WebhookSecrets.php

   ```php
   return [
       'tinkoff' => ['secret' => env('TINKOFF_WEBHOOK_SECRET')],
       'sber' => ['cert' => env('SBER_WEBHOOK_CERT')],
       'sbp' => ['ip_whitelist' => ['10.0.0.0/8', ...]],
   ];
   ```

**Статус**: ❌ **Требует реализации**

---

## УЯЗВИМОСТЬ #5: RBAC не разделяет User и Tenant CRM

### Текущее состояние

- TenantCRMOnly middleware существует
- Но политики (Policies) **не проверяют абилити** пользователя
- Обычный пользователь может потенциально получить доступ к HR, зарплатам, выплатам

### Решение

1. **Создать Policies**:
   - app/Policies/EmployeePolicyy.php — только для бизнеса (не для пользователей)
   - app/Policies/PayrollPolicy.php — только для администраторов бизнеса
   - app/Policies/PayoutPolicy.php — только для финанс-менеджеров
   - app/Policies/WalletPolicy.php — для owner/accountant

2. **Обновить Filament Resources**:
   - EmployeeResource: `->canAccess('view') → $user->hasRole('admin|manager')`
   - PayrollResource: `->canAccess('viewAny') → $user->isBusinessOwner()`
   - PayoutResource: `->canAccess('create') → $user->hasAbility('finance')`

3. **Обновить API Controllers**:

   ```php
   public function index(Request $request)
   {
       $this->authorize('viewAny', Employee::class);
       // Only then proceed
   }
   ```

4. **Gate определения** (AuthServiceProvider):

   ```php
   Gate::define('view-payroll', function (User $user) {
       return $user->isBusinessOwner() && $user->tenant_id === $user->tenantId();
   });
   ```

**Статус**: ⚠️ **Middleware есть, но Policies и Gates не полные**

---

## УЯЗВИМОСТЬ #6: Нет Input Validation на всех API

### Текущее состояние

- FormRequest'ы есть в некоторых местах
- Но много контроллеров не имеют валидации (особенно B2B API)

### Решение

1. **Создать Base FormRequest** (app/Http/Requests/BaseApiRequest.php):

   ```php
   abstract class BaseApiRequest extends FormRequest
   {
       public function authorize(): bool
       {
           return auth()->check();
       }
       
       protected function failedValidation(Validator $validator)
       {
           throw new ValidationException($validator, response()->json([
               'error' => 'Validation failed',
               'errors' => $validator->errors(),
               'correlation_id' => request()->header('X-Correlation-ID')
           ], 422));
       }
   }
   ```

2. **Создать FormRequest'ы для критичных эндпоинтов**:
   - app/Http/Requests/PaymentInitRequest.php
   - app/Http/Requests/PromoApplyRequest.php
   - app/Http/Requests/WishlistPayRequest.php
   - app/Http/Requests/ReferralClaimRequest.php
   - Все B2B API endpoints

3. **Применить в контроллерах**:

   ```php
   public function store(PaymentInitRequest $request)
   {
       $validated = $request->validated();
       // Proceed
   }
   ```

**Статус**: ⚠️ **Частично реализовано**

---

## УЯЗВИМОСТЬ #7: CORS и CSRF не описаны

### Текущее состояние

- config/cors.php существует
- Но не ясно, какие origins разрешены для SPA
- CSRF protection может быть слабой для API

### Решение

1. **Обновить config/cors.php**:

   ```php
   'allowed_origins' => [
       env('FRONTEND_URL', 'http://localhost:3000'),
       env('ADMIN_PANEL_URL', 'http://localhost:8000/admin'),
   ],
   'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
   'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key', 'X-Correlation-ID'],
   'exposed_headers' => ['X-RateLimit-Remaining', 'X-RateLimit-Reset'],
   'max_age' => 86400,
   'supports_credentials' => true,
   ```

2. **Документировать** в docs/SECURITY.md

**Статус**: ⚠️ **Config может быть не достаточно строгим**

---

## УЯЗВИМОСТЬ #8: API Versioning отсутствует

### Текущее состояние

- Все API в одной версии `/api/`
- Нет forward compatibility strategy
- Невозможно deprecate endpoints

### Решение

1. **Реструктурировать routes**:

   ```
   routes/
   ├── api.php → routes/api/v1.php (перенести существующее)
   ├── api/v1.php (новая структура с версией)
   └── api/v2.php (для будущих изменений)
   ```

2. **В routes/api.php**:

   ```php
   Route::prefix('api')
       ->middleware(['api'])
       ->group(function () {
           Route::prefix('v1')->group(base_path('routes/api/v1.php'));
           // Route::prefix('v2')->group(base_path('routes/api/v2.php'));
       });
   ```

3. **Accept header support**:

   ```php
   // Middleware для автоматического определения версии
   app/Http/Middleware/ApiVersioning.php
   ```

**Статус**: ❌ **Требует реализации**

---

## УЯЗВИМОСТЬ #9: IP Whitelisting отсутствует

### Текущее состояние

- Нет защиты для webhook endpoints
- Нет защиты для admin endpoints
- Любой IP может достучаться до Internal/WebhookController

### Решение

1. **Создать IPWhitelistMiddleware** (app/Http/Middleware/IpWhitelistMiddleware.php):

   ```php
   public function handle(Request $request, Closure $next)
   {
       $ip = $request->ip();
       $whitelist = config('security.ip_whitelist');
       
       if (!$this->isIpInWhitelist($ip, $whitelist)) {
           Log::channel('security')->warning('IP blocked', ['ip' => $ip]);
           return response()->json(['error' => 'IP not whitelisted'], 403);
       }
       return $next($request);
   }
   ```

2. **Config**: app/Config/Security.php

   ```php
   'ip_whitelist' => [
       'webhook' => [
           '85.143.0.0/16',  // Tinkoff
           '77.244.0.0/14',  // Sber
           '195.68.0.0/14',  // СБП
       ],
       'admin' => [
           '195.0.0.0/8',    // Internal corporate
       ],
   ];
   ```

3. **Применить**:

   ```php
   Route::middleware([IpWhitelistMiddleware::class . ':webhook'])
       ->post('/internal/webhooks/{provider}', WebhookController@handle);
   ```

**Статус**: ❌ **Требует реализации**

---

## УЯЗВИМОСТЬ #10: OpenAPI/Swagger не описана

### Текущее состояние

- Нет документации API
- Нет security schemes в swagger
- Нет rate limit информации

### Решение

1. **Установить L5-Swagger**: `composer require darkaonline/l5-swagger`

2. **Добавить Security Definition** в config/l5-swagger.php:

   ```yaml
   components:
     securitySchemes:
       sanctum:
         type: http
         scheme: bearer
         description: Laravel Sanctum token
       apiKey:
         type: apiKey
         in: header
         name: X-API-Key
         description: API Key for B2B integrations
     schemas:
       RateLimitInfo:
         type: object
         properties:
           X-RateLimit-Limit: { type: integer }
           X-RateLimit-Remaining: { type: integer }
           X-RateLimit-Reset: { type: integer }
   ```

3. **Аннотации в контроллерах**:

   ```php
   /**
    * @OA\Post(
    *     path="/api/v1/payments",
    *     security={{"sanctum":{}}},
    *     @OA\Response(
    *         response=429,
    *         description="Too many requests",
    *         @OA\Header(ref="#/components/headers/RateLimitInfo")
    *     )
    * )
    */
   ```

**Статус**: ❌ **Требует реализации**

---

## УЯЗВИМОСТЬ #11: Wishlist & Referral Abuse

### Текущее состояние

- FraudControlService::check() вызывается, но не полностью проверяет манипуляции вишлистами
- Нет проверки на "добавление в вишлист + сразу оплата = генерация бонуса"
- Нет проверки на "реферальная накрутка"

### Решение

1. **Расширить FraudControlService**:

   ```php
   public function checkWishlistManipulation(int $userId, int $itemId): bool
   {
       // Проверить: был ли товар в вишлисте >24 часов назад
       // Если нет и попытка оплатить в течение 1 часа → фрод
       // ML-скор повышается
   }
   
   public function checkReferralAbuse(int $referrerId, int $refereeId): bool
   {
       // Проверить: множественные рефералы с одного IP
       // Проверить: достижение оборота через фейковые заказы
       // Логировать в fraud_attempts
   }
   ```

2. **Таблица audit**:

   ```sql
   wishlist_manipulations (
       user_id, item_id, added_at, paid_at, time_diff_seconds, flagged_as_fraud
   )
   referral_abuses (
       referrer_id, referee_id, ip, created_accounts_count, flagged_as_fraud
   )
   ```

**Статус**: ⚠️ **FraudControlService частично реализован**

---

## УЯЗВИМОСТЬ #12: Search API — нет rate limit на ML-запросы

### Текущее состояние

- Общий rate limit на SearchController
- Но нет отдельного лимита на тяжёлые ML-запросы (embeddings, recommendations)

### Решение

1. **Разделить endpoints**:

   ```php
   // Легкие поиски: 1000 запросов/час
   GET /api/v1/search?q=...&fast=1
   
   // Тяжёлые (ML): 100 запросов/час
   GET /api/v1/search?q=...&with_recommendations=1&with_embeddings=1
   ```

2. **Middleware**:

   ```php
   public function handle(Request $request, Closure $next)
   {
       $isHeavy = $request->boolean('with_recommendations') 
           || $request->boolean('with_embeddings');
       
       $limit = $isHeavy ? 100 : 1000;
       
       if (!RateLimiterService::checkSearch($limit)) {
           return response()->json(['error' => 'Rate limit exceeded'], 429);
       }
       return $next($request);
   }
   ```

**Статус**: ❌ **Требует реализации**

---

## IMPLEMENTATION ROADMAP

### Week 1: Core Security Infrastructure

- [ ] IdempotencyService (task #4)
- [ ] WebhookSignatureService (task #5)
- [ ] API Authentication (task #2)
- [ ] Enhanced RateLimiter (task #3)

### Week 2: Access Control & Validation

- [ ] RBAC Policies/Gates (task #6)
- [ ] Input Validation FormRequests (task #7)
- [ ] IPWhitelist Middleware (task #10)

### Week 3: API Maturity

- [ ] API Versioning (task #9)
- [ ] OpenAPI/Swagger (task #11)
- [ ] CORS/CSRF Documentation (task #8)

### Week 4: Abuse Prevention & Documentation

- [ ] Wishlist & Referral Abuse Protection (task #12)
- [ ] Search API Rate Limiting (task #13)
- [ ] Security Documentation (task #14)

---

## TESTING STRATEGY

1. **Unit Tests**:
   - IdempotencyService: duplicate detection
   - WebhookSignatureService: signature verification
   - RateLimiter: sliding window, burst protection

2. **Integration Tests**:
   - API authentication flow (Sanctum + API Key)
   - Webhook processing with signature validation
   - Rate limit enforcement

3. **Security Tests**:
   - Replay attack simulation
   - Webhook spoofing attempts
   - IP whitelist bypass attempts
   - RBAC bypass attempts

4. **Load Tests**:
   - Rate limiter performance
   - Concurrent webhook processing

---

## MONITORING & ALERTS

1. **Sentry Integration**:
   - Signature verification failures
   - Rate limit triggers (>100/hour)
   - RBAC denials
   - Duplicate payment attempts

2. **Audit Logs**:
   - Log channel: `security` (API auth, validations)
   - Log channel: `fraud_alert` (abuse patterns)
   - Log channel: `audit` (compliance)

3. **Dashboards**:
   - Real-time rate limit violations
   - Failed webhook validations
   - RBAC denials by user/resource

---

## Success Criteria

✅ All 6 critical vulnerabilities fixed
✅ All 6+ high-risk issues mitigated  
✅ 100% API endpoints have Input Validation
✅ All webhook endpoints protected by signature verification
✅ Rate limiting enforced on all critical paths
✅ API Versioning in place
✅ RBAC policies comprehensive
✅ Security documentation complete
✅ 90%+ test coverage for security modules
