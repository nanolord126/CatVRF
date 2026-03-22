declare(strict_types=1);

# Security Implementation Guide — CatVRF

## Checklist: Как интегрировать новые Security сервисы

### Phase 1: Core Infrastructure (День 1-2)

- [x] IdempotencyService создан
- [x] WebhookSignatureService создан  
- [x] RateLimiterService расширен
- [x] IpWhitelistMiddleware создан
- [x] config/security.php добавлен
- [x] Exceptions созданы
- [ ] **NEXT: Зарегистрировать сервисы в AppServiceProvider**

```php
// app/Providers/AppServiceProvider.php

public function register(): void
{
    $this->app->singleton(\App\Services\Security\IdempotencyService::class);
    $this->app->singleton(\App\Services\Security\WebhookSignatureService::class);
    $this->app->singleton(\App\Services\Security\RateLimiterService::class);
}
```

- [ ] **NEXT: Убедиться, что queue работает для Jobs**

```bash
# Проверить, что queue настроен
QUEUE_CONNECTION=redis  # в .env

# Запустить queue worker
php artisan queue:work
```

---

### Phase 2: Payment Integration (День 2-3)

#### Шаг 1: Обновить PaymentService

```php
// modules/Finances/Services/PaymentService.php

use App\Services\Security\IdempotencyService;
use App\Services\Security\RateLimiterService;

public function __construct(
    private IdempotencyService $idempotencyService,
    private RateLimiterService $rateLimiter,
    // ... остальные зависимости
) {}

public function initPayment(array $data): PaymentResult
{
    $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();
    $idempotencyKey = $data['idempotency_key'];
    $tenantId = filament()->getTenant()->id;
    
    // 1. Проверить rate limit
    if (!$this->rateLimiter->checkPaymentInit($tenantId, Auth::id(), $correlationId)) {
        throw new RateLimitException('Too many payment attempts');
    }
    
    // 2. Проверить идемпотентность
    $cached = $this->idempotencyService->check(
        'payment_init',
        $idempotencyKey,
        $data,
        $tenantId
    );
    
    if ($cached) {
        return PaymentResult::fromArray($cached);
    }
    
    // 3. Проверить fraud
    FraudControlService::check('payment', $data);
    
    // 4. Выполнить платёж
    $result = DB::transaction(function () use ($data) {
        return $this->gateway->initPayment($data);
    });
    
    // 5. Записать результат для идемпотентности
    $this->idempotencyService->record(
        'payment_init',
        $idempotencyKey,
        $data,
        $result->toArray(),
        $tenantId
    );
    
    Log::channel('audit')->info('Payment initiated', [
        'correlation_id' => $correlationId,
        'amount' => $data['amount'],
        'tenant_id' => $tenantId,
    ]);
    
    return $result;
}
```

#### Шаг 2: Обновить PaymentController

```php
// modules/Finances/Http/Controllers/PaymentController.php

use App\Http\Requests\PaymentInitRequest;

public function store(PaymentInitRequest $request): JsonResponse
{
    try {
        $result = $this->paymentService->initPayment($request->validated());
        
        return response()->json([
            'data' => $result,
            'correlation_id' => $request->getCorrelationId(),
        ], 201);
    } catch (RateLimitException $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'correlation_id' => $request->getCorrelationId(),
        ], 429);
    }
}
```

---

### Phase 3: Webhook Integration (День 3)

#### Шаг 1: Обновить Internal/WebhookController

```php
// app/Http/Controllers/Internal/WebhookController.php

use App\Services\Security\WebhookSignatureService;
use App\Http\Middleware\IpWhitelistMiddleware;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookSignatureService $signatureService,
    ) {}
    
    public function handle(Request $request, string $provider): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature') 
                  ?? $request->header('Signature');
        
        // Проверить подпись
        if (!$this->signatureService->verify($provider, $payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Обработать webhook
        $data = json_decode($payload, true);
        return $this->handleWebhook($provider, $data);
    }
}
```

#### Шаг 2: Защитить webhook route

```php
// routes/web.php или routes/internal.php

Route::middleware([
    IpWhitelistMiddleware::class . ':webhook',
    'throttle:webhook',
])
->post('/internal/webhooks/{provider}', WebhookController@handle)
->name('webhooks.handle');
```

#### Шаг 3: Добавить config для webhook secrets

```bash
# .env файл

TINKOFF_WEBHOOK_SECRET=your_secret_here
SBER_WEBHOOK_SECRET=your_secret_here
SBP_WEBHOOK_SECRET=your_secret_here
```

---

### Phase 4: Rate Limiting Integration (День 4)

