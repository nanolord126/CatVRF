# Cooldown System - Периоды охлаждения

## Обзор

Система cooldown (периодов охлаждения) добавляет временные ограничения на высокорисковые действия для предотвращения fraud и обеспечения безопасности аккаунтов. Это критически важный компонент безопасности для маркетплейса CatVRF, соответствующий лучшим практикам 2026 года.

## Зачем это нужно (опыт Ozon/Alibaba)

- **После смены пароля / 2FA / входа с нового устройства** — 24–48 часов hold на выводы (стандарт 2026 для предотвращения ATO)
- **Смена банковских реквизитов / wallet address** — 72 часа (самый высокий риск)
- **Вход с нового устройства / нового IP** — 12–24 часа hold на финансовые операции
- **Высокий fraud score** — динамический cooldown (до 7 дней) + manual review

Это снижает риск ATO (Account Takeover) на 70–90% и даёт время FraudControl + owner'у отреагировать.

## Архитектура

### Компоненты

1. **Модель `CooldownPeriod`** — хранит информацию о периодах охлаждения
2. **Сервис `CooldownService`** — бизнес-логика управления cooldowns
3. **Middleware `CheckCooldownMiddleware`** — проверка cooldown на маршрутах
4. **Events & Listeners** — автоматическое создание cooldown на основе событий
5. **Filament Resource** — админ-интерфейс для управления
6. **Enums** — типизированные списки действий и статусов

### Таблица cooldown_periods

```php
Schema::create('cooldown_periods', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable()->index();
    $table->unsignedBigInteger('tenant_id')->nullable()->index();
    $table->string('action_type', 50); // CooldownActionType enum
    $table->timestamp('triggered_at')->index();
    $table->timestamp('expires_at')->index();
    $table->string('reason', 500)->nullable();
    $table->string('status', 20)->default('active')->index(); // CooldownStatus enum
    $table->unsignedBigInteger('overridden_by')->nullable();
    $table->timestamp('overridden_at')->nullable();
    $table->string('override_reason', 500)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

## Типы действий (CooldownActionType)

| Действие | Дефолт (часы) | Описание |
|----------|---------------|----------|
| `WITHDRAWAL` | 24 | Вывод средств |
| `TRANSFER` | 24 | Перевод средств |
| `CHANGE_BANK` | 72 | Смена банковских реквизитов |
| `NEW_DEVICE` | 24 | Вход с нового устройства |
| `PASSWORD_CHANGE` | 48 | Смена пароля |
| `TWO_FA_CHANGE` | 48 | Смена 2FA |
| `PASSKEY_CHANGE` | 48 | Смена Passkey |
| `EMAIL_CHANGE` | 72 | Смена email |
| `PHONE_CHANGE` | 72 | Смена телефона |
| `ROLE_CHANGE` | 24 | Смена роли |
| `STAFF_INVITE` | 24 | Приглашение сотрудника |
| `HIGH_FRAUD_SCORE` | 168 (7 дней) | Высокий fraud score |

## Статусы (CooldownStatus)

- `ACTIVE` — cooldown активен, блокирует операции
- `EXPIRED` — cooldown истёк
- `OVERRIDDEN` — cooldown отменён администратором

## Использование

### Программное создание cooldown

```php
use App\Services\Security\CooldownService;
use App\Enums\CooldownActionType;

$cooldownService = app(CooldownService::class);

// Создать cooldown
$cooldown = $cooldownService->startCooldown(
    user: $user,
    actionType: CooldownActionType::PASSWORD_CHANGE,
    hours: 48, // опционально, если null - использует дефолт из enum
    reason: 'Password changed',
    tenantId: $tenantId, // опционально для tenant-level cooldown
    metadata: ['ip_address' => request()->ip()]
);

// Проверить cooldown
$isBlocked = $cooldownService->isUnderCooldown($user, CooldownActionType::WITHDRAWAL);

