declare(strict_types=1);

# COPILOT RULES 2026 — СТРОГИЕ ЗАПРЕТЫ И ТРЕБОВАНИЯ

## 🔴 КРИТИЧЕСКИЕ ЗАПРЕТЫ (нарушение = отказ в merge)

### 1. Структура файлов и кодовой базы

- ❌ **Файлы короче 60 строк** (кроме миграций, фабрик, конфигов)
  - Минимум 60 строк = реальный контент, а не заглушки
  - Если файл < 60 строк = есть стаб/заглушка = REJECT

- ❌ **Пустые классы, пустые методы, заглушки**
  - `return null;` без исключения = REJECT
  - `throw new \Exception("Not implemented");` = REJECT
  - `if (false) { ... }` = REJECT
  - Пустой `form() { return null; }` в Filament = REJECT
  - Пустой `table() { return null; }` в Filament = REJECT

- ❌ **Комментарии-TODO, FIXME, HACK, // later, // temporary**
  - Все TODO/FIXME должны быть заменены на production-ready код
  - Если есть TODO = файл неполный = REJECT
  - Все комментарии должны объяснять ЧТО, а не ЧТО ДЕЛАТЬ

- ❌ **Debug функции**
  - `die()`, `exit()`, `dd()`, `dump()`, `var_dump()` = REJECT
  - `echo` без контекста логирования = REJECT
  - `print_r()`, `error_log()` = REJECT
  - Используй `Log::channel('audit')` для всех логов

- ❌ **Facade и статические вызовы**
  - `app/Facades/*` — все файлы удаляются
  - `*Facade.php` — все удаляются
  - `Auth::`, `Auth->`, `auth()` = REJECT (использовать constructor injection)
  - `Response::`, `response()` = REJECT (использовать return new JsonResponse)
  - `Request::`, `request()` = REJECT (использовать dependency injection)
  - `Route::`, `Route->` в контроллерах = REJECT
  - `Config::`, `config()` = REJECT (использовать constructor injection конфигов)
  - `Cache::`, `cache()` = REJECT (использовать CacheService через constructor)
  - `Mail::`, `mail()` = REJECT (использовать MailService)
  - `Queue::`, `queue()` = REJECT (использовать QueueService)
  - `Session::`, `session()` = REJECT

- ❌ **Пустые формы в Filament**
  - `public function form(Form $form): Form { return $form; }` = REJECT
  - `public function table(Table $table): Table { return $table; }` = REJECT
  - Все формы должны иметь минимум 5 полей, все таблицы — минимум 3 колонки

### 2. Требования к методам и сервисам

- ❌ **Методы без return типов**
  - Все методы должны иметь строгую типизацию
  - `public function doSomething()` = REJECT
  - Правильно: `public function doSomething(): Result | never`

- ❌ **Constructor injection отсутствует**
  - Все зависимости через `readonly` в конструкторе
  - `public function __construct()` без параметров = REJECT
  - Все сервисы должны иметь: `public function __construct(private readonly DependencyService $service)`

- ❌ **Отсутствие DB::transaction()**
  - Все мутации (create/update/delete) ДОЛЖНЫ быть в `DB::transaction()`
  - Запрос без транзакции = REJECT

- ❌ **Отсутствие audit-логирования**
  - Все важные операции логируются: `Log::channel('audit')`
  - Мутация без лога = REJECT
  - Платёж без лога = REJECT
  - Запись в кошелёк без лога = REJECT

- ❌ **Отсутствие correlation_id**
  - Все логи должны содержать `correlation_id`
  - Все события должны нести `correlation_id`
  - Все HTTP-ответы должны содержать `correlation_id` в header
  - `Log::info('msg')` без `correlation_id` = REJECT

- ❌ **Отсутствие fraud-check**
  - Все мутации должны начинаться с `FraudControlService::check($dto)`
  - Платёж без fraud-check = REJECT
  - Вывод денег без fraud-check = REJECT
  - Создание заказа без fraud-check = REJECT

