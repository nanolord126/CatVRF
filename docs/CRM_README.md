# CatCRM — Модульная CRM-система CatVRF

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Статус:** Production Ready  

## Описание

CatCRM — единая, модульная и глубоко интегрированная CRM-система, которая работает внутри каждого Tenant (бизнеса) как его личный инструмент. Система автоматически адаптируется под вертикали (гостиницы, флористы, бьюти, такси и любые другие услуги) и поддерживает воронки продаж разной сложности — от простой записи до полной цепочки «заказ → производство → курьерская доставка → постпродажное обслуживание».

## Архитектура

### Модульная структура

```
app/Domains/CRM/
├── Models/              # Core модели
│   ├── CrmPipeline.php  # Воронка
│   ├── CrmStage.php     # Этап воронки
│   ├── CrmDeal.php      # Сделка/лид
│   ├── CrmTask.php      # Задача
│   ├── CrmCustomer.php  # Клиент
│   └── ...              # Вертикальные профили
├── Services/            # Бизнес-логика
│   ├── PipelineService.php
│   ├── DealService.php
│   ├── TaskService.php
│   ├── OrderToCrmIntegrationService.php
│   └── ...
├── DTOs/                # Data Transfer Objects
│   ├── CreatePipelineDto.php
│   ├── CreateDealDto.php
│   └── CreateTaskDto.php
├── Events/              # События
│   └── OrderCreatedForCrm.php
├── Listeners/           # Слушатели событий
│   └── CreateDealFromOrder.php
├── Filament/            # Filament ресурсы
│   └── Resources/
│       ├── CrmPipelineResource.php
│       ├── CrmDealResource.php
│       └── CrmTaskResource.php
└── Providers/
    └── CrmServiceProvider.php
```

### Core сущности

#### CrmPipeline (Воронка)
- Определяет этапы (Stages) для конкретной вертикали
- Привязана к tenant и бизнес_group
- Поддерживает несколько воронок на один tenant
- Автоматическое создание default воронок для вертикалей

#### CrmStage (Этап)
- Этап воронки с вероятностью закрытия
- Поддерживает auto-transition правила
- Может быть финальным (won/lost)
- Временные лимиты для этапов

#### CrmDeal (Сделка)
- Основная единица CRM
- Связывается с заказами маркетплейса
- Поддерживает перемещение между этапами
- Автоматический расчёт статистики

#### CrmTask (Задача)
- Задачи для сотрудников
- Привязка к сделкам/клиентам
- Типы: call, email, meeting, follow_up, document, payment, delivery
- Приоритеты: low, medium, high, urgent

#### CrmCustomer (Клиент)
- Универсальная модель клиента
- Поддержка вертикальных профилей
- Система лояльности (bronze → diamond)
- История взаимодействий

## Вертикальная адаптация

### Гостиницы (hotels)

**Воронка:** Lead → Qualification → Booking → Check-in → Stay → Check-out

**Автоматизации:**
- Напоминания о заезде (48ч, 24ч, 2ч до)
- Напоминания о выезде (2ч до)
- Запрос отзыва (24ч после выезда)
- Реактивация (90/180 дней без визита)
- Предложения апгрейда номера

**Пример использования:**
```php
$pipeline = app(PipelineService::class)->initializeDefaultPipeline($tenantId, 'hotels');
$deal = app(DealService::class)->createDealFromOrder(
    tenantId: $tenantId,
    businessGroupId: null,
    customerId: $customerId,
    orderId: $orderId,
    orderTitle: 'Бронирование №123',
    orderValue: 15000,
    vertical: 'hotels',
);
```

### Бьюти-салоны (beauty)

**Воронка:** Запись → Подтверждение → Услуга → Завершено

**Автоматизации:**
- Напоминания о записи (24ч, 2ч до)
- Follow-up (7 дней после)
- Предложение абонемента

### Флористика (flowers)

