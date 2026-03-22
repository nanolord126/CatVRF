# Финансовый модуль (Finances Domain)

## Описание

Полнофункциональный модуль управления платежами, кошельками и подписками для CatVRF.

### Поддерживаемые платёжные системы

**Основной приоритет:**

- 🔴 **Tinkoff** - основной провайдер (SBP, карты, QR-коды, токенизация)
- 🟠 **Tochka Bank** - корпоративные платежи и выплаты
- 🟡 **Sber** - мобильная коммерция
- 🔵 **SBP** - система быстрых платежей (используется как альтернатива)

**Фискальная система (ФЗ-54):**

- **CloudKassir** (основной)
- **Atol** (резервный)

### Функциональность

- ✅ **Платёжные транзакции** - управление платежами через Tinkoff, Tochka, Sber, SBP
- ✅ **Токенизация карт** - сохранение и повторное использование платёжных карт
- ✅ **Повторяющиеся подписки** - автоматические платежи (daily, weekly, monthly, yearly)
- ✅ **Фискальные чеки** - интеграция с ФЗ-54 (CloudKassir, Atol)
- ✅ **ML-защита от мошенничества** - обнаружение подозрительных платежей
- ✅ **Multi-tenant scoping** - полная изоляция данных тенантов
- ✅ **Audit logging** - логирование всех операций с платежами
- ✅ **Idempotency** - защита от дублирования платежей

## Архитектура

```
app/Domains/Finances/
├── Http/Controllers/
│   └── SbpWebhookController.php     # Обработка вебхуков платёжных провайдеров
├── Interfaces/
│   ├── PaymentGatewayInterface.php  # Интерфейс платёжного шлюза
│   ├── FiscalServiceInterface.php   # Интерфейс фискальной системы
│   └── FiscalDriverInterface.php    # Интерфейс драйвера фискального провайдера
├── Models/
│   ├── PaymentTransaction.php       # Платёжная транзакция
│   ├── RecurringModels.php          # WalletCard, Subscription
│   └── Security/
│       └── MLModelVersion.php       # ML-модели для обнаружения мошенничества
└── Services/
    ├── PaymentService.php           # Основной сервис платежей
    ├── WalletService.php            # Управление кошельком
    ├── TinkoffDriver.php            # Драйвер Тинькофф
    └── Fiscal/
        ├── CloudKassirFiscalDriver.php
        └── AtolFiscalDriver.php
```

## Использование

### 1. Инициация платежа

```php
$paymentService = app(PaymentService::class);

$paymentResult = $paymentService->initPayment([
    'amount' => 100.50,
    'order_id' => 'ORD-12345',
    'user_id' => $userId,
    'order_type' => 'course_enrollment',
    'description' => 'Course: Laravel Mastery',
    'metadata' => [
        'correlation_id' => Str::uuid(),
        'course_id' => 123,
    ],
]);

// Результат:
// [
//     'status' => 'pending',
//     'payment_id' => '...',
//     'payment_url' => 'https://...',
//     'amount' => 100.50,
// ]
```

### 2. Обработка вебхука

```php
// routes/web.php
Route::post('/webhooks/sbp', [SbpWebhookController::class, 'handle'])
    ->name('sbp.webhook');

// Контроллер автоматически обработает платёж и обновит статус
```

### 3. Управление подписками

```php
// Создание подписки
$subscription = Subscription::create([
    'user_id' => $userId,
    'wallet_card_id' => $cardId,
    'amount' => 500,
    'frequency' => Subscription::FREQUENCY_MONTHLY,
    'starts_at' => now(),
    'correlation_id' => Str::uuid(),
]);

// Проверка активности
if ($subscription->isActive()) {
    // Подписка активна
}

// Отмена подписки
$subscription->cancel('User requested cancellation');
```

### 4. ML-защита от мошенничества