- ❌ **Отсутствие B2C/B2B-проверки**
  - Все важные операции должны знать: B2C это или B2B?
  - B2B определяется: `$isB2B = $request->has('inn') && $request->has('business_card_id')`
  - Логика различается в коммиссиях, сроках, лимитах
  - Проверка без явной логики = REJECT

### 3. Требования к тестам и миграциям

- ❌ **Миграции без idempotency**
  - Все миграции должны проверять `if (Schema::hasTable(...)) return;`
  - Миграция без проверки = REJECT

- ❌ **Миграции без комментариев**
  - Все таблицы: `$table->comment('...')`
  - Все поля: `$table->string('name')->comment('...')`
  - Таблица без комментария = REJECT

- ❌ **Миграции без correlation_id/uuid/tags**
  - Все таблицы мутаций должны иметь:
    - `$table->string('correlation_id')->nullable()->index()`
    - `$table->uuid('uuid')->nullable()->unique()->index()`
    - `$table->json('tags')->nullable()`
  - Таблица без них = REJECT

- ❌ **Тесты без корректного setup**
  - Все тесты должны использовать factories с `tenant_id`, `correlation_id`
  - Тест без tenant scoping = REJECT
  - Тест, который может выполниться неправильно в разных заказах = REJECT

### 4. Требования к routes и контроллерам

- ❌ **Routes без middleware**
  - Все routes должны иметь минимум: `middleware(['auth:sanctum', 'tenant'])`
  - Route без auth = REJECT (кроме явно публичных)
  - Route без tenant-scoping = REJECT

- ❌ **Контроллеры без try/catch**
  - Все методы контроллера в try/catch
  - Ответ всегда: `JsonResponse` с `correlation_id`
  - Контроллер без try/catch = REJECT

- ❌ **Контроллеры без FormRequest**
  - Все POST/PUT/PATCH должны использовать `FormRequest`
  - Валидация прямо в контроллере = REJECT
  - FormRequest без `authorize()` = REJECT

### 5. Требования к моделям

- ❌ **Модели без fillable/hidden/casts**
  - Все должны быть явно определены
  - `protected $fillable = [];` не допускается (явно указать все поля)
  - Модель без `$hidden = ['password', ...]` = REJECT

- ❌ **Модели без booted()**
  - Все модели должны иметь:
    ```php
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });
    }
    ```
  - Модель без global scope = REJECT (если это не публичная модель)

- ❌ **Модели без отношений**
  - Все отношения должны быть явно определены
  - Если есть FK = должно быть отношение
  - Модель с FK но без отношения = REJECT

### 6. Требования к конфигам

- ❌ **Config без env() с fallback**
  - `config('app.name')` должен быть: `env('APP_NAME', 'CatVRF')`
  - Жёсткие значения без fallback = REJECT
  - Config без комментариев = REJECT

### 7. Требования к Filament

- ❌ **Filament Resources без полного form()**
  - Все обязательные поля должны быть в form
  - `form()` = { return $form; } = REJECT
  - Resources без getEloquentQuery() с tenant scoping = REJECT

- ❌ **Filament Pages без actions**
  - `getHeaderActions()` должна возвращать минимум одну кнопку
  - Пустой getHeaderActions() = REJECT

### 8. Требования к Livewire

- ❌ **Livewire без rules()**
  - Все компоненты с мутациями должны иметь валидацию
  - `submit()` без валидации = REJECT
  - `submit()` без DB::transaction() = REJECT

### 9. Запрещённые паттерны код

