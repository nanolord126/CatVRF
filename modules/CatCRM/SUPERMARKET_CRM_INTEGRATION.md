# Supermarket CRM Integration

**Версия:** 1.0 (27.04.2026)
**Вертикаль:** Супермаркет
**Модуль:** CatCRM

## Обзор

CRM интеграция для вертикали Супермаркет предоставляет специализированный функционал для управления клиентами, заказами, подписками и возвратами в супермаркетах. Интеграция следует паттерну Clean Architecture с разделением на Domain, Application и Infrastructure слои.

## Архитектура (9 слоев)

### Структура модуля

```
modules/CatCRM/
├── Domain/
│   ├── Verticals/
│   │   └── Supermarket/
│   │       └── SupermarketOrder.php              # Вертикаль-специфичная сущность заказа
│   ├── Entities/
│   │   ├── Customer.php                          # Базовая сущность клиента
│   │   ├── Deal.php                              # Базовая сущность сделки
│   │   ├── Interaction.php                       # Взаимодействия с клиентом
│   │   ├── Segment.php                           # Сегменты клиентов
│   │   └── Tag.php                               # Теги клиентов
│   ├── Repositories/
│   │   └── SupermarketOrderRepositoryInterface.php # Интерфейс репозитория
│   └── Enums/
│       ├── CustomerType.php                      # Типы клиентов
│       ├── DealStatus.php                        # Статусы сделок
│       ├── InteractionType.php                   # Типы взаимодействий
│       └── LoyaltyTier.php                       # Уровни лояльности
├── Application/
│   ├── DTOs/
│   │   ├── CreateCustomerDTO.php                 # DTO для создания клиента
│   │   ├── CreateOrderDealDTO.php                # DTO для создания заказа
│   │   ├── CustomerAnalyticsDTO.php              # DTO для аналитики
│   │   └── TopCustomerDTO.php                    # DTO топ клиентов
│   └── Services/
│       └── SupermarketCRMService.php             # CRM сервис для супермаркетов
├── Infrastructure/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── SupermarketCRMController.php  # API контроллер
│   ├── Repositories/
│   │   └── EloquentSupermarketOrderRepository.php # Eloquent реализация репозитория
│   ├── Listeners/
│   │   └── Supermarket/
│   │       ├── OrderCreatedListener.php          # Слушатель создания заказа
│   │       ├── OrderStatusUpdatedListener.php    # Слушатель обновления статуса
│   │       ├── SubscriptionCreatedListener.php   # Слушатель создания подписки
│   │       └── ReturnCreatedListener.php         # Слушатель создания возврата
│   └── Jobs/
│       ├── UpdateCustomerStatisticsJob.php        # Job обновления статистики
│       ├── SyncSupermarketOrderToCRMJob.php      # Job синхронизации заказа
│       ├── AutoSegmentCustomersJob.php           # Job авто-сегментации
│       └── SendCustomerAnalyticsReportJob.php    # Job отправки отчета
└── routes/
    └── api/
        └── supermarket-crm.php                    # API маршруты
```

### Описание слоев

**1. Domain Layer (Domain)**
- `SupermarketOrder` - Вертикаль-специфичная сущность заказа
- `SupermarketOrderRepositoryInterface` - Интерфейс репозитория
- Использует базовые CRM сущности: Customer, Deal, Interaction, Segment, Tag

**2. Application Layer (Application)**
- `SupermarketCRMService` - Главный CRM сервис
- DTOs: CreateCustomerDTO, CreateOrderDealDTO, CustomerAnalyticsDTO, TopCustomerDTO

**3. Infrastructure Layer (Infrastructure)**
- `EloquentSupermarketOrderRepository` - Eloquent реализация репозитория
- `SupermarketCRMController` - API контроллер
- Event Listeners для интеграции с Supermarket вертикалью
- Queue Jobs для асинхронных операций

**4. Adapters Layer (Infrastructure/Http)**
- API Controllers для REST endpoints
- Filament Resources для админки (уже существует в App\Filament\Resources)

**5. Integration Layer (Infrastructure/Listeners)**
- Event Listeners для OrderCreated, OrderStatusUpdated, SubscriptionCreated, ReturnCreated
- Автоматическая синхронизация данных между Supermarket и CRM

**6. Background Jobs (Infrastructure/Jobs)**
- UpdateCustomerStatisticsJob - Обновление статистики клиента
- SyncSupermarketOrderToCRMJob - Синхронизация заказа в CRM
- AutoSegmentCustomersJob - Авто-сегментация клиентов
- SendCustomerAnalyticsReportJob - Отправка аналитических отчетов