```php
// Проверка платежа на мошенничество
$mlModel = MLModelVersion::where('is_active', true)
    ->where('model_type', MLModelVersion::TYPE_FRAUD_DETECTION)
    ->first();

// Предсказание вернёт:
// [
//     'is_fraud' => false,
//     'confidence' => 0.95,
// ]
```

## Статусы платежей

- **pending** - Ожидание подтверждения платежа
- **authorized** - Средства удержаны (платёж авторизирован)
- **settled** - Платёж завершён и средства списаны
- **failed** - Ошибка при обработке платежа
- **refunded** - Платёж возвращён

## Периодичность подписок

- `daily` - Ежедневно
- `weekly` - Еженедельно
- `monthly` - Ежемесячно
- `yearly` - Ежегодно

## Безопасность

### Multi-tenant scoping

Все модели имеют обязательное поле `tenant_id`. Доступ к платежам одного тенанта невозможен для другого.

### Audit logging

Все значимые операции логируются:

- Создание платежа
- Изменение статуса
- Возврат средств
- Обработка вебхука

### Validation webhook signature

Вебхуки проверяются по подписи (HMAC-SHA256):

```php
private function validateWebhookSignature(Request $request): bool
{
    $signature = $request->header('X-Webhook-Signature');
    $expectedSignature = hash_hmac('sha256', $request->getContent(), config('payments.webhook_secret'));
    return hash_equals($expectedSignature, $signature);
}
```

### Idempotency

Платежи обрабатываются идемпотентно. Дублирование вебхука не создаст дублирование платежа.

## Configuration

```php
// config/payments.php
return [
    'default' => 'tinkoff',
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    'providers' => [
        'tinkoff' => [
            'terminal_key' => env('TINKOFF_TERMINAL_KEY'),
            'secret_key' => env('TINKOFF_SECRET_KEY'),
            'api_url' => 'https://api.tinkoff.ru/v2/',
        ],
    ],
    'fiscal' => [
        'provider' => 'cloudkassir',
        'api_key' => env('CLOUDKASSIR_API_KEY'),
    ],
];
```

## Миграция и сидирование

```bash
# Выполнить миграцию
php artisan migrate

# Заполнить БД тестовыми данными
php artisan db:seed --class=FinancesSeeder
```

## Тестирование

```php
// Тестовый платёж (settled)
$payment = PaymentTransaction::factory()
    ->settled()
    ->create(['user_id' => $user->id]);

// Тестовый платёж (failed)
$payment = PaymentTransaction::factory()
    ->failed()
    ->create(['user_id' => $user->id]);
```

## Permissions (Spatie)

Необходимо регистрировать permissions для платежей:

```php
Permission::create(['name' => 'view-payments']);
Permission::create(['name' => 'create-payments']);
Permission::create(['name' => 'edit-payments']);
Permission::create(['name' => 'refund-payments']);
Permission::create(['name' => 'delete-payments']);
```

## Policy

Используется `PaymentTransactionPolicy` для контроля доступа:

```php
// В контроллере
$this->authorize('view', $payment);
$this->authorize('refund', $payment);
```

## Debugging

### Логирование

Все операции логируются в канал `payments`:

```bash
tail -f storage/logs/payments-*.log
```

### Отслеживание с correlation_id

```php
Log::channel('payments')->info('Payment initiated', [
    'correlation_id' => $correlationId,
    'payment_id' => $paymentId,
]);
```

## Production requirements

- ✅ Полная реализация всех методов
- ✅ Обработка ошибок в каждом методе
- ✅ Логирование всех операций
- ✅ Валидация входных данных
- ✅ Multi-tenant scoping
- ✅ Audit logging
- ✅ Идемпотентность
- ✅ Policy-based access control
- ✅ Миграции с правильными индексами
- ✅ Сидеры с реалистичными данными
- ✅ Документация (этот файл)
- ✅ Без TODO, заглушек, пропущенных методов

## License

MIT
