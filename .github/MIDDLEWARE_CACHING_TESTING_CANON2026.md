# КАНОН ДЛЯ MIDDLEWARE, КЭШИРОВАНИЯ И ТЕСТИРОВАНИЯ (2026)

## MIDDLEWARE АРХИТЕКТУРА (Обязательна)

### Что такое Middleware?

Middleware — это слой фильтров, выполняющихся перед попаданием запроса в контроллер (или после ответа).

**Используется для:**
- Проверки прав доступа
- Логирования
- Rate limiting (ограничение частоты запросов)
- Fraud-check (проверка мошенничества)
- B2C/B2B разделения
- Добавления correlation_id
- Изменения запроса/ответа

**Middleware работает как цепочка (pipeline)**. Каждый middleware может:
1. Пропустить запрос дальше: `return $next($request);`
2. Вернуть ответ сразу (например, 403 Forbidden)
3. Модифицировать запрос или ответ

### Middleware Pipeline в CatVRF (ОБЯЗАТЕЛЬНЫЙ ПОРЯДОК)

```
correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify → controller
```

**Обоснование порядка:**
1. **correlation-id** (1-й) - Создаёт ID для отслеживания
2. **auth:sanctum** (2-й) - Проверяет аутентификацию
3. **tenant** (3-й) - Устанавливает tenant scope
4. **b2c-b2b** (4-й) - Определяет режим на основе tenant
5. **rate-limit** (5-й) - Проверяет лимиты на основе tenant
6. **fraud-check** (6-й) - Проверяет фрод на основе user/tenant
7. **age-verify** (7-й) - Проверяет возраст для restricted вертикалей

### 5 Основных Middleware для CatVRF

#### 1. CorrelationIdMiddleware
```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') 
                         ?? Str::uuid()->toString();

        $request->merge(['correlation_id' => $correlationId]);
        
        return $next($request)
            ->header('X-Correlation-ID', $correlationId);
    }
}
```

**Назначение:** Генерирует или валидирует correlation_id для полного отслеживания запроса

#### 2. B2CB2BMiddleware
```php
final class B2CB2BMiddleware
{
    public function __construct(
        private readonly \App\Services\BusinessGroupService $businessGroupService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        // Определяем B2B режим: есть ли INN и business_card_id
        $isB2B = $request->has('inn') && $request->has('business_card_id');
        
        // Или если пользователь явно в B2B режиме
        $isB2B = $isB2B || $user->business_groups()->exists();

        $request->merge(['is_b2b' => $isB2B]);
        $request->merge(['b2c_mode' => !$isB2B]);

        return $next($request);
    }
}
```

**Назначение:** Определяет B2C vs B2B режим на основе наличия INN и business_card_id

#### 3. FraudCheckMiddleware
```php
final class FraudCheckMiddleware
{
    public function __construct(
        private readonly \App\Services\FraudMLService $fraudService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $correlationId = $request->get('correlation_id');

        if (!$user) {
            return $next($request);
        }

        // Пропускаем GET запросы (безопасные)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        try {
            $score = $this->fraudService->scoreOperation(
                operation: $request->method() . ' ' . $request->path(),
                userId: $user->id,
                amount: $request->get('amount', 0),
                ipAddress: $request->ip(),
                correlationId: $correlationId
            );

            if ($this->fraudService->shouldBlock($score, $request->path())) {
                return response()->json(
                    ['error' => 'Suspected fraud activity'],
                    403
                );
            }

            $request->merge(['fraud_score' => $score]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error(
                'Fraud check failed',
                ['correlation_id' => $correlationId, 'error' => $e->getMessage()]
            );
            // Не блокируем при ошибке ML-сервиса
        }

        return $next($request);
    }
}
```

**Назначение:** ML-базированная проверка на мошенничество

#### 4. RateLimitingMiddleware
```php
final class RateLimitingMiddleware
{
    public function __construct(
        private readonly \App\Services\RateLimiterService $rateLimiter
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenant = $request->tenant();

        if (!$user || !$tenant) {
            return $next($request);
        }

        $key = "rate_limit:tenant_{$tenant->id}:user_{$user->id}:{$request->path()}";
        $limit = 100; // 100 запросов
        $window = 60;  // в течение 60 секунд

        if (!$this->rateLimiter->attempt($key, $limit, $window)) {
            return response()->json(
                ['error' => 'Rate limit exceeded'],
                429
            )->header('Retry-After', $window);
        }

        return $next($request);
    }
}
```