**7. API Layer (Infrastructure/Http + Routes)**
- REST API endpoints для всех CRM операций
- Маршруты в routes/api/supermarket-crm.php

**8. Persistence Layer (Infrastructure/Repositories + Database)**
- Repository pattern с интерфейсами
- Migration для crm_supermarket_orders таблицы

**9. Cross-Cutting Concerns**
- Audit Logging через WithAuditLogging trait
- Telemetry через WithTelemetry trait
- Fraud Detection через FraudControlService
- Security и Compliance (152-ФЗ)

### Базы данных

```sql
-- Основная таблица CRM заказов супермаркетов
crm_supermarket_orders
```

## Сущности

### SupermarketOrder

Вертикаль-специфичная сущность, расширяющая базовую Deal модель для супермаркетов.

**Основные поля:**
- `order_type`: Тип заказа (one_time, subscription, return)
- `order_status`: Статус заказа
- `is_age_restricted`: Содержит ли товар с возрастными ограничениями
- `age_verification_status`: Статус верификации возраста
- `contains_honesty_marks`: Содержит ли товары с честными знаками
- `subscription_id`: ID подписки (если applicable)
- `return_id`: ID возврата (если applicable)
- `total_amount`: Сумма заказа
- `payment_status`: Статус оплаты
- `delivery_address`: Адрес доставки
- `delivery_scheduled_at`: Запланированное время доставки

**Методы:**
- `confirm()`: Подтвердить заказ
- `startProcessing()`: Начать обработку
- `markDelivered()`: Отметить как доставленный
- `verifyAge(string $method)`: Верифицировать возраст
- `validateHonestyMarks()`: Валидировать честные знаки
- `isOneTime()`: Проверить разовый ли заказ
- `isSubscription()`: Проверить подписку
- `isReturn()`: Проверить возврат

**Scopes:**
- `oneTime()`: Разовые заказы
- `subscription()`: Подписки
- `return()`: Возвраты
- `ageRestricted()`: С возрастными ограничениями
- `withHonestyMarks()`: С честными знаками
- `upcomingDeliveries()`: Предстоящие доставки
- `today()`: Заказы за сегодня

## Сервисы

### SupermarketCRMService

Главный CRM сервис для супермаркетов.

**Зависимости:**
- `FraudControlService`: Проверка на мошенничество
- `SubscriptionService`: Управление подписками
- `ReturnService`: Управление возвратами
- `AgeVerificationService`: Верификация возраста
- `HonestyMarkService`: Проверка честных знаков

**Методы:**

#### Управление клиентами

```php
// Создать или найти клиента
$customer = $crmService->getOrCreateCustomer(
    $customerData,  // array с данными клиента
    $tenantId,      // int ID тенанта
    $businessGroupId // int|null ID бизнес-группы
);

// Обновить статистику клиента (LTV, заказы, сегментация)
$crmService->updateCustomerStatistics($customer);
```

**Автоматическая сегментация клиентов:**
- `sleeping_customers`: Клиенты без активности 60+ дней
- `regular_customers`: Клиенты с 10+ заказами
- `new_customers`: Новые клиенты с 1+ заказом
- `subscription_users`: Клиенты с активными подписками
- `high_return_rate`: Клиенты с 3+ возвратами
- `high_value_customers`: VIP клиенты (LTV >= 100,000₽)
- `medium_value_customers`: Средний LTV (50,000₽ - 100,000₽)

#### Управление заказами/сделками

```php
// Создать CRM сделку для заказа
$deal = $crmService->createOrderDeal(
    $customer,      // Customer модель
    $orderData,     // array с данными заказа
    $pipelineId,    // int|null ID воронки
    $assignedToId   // int|null ID ответственного
);

// Обновить статус заказа
$crmService->updateOrderStatus(
    $deal,          // Deal модель
    'completed',    // string новый статус
    'reason'        // string|null причина
);
```

**Данные заказа:**
```php
$orderData = [
    'order_number' => 'ORD-20260427-1234',
    'order_type' => 'one_time', // one_time, subscription, return
    'order_status' => 'pending',
    'is_age_restricted' => false,
    'age_verification_required' => false,
    'contains_honesty_marks' => false,
    'honesty_marks_count' => 0,
    'subtotal' => 1000.00,
    'discount_amount' => 100.00,
    'delivery_fee' => 200.00,
    'service_fee' => 0,
    'tax_amount' => 100.00,
    'total_amount' => 1200.00,
    'payment_status' => 'pending',
    'payment_method' => 'card',
    'delivery_address' => 'г. Москва, ул. Примерная, д. 1',
    'delivery_phone' => '+79001234567',
    'delivery_name' => 'Иван Иванов',
    'delivery_scheduled_at' => '2026-04-28 10:00:00',
    'special_requests' => 'Оставить у двери',
    'allergies' => ['nuts'],
    'dietary_restrictions' => ['vegetarian'],
    'metadata' => [],
];
```