#### Шаг 1: Добавить middleware в routes

```php
// routes/api.php

Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(function () {
        // Платежи с rate limiting
        Route::post('payments', [PaymentController::class, 'store'])
            ->middleware(RateLimitPaymentMiddleware::class);
        
        // Промо с rate limiting
        Route::post('promos/apply', [PromoController::class, 'apply'])
            ->middleware(RateLimitPromoMiddleware::class);
        
        // Поиск с rate limiting для ML
        Route::get('search', [SearchController::class, 'index'])
            ->middleware(RateLimitSearchMiddleware::class);
    });
```

#### Шаг 2: Создать Middleware

```php
// app/Http/Middleware/RateLimitPaymentMiddleware.php

namespace App\Http\Middleware;

use App\Services\Security\RateLimiterService;
use App\Exceptions\RateLimitException;

class RateLimitPaymentMiddleware
{
    public function __construct(
        private RateLimiterService $rateLimiter,
    ) {}
    
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        if (!$this->rateLimiter->checkPaymentInit(
            Auth::user()->tenant_id,
            Auth::id(),
            $correlationId
        )) {
            throw new RateLimitException();
        }
        
        return $next($request);
    }
}
```

---

### Phase 5: Input Validation (День 4)

#### Шаг 1: Обновить все API Controllers

```php
// Например, PaymentController

public function store(PaymentInitRequest $request): JsonResponse
{
    $validated = $request->validated();
    // Теперь $validated гарантированно содержит валидные данные
    
    $result = $this->service->initPayment($validated);
    
    return response()->json($result);
}
```

#### Шаг 2: Убедиться, что FormRequest'ы созданы

```bash
# Должны быть созданы:
app/Http/Requests/PaymentInitRequest.php
app/Http/Requests/PromoApplyRequest.php
app/Http/Requests/ReferralClaimRequest.php

# Добавить для остальных критичных endpoints
```

---

### Phase 6: CORS Configuration (День 5)

#### Шаг 1: Обновить config/cors.php

```php
// config/cors.php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://localhost:8000'
    )),
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-API-Key',
        'X-Correlation-ID',
        'Idempotency-Key',
        'X-Requested-With',
    ],
    
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],
    
    'max_age' => 86400,
    
    'supports_credentials' => true,
];
```

#### Шаг 2: Обновить .env

```bash
CORS_ALLOWED_ORIGINS="http://localhost:3000,http://localhost:8000,https://app.catvrf.ru"
```

---

### Phase 7: Testing (День 5-6)

#### Шаг 1: Unit Tests

```bash
php artisan make:test IdempotencyServiceTest
php artisan make:test WebhookSignatureServiceTest
php artisan make:test RateLimiterServiceTest
```

Пример теста:

```php
// tests/Unit/Services/IdempotencyServiceTest.php

public function test_idempotency_duplicate_detection(): void
{
    $service = new IdempotencyService();
    
    $payload = ['amount' => 10000, 'email' => 'test@test.com'];
    $idempotencyKey = 'key123';
    $tenantId = 1;
    
    // Первый вызов - null
    $result1 = $service->check('payment_init', $idempotencyKey, $payload, $tenantId);
    $this->assertNull($result1);
    
    // Запись результата
    $response = ['payment_id' => 'pay_123', 'status' => 'pending'];
    $service->record('payment_init', $idempotencyKey, $payload, $response, $tenantId);
    
    // Второй вызов с тем же payload - должен вернуть cached
    $result2 = $service->check('payment_init', $idempotencyKey, $payload, $tenantId);
    $this->assertEquals($response, $result2);
}

public function test_idempotency_payload_mismatch_throws(): void
{
    $service = new IdempotencyService();
    
    $payload1 = ['amount' => 10000];
    $payload2 = ['amount' => 20000];  // Другая сумма!
    $idempotencyKey = 'key123';
    $tenantId = 1;
    
    $response = ['payment_id' => 'pay_123'];
    $service->record('payment_init', $idempotencyKey, $payload1, $response, $tenantId);
    
    // Второй вызов с другим payload - должен выбросить исключение
    $this->expectException(InvalidPayloadException::class);
    $service->check('payment_init', $idempotencyKey, $payload2, $tenantId);
}
```

#### Шаг 2: Integration Tests

```bash
php artisan make:test PaymentApiTest --feature
```