**Назначение:** Tenant-aware rate limiting с Redis

#### 5. AgeVerificationMiddleware
```php
final class AgeVerificationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->date_of_birth) {
            return $next($request);
        }

        $age = \Carbon\Carbon::parse($user->date_of_birth)->age;

        // Проверяем по вертикали
        $restrictedVerticals = ['Bars', 'Pharmacy', 'Vapes', 'Alcohol'];
        
        foreach ($restrictedVerticals as $vertical) {
            if (str_contains($request->path(), strtolower($vertical))) {
                if ($age < 18) {
                    return response()->json(
                        ['error' => 'Age restriction: 18+ only'],
                        403
                    );
                }
            }
        }

        $request->merge(['user_age' => $age]);

        return $next($request);
    }
}
```

**Назначение:** Проверка возраста для restricted вертикалей

### Регистрация Middleware в Kernel.php

```php
declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // Global middleware - выполняются для каждого запроса
        \App\Http\Middleware\CorrelationIdMiddleware::class,
    ];

    protected $middlewareGroups = [
        'api' => [
            \App\Http\Middleware\CorrelationIdMiddleware::class,
            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth'           => \App\Http\Middleware\Authenticate::class,
        'auth.basic'     => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session'   => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers'  => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'            => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'          => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive'   => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed'         => \App\Http\Middleware\ValidateSignature::class,
        'throttle'       => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'       => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        
        // CatVRF Custom Middleware (ОБЯЗАТЕЛЬНЫ)
        'correlation-id'   => \App\Http\Middleware\CorrelationIdMiddleware::class,
        'b2c-b2b'          => \App\Http\Middleware\B2CB2BMiddleware::class,
        'fraud-check'      => \App\Http\Middleware\FraudCheckMiddleware::class,
        'rate-limit'       => \App\Http\Middleware\RateLimitingMiddleware::class,
        'age-verify'       => \App\Http\Middleware\AgeVerificationMiddleware::class,
        
        // Caching Middleware
        'b2c-b2b-cache'    => \App\Http\Middleware\B2CB2BCacheMiddleware::class,
        'response-cache'   => \App\Http\Middleware\ResponseCacheMiddleware::class,
        'user-taste-cache' => \App\Http\Middleware\UserTasteCacheMiddleware::class,
    ];
}
```

### Применение Middleware в Routes

**Лучший способ** (рекомендуется для всех маршрутов):

```php
// routes/api.php

Route::middleware([
    'correlation-id',      // 1st - Generate ID
    'auth:sanctum',        // 2nd - Authenticate
    'tenant',              // 3rd - Tenant scoping
    'b2c-b2b',            // 4th - Mode detection
    'rate-limit:100,1',   // 5th - Rate limiting (100 req/min)
    'fraud-check',        // 6th - Fraud detection
    'age-verify',         // 7th - Age verification
])->group(function () {
    Route::apiResource('beauty/salons', BeautySalonController::class);
    Route::apiResource('beauty/appointments', AppointmentController::class);
    // ... все остальные routes
});
```

**Как использовать в контроллере:**

```php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

final class BeautySalonController extends BaseApiController
{
    public function index(Request $request)
    {
        $correlationId = $this->getCorrelationId(); // Из middleware
        $isB2B = $request->get('is_b2b', false);   // Из middleware
        $fraudScore = $request->get('fraud_score', 0.0); // Из middleware
        
        // КОНТРОЛЛЕР ЧИСТЫЙ - БЕЗ ДУБЛИРОВАНИЯ ЛОГИКИ MIDDLEWARE
        // Вся проверка доступа уже выполнена middleware
        
        return $this->successResponse(
            $this->salonService->list(isB2B: $isB2B),
            correlationId: $correlationId
        );
    }
}
```

---