```php
// ❌ Запрещено — Facade
Auth::check();
response()->json([]);
Route::get('/', [HomeController::class, 'index']);
Cache::remember('key', 60, fn() => ...);
Log::error('message');  // Используй Log::channel('audit')

// ✅ Правильно — Constructor injection
public function __construct(
    private readonly AuthService $auth,
    private readonly ResponseFactory $response,
) {}

$this->auth->check();
return new JsonResponse([]);

// ❌ Запрещено — Методы без типов
public function getUser() { }

// ✅ Правильно — С типами
public function getUser(): User | null { throw new UserNotFoundException(); }

// ❌ Запрещено — Return null без исключения
public function find($id) {
    return User::find($id);  // Может вернуть null
}

// ✅ Правильно — Бросить исключение
public function find($id): User {
    return User::findOrFail($id);
}

// ❌ Запрещено — Без fraud-check
public function paymentCreate(PaymentRequest $request): JsonResponse {
    $payment = Payment::create(...);
}

// ✅ Правильно — С fraud-check
public function paymentCreate(PaymentRequest $request): JsonResponse {
    FraudControlService::check(PaymentDto::from($request));
    // Дальше логика
}

// ❌ Запрещено — Без correlation_id
Log::info('Payment created', ['payment_id' => $id]);

// ✅ Правильно — С correlation_id
Log::channel('audit')->info('Payment created', [
    'payment_id' => $id,
    'correlation_id' => $request->header('X-Correlation-ID'),
]);
```

---

## 🟢 ТРЕБУЕМЫЕ ПАТТЕРНЫ

### Все сервисы должны следовать этому формату:

```php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ExampleService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly IdempotencyService $idempotency,
    ) {}

    public function doSomething(ExampleDto $dto): Result | never
    {
        // 1. Проверка на дублирование (idempotency)
        if ($this->idempotency->exists($dto->idempotencyKey)) {
            return $this->idempotency->get($dto->idempotencyKey);
        }

        // 2. Fraud-check
        $fraudScore = $this->fraud->check($dto);
        if ($fraudScore > 0.8) {
            throw new FraudException('Suspicious activity detected');
        }

        // 3. Транзакция
        DB::transaction(function () use ($dto) {
            // Логика здесь
            $result = ...;

            // 4. Аудит
            $this->audit->log('action_name', $result->id, [
                'correlation_id' => $dto->correlationId,
                'before' => $dto->toArray(),
                'after' => $result->toArray(),
            ]);

            return $result;
        });
    }
}
```

### Все контроллеры должны следовать этому формату:

```php
declare(strict_types=1);

namespace App\Http\Controllers;

final readonly class ExampleController
{
    public function __construct(
        private readonly ExampleService $service,
    ) {}

    public function store(ExampleRequest $request): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();
            
            $dto = ExampleDto::from($request);
            $dto->correlationId = $correlationId;

            $result = $this->service->doSomething($dto);

            return response()->json([
                'success' => true,
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);
        } catch (FraudException $e) {
            Log::channel('fraud_alert')->warning('Fraud detected', [
                'correlation_id' => $correlationId ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Operation not allowed',
                'correlation_id' => $correlationId ?? null,
            ], 403);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Error in store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId ?? null,
            ], 500);
        }
    }
}
```

---

## 📋 ЧЕК-ЛИСТ ПЕРЕД MERGE

- [ ] Нет файлов короче 60 строк (кроме миграций/фабрик)
- [ ] Нет TODO, FIXME, HACK комментариев
- [ ] Нет dd(), dump(), die(), var_dump()
- [ ] Нет Facade использований
- [ ] Нет статических вызовов auth(), response(), Request::, Config::
- [ ] Все методы имеют return типы
- [ ] Все сервисы с constructor injection
- [ ] Все мутации в DB::transaction()
- [ ] Все мутации с FraudControlService::check()
- [ ] Все логи с correlation_id
- [ ] Все логи в Log::channel('audit')
- [ ] Все B2C/B2B операции с явной проверкой `$isB2B`
- [ ] Все миграции с idempotency
- [ ] Все таблицы мутаций имеют correlation_id/uuid/tags
- [ ] Все контроллеры в try/catch
- [ ] Все routes с middleware
- [ ] Все модели с fillable/hidden/casts
- [ ] Все модели с booted() и global scope
- [ ] Все FormRequest с authorize()
- [ ] Все Filament Resources с полным form()/table()

---

**Автор:** Copilot Rules 2026  
**Версия:** 1.0  
**Дата:** 25.03.2026  
**Статус:** PRODUCTION READY