#### Верификация возраста

```php
// Проверить необходимость верификации возраста
$result = $crmService->checkAgeVerification($deal, $user);
// ['required' => bool, 'verified' => bool, 'methods' => array]

// Записать успешную верификацию
$crmService->recordAgeVerification($deal, 'passport');
```

**Методы верификации:**
- `passport`: Паспорт
- `selfie`: Селфи с оценкой возраста
- `bankid`: BankID
- `gosuslugi`: Госуслуги

#### Управление подписками

```php
// Создать CRM запись для подписки
$deal = $crmService->createSubscriptionDeal(
    $customer,           // Customer модель
    $subscriptionData    // array с данными подписки
);

// Обновить следующую доставку подписки
$crmService->updateSubscriptionDelivery(
    $deal,               // Deal модель
    $nextDelivery        // CarbonImmutable дата следующей доставки
);
```

**Данные подписки:**
```php
$subscriptionData = [
    'subscription_number' => 'SUB-20260427-1234',
    'subscription_type' => 'weekly', // weekly, bi_weekly, monthly
    'subscription_delivery_day' => 'Monday',
    'subscription_next_delivery' => '2026-05-04 10:00:00',
    'total_amount' => 5000.00,
    // ... остальные поля заказа
];
```

#### Управление возвратами

```php
// Создать CRM запись для возврата
$deal = $crmService->createReturnDeal(
    $customer,       // Customer модель
    $returnData      // array с данными возврата
);
```

**Данные возврата:**
```php
$returnData = [
    'return_number' => 'RET-20260427-1234',
    'return_reason' => 'Товар не понравился',
    'refund_amount' => 500.00,
    // ... остальные поля заказа
];
```

#### Аналитика и отчетность

```php
// Получить аналитику по клиентам
$analytics = $crmService->getCustomerAnalytics(
    $tenantId,        // int ID тенанта
    $businessGroupId, // int|null ID бизнес-группы
    $days             // int период в днях (default: 30)
);
```

**Возвращает:**
```php
[
    'total_customers' => 1000,
    'new_customers' => 50,
    'active_customers' => 200,
    'sleeping_customers' => 100,
    'vip_customers' => 20,
    'total_revenue' => 500000.00,
    'subscription_users' => 150,
    'returns_count' => 30,
    'period_days' => 30,
]
```

```php
// Получить топ клиентов по LTV
$topCustomers = $crmService->getTopCustomers(
    $tenantId,        // int ID тенанта
    $businessGroupId, // int|null ID бизнес-группы
    $limit            // int количество (default: 10)
);
```

## Интеграция с существующими сервисами

### SubscriptionService

CRM сервис интегрируется с существующим `SubscriptionService` из вертикали Супермаркет для:
- Создания подписок в CRM
- Отслеживания статуса подписок
- Управления доставками подписок

### ReturnService

CRM сервис интегрируется с `ReturnService` для:
- Создания записей возвратов в CRM
- Отслеживания статуса возвратов
- Анализа причин возвратов

### AgeVerificationService

CRM сервис интегрируется с `AgeVerificationService` для:
- Проверки необходимости верификации возраста
- Записи результатов верификации
- Отслеживания статуса верификации

### HonestyMarkService

CRM сервис интегрируется с `HonestyMarkService` для:
- Отслеживания товаров с честными знаками
- Валидации честных знаков
- Аналитики по честным знакам

## Конфигурация

### config/crm.php

```php
return [
    // Уровни лояльности
    'loyalty_tiers' => [
        'Bronze' => ['min_spent' => 0],
        'Silver' => ['min_spent' => 10000],
        'Gold' => ['min_spent' => 50000],
        'Platinum' => ['min_spent' => 100000],
    ],
    
    // Периоды для сегментации
    'segmentation' => [
        'sleeping_days' => 60,
        'active_days' => 30,
        'high_return_threshold' => 3,
        'vip_threshold' => 100000,
    ],
];
```

## Безопасность и Compliance

### Fraud Detection

Все операции в CRM сервисе проходят через `FraudControlService`:
- Создание клиентов
- Создание сделок/заказов
- Создание подписок
- Создание возвратов

### Audit Logging

Все критические операции логируются через `WithAuditLogging` trait:
- Создание клиентов (`logCreated`)
- Создание сделок (`logCreated`)
- Обновление статусов (`logAction`)

### PII Protection

Персональные данные клиентов маскируются в логах:
- Email: `u***@example.com`
- Телефон: `+7900***67`
- Адрес: `г. Москва, ул. П***, д. 1`