## КЭШИРОВАНИЕ С MIDDLEWARE (Обязательна эффективная интеграция)

### Когда нужно кэширование?

| Сценарий | Обычный Cache | Cache + Queue | Почему |
|----------|--------------|---------------|--------|
| Популярные товары | Да | Да | Тяжёлый расчёт |
| Профиль вкусов пользователя | Да | Да | ML-вычисления |
| Результаты AI-конструкторов | Да | Да | Генерация + анализ |
| Доступность мастеров/слоты | Да | Да | Частые изменения |
| Цены и наличие товаров | Да | Да | Нужно быстро |
| B2C/B2B режим пользователя | Да | Нет | Простой расчёт |

### Типы Кэширования в Middleware

| Middleware | Кэшируемые данные | Driver | TTL | Использование |
|-----------|------------------|--------|-----|----------------|
| B2CB2BCacheMiddleware | B2C/B2B режим | Redis | 1 час | Частый запрос |
| UserTasteCacheMiddleware | Профиль вкусов | Redis | 30 мин | AI-рекомендации |
| ResponseCacheMiddleware | Полный JSON | Redis | 5-15 мин | Публичные списки |
| MasterAvailabilityCache | Слоты мастеров | Redis | 5 мин | Календарь |

### Пример 1: B2CB2BCacheMiddleware

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class B2CB2BCacheMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return $next($request);
        }

        $cacheKey = "user_{$userId}_b2b_mode";

        // Cache::remember - получить из кэша или вычислить и кэшировать
        $isB2B = Cache::remember($cacheKey, now()->addHour(), function () use ($request) {
            return $request->has('inn') && $request->has('business_card_id');
        });

        $request->merge(['is_b2b' => $isB2B]);

        return $next($request);
    }
}
```

### Пример 2: ResponseCacheMiddleware

```php
final class ResponseCacheMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'response_' . md5($request->fullUrl() . ($request->user()?->id ?? ''));

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $response = $next($request);

        // Кэшируем только успешные GET ответы
        if ($response->isSuccessful() && $request->isMethod('GET')) {
            Cache::put($key, $response, now()->addMinutes(10));
        }

        return $response;
    }
}
```

### Пример 3: Cache Warming с Queues

```php
// app/Jobs/CacheWarmers/WarmUserTasteProfileJob.php

namespace App\Jobs\CacheWarmers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class WarmUserTasteProfileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $userId) {}

    public function handle(\App\Services\UserTasteProfileService $service)
    {
        $profile = $service->calculateFullProfile($this->userId);

        Cache::store('redis')
             ->tags(['user_taste'])
             ->put(
                 "user_taste_{$this->userId}", 
                 $profile, 
                 now()->addHours(6)
             );
    }
}
```

### Cache Invalidation (инвалидация кэша)

```php
// При изменении профиля пользователя
event(new UserProfileChanged($userId));

// Listener автоматически инвалидирует кэш
Cache::tags(['user_taste', "user_{$userId}"])->flush();

// Или через Job для фонового обновления
WarmUserTasteProfileJob::dispatch($userId)->onQueue('cache-warm');
```

---

## ТЕСТИРОВАНИЕ MIDDLEWARE (Обязательно)

### Два типа тестов

| Тип | Когда | Плюсы | Минусы |
|-----|------|-------|--------|
| Unit | Логика одного middleware | Быстро, изолированно | Не проверяет flow |
| Feature | Через реальные маршруты | Проверяет весь pipeline | Медленнее |

**Рекомендация**: Основной упор на Feature-тесты, потому что middleware работают в связке.

### Feature-тест для B2CB2BMiddleware

```php
// tests/Feature/Middleware/B2CB2BMiddlewareTest.php

use App\Models\User;

it('определяет B2C режим когда нет inn и business_card_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/beauty/salons', [
            'Accept' => 'application/json',
        ]);

    $response->assertOk();
    
    // Проверяем что режим установлен в request
    expect(request()->get('is_b2b'))->toBeFalse();
});

