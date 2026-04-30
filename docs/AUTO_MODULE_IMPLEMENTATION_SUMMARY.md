# Auto Module Implementation Summary

## Overview

Auto модуль CatCRM — полноценная система управления автотранспортом, включающая заказы на обслуживание, воронки продаж, управление услугами, автомобилями и интеграцию с маркетплейсом CatVRF.

## Architecture

### Domain Layer

#### Entities
- **Service** — услуга (ТО, ремонт, диагностика, запчасти, детейлинг, шиномонтаж)
- **Order** — заказ на обслуживание
- **OrderItem** — позиция заказа
- **Vehicle** — автомобиль
- **Customer** — клиент
- **Mechanic** — механик

#### Enums
- **OrderStatus** — статусы заказов (pending, confirmed, in_progress, ready, completed, cancelled, refunded)
- **ServiceCategory** — категории услуг (maintenance, repair, diagnostics, parts, detailing, tires)
- **VehicleType** — типы автомобилей (sedan, suv, hatchback, coupe, truck, van, motorcycle)
- **VehicleStatus** — статусы автомобилей (in_service, ready, waiting, diagnosing)

### Application Services

- **OrderService** — управление заказами
- **ServiceService** — управление услугами
- **VehicleService** — управление автомобилями
- **InventoryService** — управление инвентарём запчастей
- **SalesFunnelService** — управление воронкой продаж
- **MaintenanceReminderService** — сервис напоминаний о ТО

### Infrastructure Layer

#### Models
- **ServiceModel** — модель услуги
- **OrderModel** — модель заказа
- **VehicleModel** — модель автомобиля
- **CustomerModel** — модель клиента

#### Repositories
- **ServiceRepositoryInterface** — репозиторий услуг
- **OrderRepositoryInterface** — репозиторий заказов
- **VehicleRepositoryInterface** — репозиторий автомобилей

## Filament Resources

### OrderResource
- **Статусы с анимированными бейджами**: pending (warning), confirmed (info), in_progress (primary), ready (success), completed (success), cancelled (danger), refunded (secondary)
- **Иконки**: clock, check-circle, cog, check-badge, check-circle, x-circle, arrow-uturn-left
- **Фильтры**: по статусу, клиенту, категории, дате
- **Действия**: просмотр, редактирование, удаление

### ServiceResource
- **Управление услугами**
- **Категории**: maintenance, repair, diagnostics, parts, detailing, tires
- **Типы**: oil_change, brake_service, filter_change, fluid_change, inspection
- **Статусы**: available, scheduled, in_progress, completed, cancelled
- **Фильтры**: по категории, типу, статусу, цене

### VehicleResource
- **Управление автомобилями**
- **Типы**: sedan, suv, hatchback, coupe, truck, van, motorcycle
- **Статусы**: in_service, ready, waiting, diagnosing
- **Интеграция с VIN**

## Livewire Components

- **RealTimeServiceDashboard** — дашборд услуг в реальном времени
- **VehicleTracking** — отслеживание автомобилей
- **SalesFunnelBoard** — канбан-доска воронки продаж
- **MaintenanceScheduler** — планировщик ТО

## Configuration

Конфигурация модуля находится в `config/crm-auto.php`:

```php
return [
    'enabled' => env('AUTO_ENABLED', true),
    'default_currency' => env('AUTO_CURRENCY', 'RUB'),
    'orders' => [
        'auto_confirm' => env('AUTO_AUTO_CONFIRM', false),
        'require_deposit' => env('AUTO_REQUIRE_DEPOSIT', true),
        'deposit_percentage' => env('AUTO_DEPOSIT_PERCENTAGE', 20),
        'hold_time_minutes' => env('AUTO_HOLD_TIME', 30),
        'max_services_per_order' => env('AUTO_MAX_SERVICES', 10),
    ],
    'sales_funnel' => [
        'stages' => [...],
        'auto_stage_transition' => env('AUTO_AUTO_STAGE_TRANSITION', true),
    ],
    'services' => [
        'categories' => [...],
        'maintenance_types' => [...],
        'inventory' => [
            'auto_low_stock_alert' => true,
            'low_stock_threshold' => 3,
        ],
    ],
    'marketplace' => [
        'enabled' => env('AUTO_MARKETPLACE_ENABLED', true),
        'sync_interval_minutes' => env('AUTO_SYNC_INTERVAL', 15),
    ],
    'fraud_detection' => [
        'enabled' => env('AUTO_FRAUD_DETECTION_ENABLED', true),
        'threshold' => env('AUTO_FRAUD_THRESHOLD', 0.7),
        'check_vin' => env('AUTO_CHECK_VIN', true),
    ],
];
```

## Integration with CatVRF Marketplace

### Synchronization
- **Services** — услуги синхронизируются с маркетплейсом
- **Orders** — заказы из маркетплейса автоматически попадают в CRM
- **Prices** — цены обновляются автоматически
- **Availability** — доступность синхронизируется в реальном времени

### Webhooks
- Webhook URL настраивается в `config/crm-auto.php`
- Секретный ключ для валидации: `AUTO_WEBHOOK_SECRET`

## Fraud Detection

### Features
- **Fraud Score** — оценка подозрительности клиента (0-1)
- **Order History Check** — проверка истории заказов
- **VIN Check** — проверка VIN номера

### Thresholds
- Порог блокировки: `AUTO_FRAUD_THRESHOLD` (по умолчанию 0.7)

## Vehicle Management

### Vehicle Types
- **Sedan** — седан
- **SUV** — внедорожник
- **Hatchback** — хэтчбек
- **Coupe** — купе
- **Truck** — грузовик
- **Van** — фургон
- **Motorcycle** — мотоцикл