**Воронка:** Заказ → Дизайн → Сборка → Доставка → Доставлено

**Автоматизации:**
- Трекинг доставки
- Фотоотчёт (1ч после доставки)
- Follow-up (3 дня после)

### Такси (taxi)

**Воронка:** Новый заказ → Назначение водителя → Подача → Поездка → Завершено

**Автоматизации:**
- Уведомление водителю
- ETA уведомление пассажиру
- Запрос отзыва (1ч после)

## Интеграция с CatVRF

### Заказы → Сделки

Автоматическое создание сделок из заказов маркетплейса:

```php
app(OrderToCrmIntegrationService::class)->handleNewOrder(
    orderId: $orderId,
    tenantId: $tenantId,
    businessGroupId: $businessGroupId,
    customerId: $customerId,
    orderTitle: $orderTitle,
    orderValue: $orderValue,
    vertical: $vertical,
    correlationId: $correlationId,
);
```

**Конфигурация (config/crm.php):**
```php
'integration' => [
    'orders' => [
        'auto_create_deals' => env('CRM_AUTO_CREATE_DEALS_FROM_ORDERS', true),
        'sync_on_order_status_change' => env('CRM_SYNC_ON_ORDER_STATUS_CHANGE', true),
        'sync_on_order_payment' => env('CRM_SYNC_ON_ORDER_PAYMENT', true),
    ],
],
```

### Статусы заказов → Статусы сделок

| Статус заказа | Статус сделки |
|--------------|--------------|
| completed/delivered | won |
| cancelled/refunded | lost |
| processing/confirmed | in_progress |

## Filament Ресурсы

### CrmPipelineResource
Управление воронками:
- Создание/редактирование воронок
- Настройка этапов
- Вертикальная привязка
- Цветовая кодировка

### CrmDealResource
Управление сделками:
- Kanban-вид по этапам
- Фильтры по статусу, воронке, исполнителю
- Drag & Drop перемещение между этапами
- Связь с клиентами и заказами

### CrmTaskResource
Управление задачами:
- Создание задач разных типов
- Привязка к сделкам/клиентам
- Приоритеты и сроки
- Отслеживание просроченных задач

## Настройка новой вертикали

### 1. Добавить в config/crm.php

```php
'pipelines' => [
    'new_vertical' => [
        'name' => 'Новая вертикаль',
        'stages' => [
            ['name' => 'Этап 1', 'order' => 1, 'probability' => 20],
            ['name' => 'Этап 2', 'order' => 2, 'probability' => 50],
            ['name' => 'Завершено', 'order' => 3, 'probability' => 100, 'is_won_stage' => true],
        ],
    ],
],
```

### 2. Добавить в PipelineService::initializeDefaultPipeline()

```php
'new_vertical' => [
    ['name' => 'Этап 1', 'key' => 'stage_1', 'order' => 1, 'probability' => 20],
    ['name' => 'Этап 2', 'key' => 'stage_2', 'order' => 2, 'probability' => 50],
    ['name' => 'Завершено', 'key' => 'completed', 'order' => 3, 'probability' => 100, 'is_won' => true],
],
```

### 3. Создать вертикальный профиль клиента (опционально)

```php
// app/Domains/CRM/Models/CrmNewVerticalProfile.php
final class CrmNewVerticalProfile extends Model
{
    protected $table = 'crm_new_vertical_profiles';
    
    protected $fillable = [
        'crm_client_id',
        'tenant_id',
        // вертикаль-специфичные поля
    ];
}
```

## API Примеры

### Создание воронки

```php
$pipeline = app(PipelineService::class)->createPipeline(
    new CreatePipelineDto(
        tenantId: $tenantId,
        businessGroupId: null,
        name: 'Custom Pipeline',
        slug: 'custom-pipeline',
        vertical: 'default',
    )
);
```

### Создание сделки