```php
// tests/Feature/PaymentApiTest.php

public function test_payment_requires_authentication(): void
{
    $response = $this->postJson('/api/v1/payments', [
        'amount' => 10000,
        'currency' => 'RUB',
    ]);
    
    $response->assertStatus(401);
}

public function test_payment_validates_input(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/payments', [
            'amount' => 50,  // Слишком мало!
            'currency' => 'INVALID',
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['amount', 'currency']);
}

public function test_payment_enforces_rate_limit(): void
{
    $user = User::factory()->create();
    
    // 11 попыток - должна не пройти последняя
    for ($i = 0; $i < 11; $i++) {
        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments', [
                'amount' => 10000,
                'currency' => 'RUB',
                'description' => 'Test payment',
                'customer_email' => 'test@test.com',
                'return_url' => 'https://test.com',
                'idempotency_key' => "key_$i",
            ]);
        
        if ($i < 10) {
            $response->assertStatus(201);
        } else {
            $response->assertStatus(429);
        }
    }
}
```

#### Шаг 3: Security Tests

```bash
php artisan make:test SecurityTest --feature
```

```php
// tests/Feature/SecurityTest.php

public function test_webhook_invalid_signature_rejected(): void
{
    $response = $this->post('/internal/webhooks/tinkoff', [], [
        'X-Signature' => 'invalid_signature',
    ]);
    
    $response->assertStatus(401);
}

public function test_webhook_ip_whitelist_enforced(): void
{
    $this->withoutMiddleware();  // Отключить другой middleware временно
    
    $response = $this->from('10.0.0.1')
        ->post('/internal/webhooks/tinkoff', []);
    
    $response->assertStatus(403);
}

public function test_rbac_blocks_unauthorized_access(): void
{
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)
        ->get('/api/v1/tenants/1/employees');
    
    $response->assertStatus(403);
}
```

---

### Phase 8: Deployment & Monitoring (День 6-7)

#### Шаг 1: Миграция в production

```bash
# 1. Убедиться, что все тесты проходят
php artisan test

# 2. Backup БД
php artisan migrate:refresh --env=production --step

# 3. Deploy код
git push origin main
# (CD pipeline автоматически запустится)

# 4. Запустить миграции в production
php artisan migrate --env=production

# 5. Запустить queue worker
supervisor (должен быть настроен)

# 6. Проверить логи
tail -f storage/logs/audit.log
tail -f storage/logs/fraud_alert.log
```

#### Шаг 2: Настроить мониторинг

```php
// config/logging.php

'channels' => [
    'single' => [...],
    'audit' => [
        'driver' => 'single',
        'path' => storage_path('logs/audit.log'),
    ],
    'fraud_alert' => [
        'driver' => 'single',
        'path' => storage_path('logs/fraud_alert.log'),
    ],
],
```

#### Шаг 3: Sentry настройка

```bash
composer require sentry/sentry-laravel

php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

```env
SENTRY_LARAVEL_DSN=https://your_key@sentry.io/your_project_id
SENTRY_ENVIRONMENT=production
```

---

### Phase 9: Documentation & Knowledge Transfer (День 7)

- [x] docs/SECURITY.md создан
- [x] docs/SECURITY_AUDIT_REMEDIATION_PLAN.md создан
- [ ] **NEXT: Обновить API Documentation**
- [ ] **NEXT: Создать Security Guidelines для team**
- [ ] **NEXT: Провести security review с team**

---

## Rollback Plan (если что-то сломалось)

```bash
# Откатить определённый сервис
git revert <commit_hash>

# Или откатить определённый файл
git checkout HEAD~1 app/Services/Security/IdempotencyService.php

# Перезаспустить queue
php artisan queue:flush

# Очистить Redis cache
php artisan cache:flush
```

---

## Troubleshooting

### Проблема: "Rate limit не работает"

**Решение:**

```bash
# Убедиться, что Redis работает
redis-cli ping  # Должен вернуть PONG

# Проверить config/cache.php
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Проблема: "Webhook подпись не проверяется"

**Решение:**

```bash
# Убедиться, что secrets в .env
echo $TINKOFF_WEBHOOK_SECRET
echo $SBER_WEBHOOK_SECRET

# Проверить, что IP в whitelist
php artisan tinker
> config('security.ip_whitelist.webhook')
```

### Проблема: "FormRequest валидация не срабатывает"

**Решение:**

```bash
# Убедиться, что используется правильный class
use App\Http\Requests\PaymentInitRequest;

# Проверить, что в controller используется FormRequest
public function store(PaymentInitRequest $request)
```

---

## Next Steps

1. **Week 2**: API Versioning (/api/v1/)
2. **Week 3**: OpenAPI/Swagger
3. **Week 4**: Advanced ML abuse protection
4. **Week 5**: Compliance audit (PCI-DSS, GDPR)

---

**Last Updated**: 2026-03-17
**Status**: Ready for Implementation