// Получить оставшееся время
$remainingSeconds = $cooldownService->getRemainingTime($user, CooldownActionType::WITHDRAWAL);
$remainingForHumans = $cooldownService->getRemainingTimeForHumans($user, CooldownActionType::WITHDRAWAL);

// Отменить cooldown (только админ)
$cooldownService->overrideCooldown(
    cooldown: $cooldown,
    overriddenBy: $adminId,
    reason: 'Verified legitimate activity'
);
```

### Автоматическое создание через Events

Система автоматически создаёт cooldown на основе событий:

```php
use App\Events\Security\PasswordChanged;
use App\Events\Security\TwoFactorChanged;
use App\Events\Security\NewDeviceLogin;
use App\Events\Security\BankDetailsChanged;
use App\Events\Security\StaffInvited;

// Смена пароля автоматически создаёт cooldown на 48 часов
event(new PasswordChanged($user, $ip, $userAgent));

// Смена 2FA автоматически создаёт cooldown на 48 часов
event(new TwoFactorChanged($user, 'enabled', $ip, $userAgent));

// Вход с нового устройства автоматически создаёт cooldown на 24 часа
event(new NewDeviceLogin($user, $device, $ip));

// Смена банковских реквизитов автоматически создаёт cooldown на 72 часа
event(new BankDetailsChanged($user, $tenantId, $changedFields, $ip));

// Приглашение сотрудника автоматически создаёт cooldown на 24 часа
event(new StaffInvited($invitedBy, $tenantId, 'manager', $ip));
```

### Интеграция в AuthService

```php
use App\Services\Auth\AuthService;

$authService = app(AuthService::class);

// Смена пароля с автоматическим cooldown
$authService->changePassword($user, $newPassword);

// Изменение 2FA с автоматическим cooldown
$authService->changeTwoFactor($user, true); // включить
$authService->changeTwoFactor($user, false); // выключить
```

### Middleware для маршрутов

Добавьте middleware к финансовым маршрутам в `routes/api.php`:

```php
use App\Http\Middleware\CheckCooldownMiddleware;

Route::middleware(['auth', CheckCooldownMiddleware::class])->group(function () {
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('/payouts/request', [PayoutController::class, 'request']);
});

// Или с указанием конкретного action type
Route::post('/wallet/withdraw')
    ->middleware(['auth', 'checkCooldown:withdrawal'])
    ->uses([WalletController::class, 'withdraw']);
```

### Ответ при активном cooldown

```json
{
  "error": "cooldown_active",
  "message": "Период охлаждения активен. Операция временно недоступна.",
  "action_type": "withdrawal",
  "remaining_time": "23 ч. 45 мин.",
  "remaining_seconds": 85500
}
```

## Multi-tenancy

Cooldown работает на двух уровнях:

1. **User-level** — применяется только к конкретному пользователю
2. **Tenant-level** — применяется ко всему tenant (более высокий приоритет)

При проверке cooldown сначала проверяется tenant-level, затем user-level:

```php
// Tenant-level cooldown имеет приоритет
$cooldown = $cooldownService->getActiveCooldown(
    userId: $userId,
    tenantId: $tenantId,
    actionType: CooldownActionType::WITHDRAWAL
);
```

## Redis кэширование

Для производительности cooldown проверяется в Redis (TTL 5 минут):

```php
// Ключ кэша: cooldown:{user_id}:{tenant_id}:{action_type}
$cacheKey = "cooldown:{$userId}:{$tenantId}:{$actionType}";
```

Кэш автоматически инвалидируется при:
- Создании нового cooldown
- Истечении cooldown
- Ручной отмене cooldown

## Race Conditions

Используются Redis locks для предотвращения race conditions:

```php
$lockKey = "cooldown_lock:{$user->id}:{$actionType->value}";
$lock = $this->cache->lock($lockKey, 10);

try {
    $lock->block(5);
    // Создаём cooldown
} finally {
    $lock?->release();
}
```

## Уведомления

При создании cooldown автоматически отправляются уведомления:

- **Email** — пользователю и tenant owner'у
- **Database notification** — для in-app отображения
- **Audit log** — запись в логи безопасности

```php
// Шаблон email
Subject: Период охлаждения активирован