```php
$deal = app(DealService::class)->createDeal(
    new CreateDealDto(
        tenantId: $tenantId,
        businessGroupId: null,
        pipelineId: $pipelineId,
        stageId: $stageId,
        customerId: $customerId,
        title: 'New Deal',
        value: 50000,
        status: 'new',
    )
);
```

### Перемещение сделки

```php
$deal = app(DealService::class)->moveDealToStage(
    dealId: $dealId,
    stageId: $newStageId,
    reason: 'Customer confirmed',
);
```

### Создание задачи

```php
$task = app(TaskService::class)->createTask(
    new CreateTaskDto(
        tenantId: $tenantId,
        businessGroupId: null,
        dealId: $dealId,
        customerId: $customerId,
        assignedToId: $userId,
        createdById: $userId,
        title: 'Follow up call',
        type: 'call',
        status: 'pending',
        priority: 'high',
        dueDate: now()->addDay()->toDateTimeString(),
    )
);
```

## Тестирование

### Запуск тестов

```bash
# Все CRM тесты
./vendor/bin/pest tests/Unit/CRM/
./vendor/bin/pest tests/Feature/CRM/

# Конкретный тест
./vendor/bin/pest tests/Unit/CRM/PipelineServiceUnitTest.php
```

### Покрытие тестами

- **PipelineService:** Создание воронок, этапы, инициализация по вертикалям
- **DealService:** Создание сделок, интеграция с заказами, перемещение между этапами
- **TaskService:** Создание задач, завершение, follow-up задачи

## Конфигурация

### .env переменные

```env
CRM_ENABLED=true
CRM_AUTO_CREATE_DEALS_FROM_ORDERS=true
CRM_SYNC_ON_ORDER_STATUS_CHANGE=true
CRM_SYNC_ON_ORDER_PAYMENT=true
CRM_AUTO_UPDATE_DEAL_ON_PAYMENT=true
CRM_SYNC_TO_CLICKHOUSE=true
CRM_AUTOMATION_ENABLED=true
CRM_AUTOMATION_QUEUE=crm
```

## Чек-лист интеграции с существующими модулями

- [x] **KYB:** Интеграция через business_group_id
- [x] **Платежи:** Обновление статуса сделки при оплате (OrderToCrmIntegrationService)
- [x] **Доставка:** Связь через dealable полиморфные отношения
- [x] **Аналитика:** Синхронизация с ClickHouse (конфигурируемо)
- [x] **Behavioral Biometrics:** Доступ через fraud check в сервисах
- [x] **Fraud Detection:** FraudControlService во всех публичных методах
- [x] **Device Management:** Связь через metadata в сделках

## Production Checklist

- [ ] Миграции выполнены на production
- [ ] CrmServiceProvider зарегистрирован в config/app.php
- [ ] Очереди настроены (crm, crm-automations, crm-segments, crm-notifications)
- [ ] Horizon настроен для мониторинга очередей
- [ ] ClickHouse интеграция включена (если используется)
- [ ] Вертикальные воронки настроены для всех бизнесов
- [ ] Автоматизации протестированы
- [ ] Мониторинг и алерты настроены
- [ ] Резервное копирование crm_* таблиц настроено

## Поддерживаемые вертикали

- ✅ Гостиницы (hotels)
- ✅ Бьюти-салоны (beauty)
- ✅ Флористика (flowers)
- ✅ Такси/таксопарки (taxi)
- ✅ Автомобили (auto)
- ✅ Еда (food)
- ✅ Мебель (furniture)
- ✅ Мода (fashion)
- ✅ Фитнес (fitness)
- ✅ Недвижимость (real_estate)
- ✅ Медицина (medical)
- ✅ Образование (education)
- ✅ Путешествия (travel)
- ✅ Зоотовары (pet)
- ✅ Электроника (electronics)
- ✅ Мероприятия (events)
- ✅ Универсальная (default)

## Лицензия

Proprietary — CatVRF 2026

---

**Канон CatVRF 2026 — PRODUCTION MANDATORY**
