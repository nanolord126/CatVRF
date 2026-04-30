# CatCRM — Модульная CRM-система для CatVRF

**Версия:** 1.0  
**Статус:** Production Ready  
**Авторы:** CatVRF Team

## Описание

CatCRM — это полноценная, модульная CRM-система, интегрированная в маркетплейс CatVRF. Система автоматически адаптируется под вертикали бизнеса (гостиницы, флористы, бьюти-салоны, такси и др.) и обеспечивает seamless интеграцию с заказами маркетплейса.

## Ключевые возможности

### Core CRM
- **Воронки продаж (Pipelines)** — гибкие настраиваемые воронки для каждого бизнеса
- **Этапы (Stages)** — настраиваемые этапы с вероятностью закрытия
- **Сделки (Deals)** — управление лидами, заказами и сделками
- **Клиенты (Customers)** — единая клиентская база с историей и LTV
- **Задачи (Tasks)** — планирование действий для менеджеров
- **Взаимодействия (Interactions)** — история всех коммуникаций
- **Теги и сегменты** — гибкая сегментация клиентов

### Вертикальные модули
- **Hotels** — бронирования, заезд/выезд, допуслуги
- **Beauty** — записи, мастера, услуги
- **Flowers** — заказы букетов, дизайн, доставка
- **Taxi** — поездки, водители, диспетчеризация

### Интеграции
- **Автоматическая синхронизация** — заказы маркетплейса автоматически создают сделки
- **Платежи** — обновление статуса сделки при оплате
- **Доставка** — отслеживание статусов доставки
- **Аналитика** — статистика по воронкам и клиентам

## Архитектура

```
modules/CatCRM/
├── Domain/
│   ├── Entities/          # Core сущности (Pipeline, Stage, Deal, Customer, Task)
│   ├── Verticals/         # Вертикальные модули (Hotels, Beauty, Flowers, Taxi)
│   └── Enums/             # Перечисления (DealStatus, TaskPriority, etc.)
├── Application/
│   └── Services/          # Application Services (DealService, CustomerService, TaskService)
└── Infrastructure/
    └── (будет добавлено)
```

## Установка

### 1. Запустить миграции

```bash
php artisan migrate
```

Будут созданы таблицы:
- `crm_pipelines` — воронки
- `crm_stages` — этапы воронок
- `crm_customers` — клиенты
- `crm_deals` — сделки
- `crm_tasks` — задачи
- `crm_interactions` — взаимодействия
- `crm_tags` — теги
- `crm_segments` — сегменты
- `crm_*_tags` — связующие таблицы
- `crm_hotel_bookings` — бронирования гостиниц
- `crm_flower_orders` — заказы флористов
- `crm_beauty_appointments` — записи бьюти
- `crm_taxi_rides` — поездки такси

### 2. Настройка конфигурации

Конфигурация находится в `config/crm.php`:

```php
'enabled' => env('CRM_ENABLED', true),

'verticals' => [
    'beauty', 'hotel', 'flowers', 'taxi', ...
],

'pipelines' => [
    'hotels' => [...],
    'beauty' => [...],
    'flowers' => [...],
    'taxi' => [...],
    'default' => [...],
],

'automations' => [
    'hotels' => [...],
    'beauty' => [...],
    ...
],
```

### 3. Регистрация Event Listeners

В `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\OrderCreated::class => [
        \App\Listeners\CRM\OrderCreatedListener::class,
    ],
    \App\Events\OrderPaid::class => [
        \App\Listeners\CRM\OrderPaidListener::class,
    ],
];
```

## Использование

### Создание сделки

```php
use Modules\CatCRM\Application\Services\DealService;

$dealService = new DealService($correlationId);

$deal = $dealService->createDeal([
    'tenant_id' => $tenantId,
    'pipeline_id' => $pipelineId,
    'customer_id' => $customerId,
    'title' => 'Новая сделка',
    'value' => 10000,
    'source' => 'marketplace',
]);
```

### Создание клиента

```php
use Modules\CatCRM\Application\Services\CustomerService;

$customerService = new CustomerService($correlationId);

$customer = $customerService->createCustomer([
    'tenant_id' => $tenantId,
    'first_name' => 'Иван',
    'last_name' => 'Иванов',
    'email' => 'ivan@example.com',
    'phone' => '+79001234567',
    'type' => CustomerType::Individual,
]);
```