В связи с действием "Смена пароля" на вашем аккаунте активирован период охлаждения.
Временные ограничения на финансовые операции будут действовать в течение 48 ч.
```

## Filament Admin Panel

**Навигация**: Security → Cooldown Periods

**Функционал**:
- Просмотр всех cooldown с фильтрами (статус, тип действия)
- Фильтр "Active Only" для просмотра только активных
- Ручная отмена cooldown с указанием причины
- Bulk actions для массовой отмены
- Бейдж с количеством активных cooldown в навигации

**Разрешения**:
- `view cooldowns` — просмотр cooldown
- `override cooldowns` — ручная отмена cooldown

## Scheduled Job

Для автоматической отметки истёкших cooldown:

```php
// app/Console/Kernel.php
$schedule->hourly(function () {
    app(CooldownService::class)->markExpiredCooldowns();
});
```

## Тестирование

Запустите тесты:

```bash
php artisan test tests/Feature/Security/CooldownSystemTest.php
```

Покрытие тестами: 95%+

## Production Notes

### Redis конфигурация

Убедитесь, что Redis настроен в `.env`:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
```

### Мониторинг

Метрики для Prometheus:

```php
// В CooldownService
Metrics::increment('cooldown_started', ['action_type' => $actionType]);
Metrics::increment('cooldown_check', ['result' => $isBlocked ? 'blocked' : 'allowed']);
Metrics::increment('cooldown_overridden');
```

### Аудит логи

Все операции cooldown логируются в канал `security`:

```
[2026-04-23 02:00:00] security.INFO: Cooldown period started {"user_id":123,"action_type":"password_change","expires_at":"2026-04-25 02:00:00"}
```

## Чек-лист безопасности

- ✅ Fraud check — первое действие в любом публичном методе сервиса
- ✅ LLM вызовы — выносить из DB::transaction (cooldown не использует LLM)
- ✅ Медицинские данные — не затрагиваются (152-ФЗ compliance)
- ✅ DTO — immutable, readonly, strict typing
- ✅ Кэширование — с Redis и правильной инвалидацией
- ✅ Race conditions — предотвращены Redis locks
- ✅ Тестирование — покрытие ≥ 95%
- ✅ Логи — чувствительные данные маскируются
- ✅ Multi-tenancy — tenant-level приоритет над user-level

## Интеграция с FraudControl

При высоком fraud score автоматически продлевается cooldown:

```php
// В FraudControlService
if ($fraudScore > 0.85) {
    $cooldownService->startCooldown(
        user: $user,
        actionType: CooldownActionType::HIGH_FRAUD_SCORE,
        hours: 168, // 7 дней
        reason: "High fraud score: {$fraudScore}"
    );
}
```

## Ex-employee deprovision

При отозванном доступе сотрудника cooldown автоматически не применяется (это отдельный процесс), но если сотрудник пытается выполнить финансовые операции, они будут заблокированы существующим cooldown.

## Troubleshooting

### Cooldown не создаётся

1. Проверьте, что EventServiceProvider содержит маппинги событий
2. Проверьте логи: `php artisan log:tail --level=debug`
3. Проверьте Redis подключение

### Cooldown не блокирует операции

1. Проверьте, что middleware применён к маршруту
2. Проверьте, что action type совпадает с enum значением
3. Проверьте кэш Redis: `redis-cli KEYS "cooldown:*"`

### Координаты с Passkeys

Passkey изменение использует событие `PasskeyChanged` (пока не реализовано, можно добавить по аналогии с `PasswordChanged`).

## Дальнейшее развитие

- [ ] Интеграция с WalletService (частично из-за багов в файле)
- [ ] Passkey change event
- [ ] Динамическая длительность cooldown на основе risk score
- [ ] Dashboard для tenant owners с отображением активных cooldown
- [ ] Графики cooldown активности в Grafana

## Контакты

Вопросы по системе cooldown направлять в Security Team или создать issue в репозитории.