it('определяет B2B режим когда переданы inn и business_card_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/beauty/salons', [
            'inn' => '7707083893',
            'business_card_id' => 'bc_123456',
            'Accept' => 'application/json',
        ]);

    $response->assertOk();
    expect(request()->get('is_b2b'))->toBeTrue();
});
```

### Feature-тест для FraudCheckMiddleware

```php
it('блокирует запрос при срабатывании fraud-check', function () {
    $user = User::factory()->create();

    // Mock FraudMLService для имитации срабатывания фрода
    $this->mock(\App\Services\FraudMLService::class)
         ->shouldReceive('scoreOperation')
         ->andReturn(0.95); // Высокий фрод скор
    
    $this->mock(\App\Services\FraudMLService::class)
         ->shouldReceive('shouldBlock')
         ->andReturn(true);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/beauty/appointments', [
            'salon_id' => 1,
            'master_id' => 1,
            'datetime' => now()->addDay(),
        ]);

    $response->assertStatus(403)
             ->assertJson(['error' => 'Suspected fraud activity']);
});
```

### Feature-тест для RateLimitingMiddleware

```php
it('возвращает 429 при превышении rate limit', function () {
    $user = User::factory()->create();
    $tenant = $user->tenant;

    // Отправляем 101 запрос (лимит 100)
    for ($i = 0; $i <= 100; $i++) {
        $response = $this->actingAs($user)
            ->getJson('/api/v1/beauty/salons');

        if ($i < 100) {
            $response->assertOk();
        } else {
            $response->assertStatus(429)
                     ->assertJson(['error' => 'Rate limit exceeded']);
        }
    }
});
```

### Unit-тест для B2CB2BMiddleware

```php
// tests/Unit/Middleware/B2CB2BMiddlewareTest.php

use App\Http\Middleware\B2CB2BMiddleware;
use Illuminate\Http\Request;

it('устанавливает is_b2b = true при наличии inn и business_card_id', function () {
    $middleware = new B2CB2BMiddleware(app(\App\Services\BusinessGroupService::class));

    $request = Request::create('/test', 'GET', [
        'inn' => '1234567890',
        'business_card_id' => 'bc_123',
    ]);

    $request->setUserResolver(fn() => User::factory()->create());

    $middleware->handle($request, fn($r) => $r);

    expect($request->get('is_b2b'))->toBeTrue();
});
```

### Структура тестов для CatVRF

```
tests/
├── Feature/
│   ├── Middleware/
│   │   ├── B2CB2BMiddlewareTest.php
│   │   ├── FraudCheckMiddlewareTest.php
│   │   ├── RateLimitingMiddlewareTest.php
│   │   ├── AgeVerificationMiddlewareTest.php
│   │   └── CorrelationIdMiddlewareTest.php
│   ├── CacheMiddleware/
│   │   ├── B2CB2BCacheMiddlewareTest.php
│   │   ├── ResponseCacheMiddlewareTest.php
│   │   └── UserTasteCacheMiddlewareTest.php
│   └── Vertical/
│       ├── Beauty/
│       ├── Hotels/
│       └── ...
└── Unit/
    └── Middleware/
        ├── B2CB2BMiddlewareTest.php
        ├── FraudCheckMiddlewareTest.php
        └── ...
```

### Советы для тестирования

- ✅ Всегда тестируй middleware chain в Feature-тестах
- ✅ Для RateLimitingMiddleware - отправляй несколько запросов подряд
- ✅ Для AgeVerificationMiddleware - тестируй обе граничные условия (< 18, >= 18)
- ✅ Используй `actingAs($user)` + `withoutMiddleware()` для изолированных тестов
- ✅ Добавь тесты на порядок выполнения middleware (correlation_id должен быть первым)
- ✅ Проверяй headers в ответе (X-Correlation-ID, Retry-After)
- ✅ Логируй все тесты middleware с correlation_id

---

**ИТОГО ДЛЯ MIDDLEWARE:**
- 5 основных middleware классов ✅
- Обязательный порядок в pipeline ✅
- Кэширование где нужно ✅
- Полное покрытие тестами ✅
- Интеграция с auth, tenant, fraud, rate-limit ✅

**Последний шаг**: Добавить эти разделы в .github/copilot-instructions.md перед финальным завершением ETAP 1