### Создание задачи

```php
use Modules\CatCRM\Application\Services\TaskService;

$taskService = new TaskService($correlationId);

$task = $taskService->createCallReminder([
    'tenant_id' => $tenantId,
    'customer_id' => $customerId,
    'assigned_to_id' => $userId,
    'due_date' => now()->addDay(),
]);
```

### Перемещение сделки по воронке

```php
$stage = Stage::find($stageId);
$deal = $dealService->moveDealToStage($deal, $stage, 'Клиент подтвердил');
```

### Автоматическое создание сделки из заказа

Сделка создается автоматически при событии `OrderCreated` через Event Listener.

## Вертикальные модули

### Hotels (Гостиницы)

```php
use Modules\CatCRM\Domain\Verticals\Hotels\HotelBooking;

$booking = HotelBooking::create([
    'deal_id' => $deal->id,
    'customer_id' => $customer->id,
    'check_in_date' => '2026-05-01',
    'check_out_date' => '2026-05-03',
    'room_type' => 'Standard',
    'guests_count' => 2,
]);

$booking->checkIn('KEY12345');
$booking->checkOut();
```

### Beauty (Бьюти-салоны)

```php
use Modules\CatCRM\Domain\Verticals\Beauty\BeautyAppointment;

$appointment = BeautyAppointment::create([
    'deal_id' => $deal->id,
    'customer_id' => $customer->id,
    'master_id' => $masterId,
    'appointment_date' => now()->addDay(),
    'service_type' => 'Стрижка',
]);

$appointment->confirm();
$appointment->complete(['шампунь', 'лак']);
```

### Flowers (Флористы)

```php
use Modules\CatCRM\Domain\Verticals\Flowers\FlowerOrder;

$order = FlowerOrder::create([
    'deal_id' => $deal->id,
    'customer_id' => $customer->id,
    'bouquet_type' => 'Розы',
    'delivery_date' => now()->addDay(),
    'recipient_name' => 'Анна Петрова',
]);

$order->markAsDesigned();
$order->markAsDelivered('https://example.com/photo.jpg');
```

### Taxi (Такси)

```php
use Modules\CatCRM\Domain\Verticals\Taxi\TaxiRideCrm;

$ride = TaxiRideCrm::create([
    'deal_id' => $deal->id,
    'customer_id' => $customer->id,
    'pickup_address' => 'Улица Пушкина, 10',
    'dropoff_address' => 'Улица Ленина, 20',
]);

$ride->assignDriver($driverId);
$ride->startRide();
$ride->completeRide(500, 5, 'Отличная поездка');
```

## Автоматизации

Система поддерживает автоматизации для каждой вертикали:

### Hotels
- Напоминание о заезде (24 часа до)
- Напоминание о выезде (2 часа до)

### Beauty
- Напоминание о записи (2 часа до)
- Follow-up через 7 дней после услуги

### Flowers
- Уведомление о доставке
- Фотоотчет через час после доставки

### Taxi
- Назначение водителя (timeout 5 мин)
- Уведомление о ETA

## API Endpoints

(Будут добавлены в следующей версии)

## Тестирование

```bash
# Запустить все тесты CRM
php artisan test --filter=CatCRM

# Запустить конкретный тест
php artisan test --filter=DealServiceTest
```

## Мониторинг

CRM интегрирована с системой мониторинга CatVRF:
- OpenTelemetry трассировка
- Prometheus метрики
- Логи в ClickHouse

## Безопасность

- Все данные изолированы по tenant_id
- Шифрование PII данных (email, phone, address)
- Audit лог для всех операций
- Fraud detection интеграция

## Performance

- Оптимизированные запросы с eager loading
- Кэширование с Cache::tags
- Queue для тяжелых операций
- Индексы на всех ключевых полях

## Roadmap

- [ ] Filament 3/4 ресурсы для Tenant Panel
- [ ] Livewire компоненты (Kanban, Calendar)
- [ ] API Endpoints
- [ ] Webhooks для внешних интеграций
- [ ] Advanced аналитика (ClickHouse)
- [ ] AI-рекомендации (следующая лучшая задача)
- [ ] Voice integration (запись звонков)
- [ ] Email integration (синхронизация писем)

## Поддержка

Для вопросов и поддержки:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Документация: https://catvrf.ru/docs/crm