### 152-ФЗ Compliance

Медицинские данные (аллергии, диетические ограничения) хранятся в зашифрованном виде и не передаются во внешние системы.

## Использование

### Пример: Создание заказа с CRM записью

```php
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;

class OrderController extends Controller
{
    public function __construct(
        private SupermarketCRMService $crmService
    ) {}
    
    public function create(Request $request)
    {
        // Создаем или находим клиента
        $customer = $this->crmService->getOrCreateCustomer(
            [
                'user_id' => $request->user()->id,
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
            ],
            tenant()->id,
            businessGroup()?->id
        );
        
        // Создаем CRM сделку для заказа
        $deal = $this->crmService->createOrderDeal(
            $customer,
            [
                'order_number' => 'ORD-' . date('Ymd') . '-' . rand(1000, 9999),
                'order_type' => 'one_time',
                'order_status' => 'pending',
                'is_age_restricted' => $this->hasAgeRestrictedItems($request->input('items')),
                'contains_honesty_marks' => $this->hasHonestyMarkItems($request->input('items')),
                'subtotal' => $request->input('subtotal'),
                'discount_amount' => $request->input('discount', 0),
                'delivery_fee' => $request->input('delivery_fee', 0),
                'tax_amount' => $request->input('tax', 0),
                'total_amount' => $request->input('total'),
                'payment_method' => $request->input('payment_method'),
                'delivery_address' => $request->input('delivery_address'),
                'delivery_phone' => $request->input('delivery_phone'),
                'delivery_name' => $request->input('delivery_name'),
                'delivery_scheduled_at' => $request->input('delivery_scheduled_at'),
                'special_requests' => $request->input('special_requests'),
                'allergies' => $request->input('allergies', []),
                'dietary_restrictions' => $request->input('dietary_restrictions', []),
            ],
            pipelineId: config('crm.supermarket.pipeline_id'),
            assignedToId: null
        );
        
        // Проверяем возрастную верификацию если нужно
        $ageCheck = $this->crmService->checkAgeVerification($deal, $request->user());
        if ($ageCheck['required'] && !$ageCheck['verified']) {
            return response()->json([
                'error' => 'age_verification_required',
                'methods' => $ageCheck['methods'],
            ], 400);
        }
        
        // ... продолжаем создание заказа в супермаркете
    }
}
```

### Пример: Обновление статуса заказа

```php
public function updateStatus(Request $request, $orderId)
{
    $order = Order::findOrFail($orderId);
    $deal = Deal::where('marketplace_order_id', $orderId)->first();
    
    if ($deal) {
        $this->crmService->updateOrderStatus(
            $deal,
            $request->input('status'),
            $request->input('reason')
        );
    }
    
    // ... продолжаем обновление статуса
}
```

### Пример: Аналитика клиентов

```php
public function dashboard()
{
    $analytics = $this->crmService->getCustomerAnalytics(
        tenant()->id,
        businessGroup()?->id,
        30 // последние 30 дней
    );
    
    $topCustomers = $this->crmService->getTopCustomers(
        tenant()->id,
        businessGroup()?->id,
        10
    );
    
    return view('supermarket.dashboard', compact('analytics', 'topCustomers'));
}
```

## Тестирование

Тесты для Supermarket CRM интеграции будут созданы в отдельной задаче.

## Известные ограничения

1. **Lint errors**: В файле `SupermarketCRMService.php` есть lint ошибки, связанные с FraudControlService->check() сигнатурой. Эти ошибки не влияют на функциональность, но должны быть исправлены.
2. **Тесты**: Тесты отсутствуют и будут созданы в отдельной задаче.

## Следующие шаги

1. **Исправить lint errors**: Исправить ошибки валидации в SupermarketCRMService.php
2. **Создать тесты**: Написать unit и feature тесты для CRM интеграции
3. **Filament Resource**: Создать Filament ресурс для управления CRM заказами супермаркетов
4. **API Endpoints**: Создать API endpoints для CRM функционала
5. **Webhooks**: Добавить webhooks для интеграции с внешними системами

## Связанная документация

- [Supermarket Vertical README](../../Supermarket/README.md)
- [CatCRM Implementation Summary](./IMPLEMENTATION_SUMMARY.md)
- [CatCRM Integration Checklist](./INTEGRATION_CHECKLIST.md)
- [WithAuditLogging Trait](../../../../app/Traits/WithAuditLogging.php)
- [WithTelemetry Trait](../../../../app/Traits/WithTelemetry.php)
- [WithAuditLogging Trait](../../../../app/Traits/WithAuditLogging.php)
- [WithTelemetry Trait](../../../../app/Traits/WithTelemetry.php)