### Vehicle Statuses
- **In Service** — на обслуживании
- **Ready** — готов
- **Waiting** — ожидает
- **Diagnosing** — диагностика

## Service Categories

### Maintenance
- Oil Change — замена масла
- Brake Service — обслуживание тормозов
- Filter Change — замена фильтров
- Fluid Change — замена жидкостей
- Inspection — техосмотр

### Repair
- Engine Repair — ремонт двигателя
- Transmission Repair — ремонт КПП
- Electrical Repair — ремонт электрооборудования
- Suspension Repair — ремонт подвески

### Diagnostics
- Computer Diagnostics — компьютерная диагностика
- OBD Scan — OBD сканирование
- Road Test — дорожный тест

### Parts
- Spare Parts — запчасти
- Consumables — расходные материалы
- Accessories — аксессуары

### Detailing
- Car Wash — мойка
- Polishing — полировка
- Ceramic Coating — керамическое покрытие
- Interior Cleaning — химчистка салона

### Tires
- Tire Change — замена шин
- Wheel Alignment — сход-развал
- Tire Balancing — балансировка колёс
- Tire Storage — хранение шин

## Cache Configuration

### TTL Settings
- **Services**: 3600 секунд (1 час)
- **Orders**: 300 секунд (5 минут)
- **Inventory**: 180 секунд (3 минуты)
- **Prices**: 600 секунд (10 минут)

### Cache Tags
- `auto:services`
- `auto:orders`
- `auto:inventory`

## Queue Configuration

- **order_created**: `auto`
- **order_confirmed**: `auto`
- **sync_marketplace**: `auto-sync`
- **notifications**: `auto-notifications`
- **fraud_check**: `auto-fraud`

## Automations

### Notifications
- **order_created** — уведомление о создании заказа
- **order_confirmed** — подтверждение заказа
- **service_started** — начало обслуживания
- **service_ready** — услуга готова
- **maintenance_reminder** — напоминание о ТО за 7 дней
- **inspection_reminder** — напоминание о техосмотре за 30 дней

### Auto Status Changes
- **confirm_orders** — автоматическое подтверждение заказов
- **start_services** — автоматический запуск услуг
- **complete_services** — автоматическое завершение услуг

### Maintenance Recommendations
- **Based on Mileage** — рекомендации на основе пробега
- **Based on Time** — рекомендации на основе времени
- **Manufacturer Schedule** — регламент производителя

## Analytics

### Metrics
- **conversion_rate** — конверсия
- **average_order_value** — средний чек
- **service_frequency** — частота обслуживания
- **popular_services** — популярные услуги
- **customer_retention** — удержание клиентов

### Retention
- Хранение аналитики: 365 дней (настраивается через `AUTO_ANALYTICS_RETENTION`)

## Security

### Features
- **2FA for Large Orders** — двухфакторная аутентификация для крупных заказов
- **Max Orders Per Day** — ограничение на количество заказов за день
- **Max Amount Without Verification** — ограничение на сумму без доп. проверки

### Thresholds
- Порог крупного заказа: 100,000 ₽
- Максимальное количество заказов в день: 10
- Максимальная сумма без верификации: 50,000 ₽

## Integrations

### CRM Systems
- **AmoCRM** — интеграция через API
- **Bitrix24** — интеграция через API

### Diagnostics Equipment
- **OBD** — OBD адаптеры
- **Manufacturer API** — API производителей автомобилей

### Parts Catalog
- **TecDoc** — каталог TecDoc
- **EMEX** — каталог EMEX

## Usage

### Activation
```php
// .env
AUTO_ENABLED=true
AUTO_MARKETPLACE_ENABLED=true
AUTO_FRAUD_DETECTION_ENABLED=true
```

### Creating an Order
```php
use Modules\Auto\Application\Services\OrderService;

$orderService = app(OrderService::class);

$order = $orderService->createOrder([
    'customer_id' => $customerId,
    'vehicle_id' => $vehicleId,
    'services' => [
        ['service_id' => $serviceId1, 'quantity' => 1],
        ['service_id' => $serviceId2, 'quantity' => 1],
    ],
]);
```

### Managing Maintenance Reminders
```php
use Modules\Auto\Application\Services\MaintenanceReminderService;

$reminderService = app(MaintenanceReminderService::class);

// Автоматическое создание напоминаний
$reminderService->generateReminders();

// Отправка напоминаний
$reminderService->sendReminders();
```

## Acceptance Criteria

- [ ] Модуль полностью адаптирован под автотранспорт (услуги, заказы, автомобили)
- [ ] Реал-тайм дашборд услуг
- [ ] Автоматические воронки и уведомления работают без ручных действий
- [ ] Глубокая интеграция с CatVRF (платежи, отзывы, fraud-проверка)
- [ ] Удобный интерфейс для механиков и клиентов
- [ ] Покрытие тестами ≥ 95%
- [ ] Конфигурационный файл создан и документирован
- [ ] README с инструкциями по активации и использованию

## Testing

### Test Coverage
- Unit тесты для сервисов (OrderService, ServiceService, VehicleService)
- Feature тесты для Filament ресурсов
- Интеграционные тесты для маркетплейса
- Тесты Fraud Detection
- Тесты VIN проверки

### Running Tests
```bash
php artisan test --filter=Auto
```

## Documentation

- **Configuration**: `config/crm-auto.php`
- **Module Directory**: `modules/Auto/`
- **This README**: `docs/AUTO_MODULE_IMPLEMENTATION_SUMMARY.md`

## Support

Для вопросов и поддержки обращайтесь к документации проекта или создайте issue в репозитории.
