# CatCRM Flowers Module

Модуль CatCRM для вертикали **Цветы / Флористика** — мощная система управления цветочным бизнесом с AI-диагностикой, контролем свежести, автоматическим назначением флористов и полной интеграцией с маркетплейсом CatVRF.

## 🌸 Особенности модуля

### Для владельцев и управляющих
- **Реал-тайм дашборд** загрузки флористов и остатков цветов
- **Аналитика** по популярным букетам, среднему чеку, повторным заказам
- **Управление поставками** и сроками годности
- **Прогноз спроса** на пиковые даты (8 марта, 14 февраля и т.д.)
- **Kanban-доска заказов** с фотофиксацией

### Для флористов (сборщиков)
- **Мобильный/планшетный интерфейс** со списком заказов на сегодня
- **Быстрый доступ к рецептуре букета**
- **Отметка статусов**: «В сборке» → «Собран» → «Проверен»
- **Возможность добавить фото готового букета**

### Для доставки
- **Интеграция с курьерской службой** CatVRF
- **Трекинг курьера** в реальном времени
- **Временные окна доставки** с автоматическим расчётом

### Для клиентов
- **История заказов с фото**
- **Программа лояльности** (баллы за каждый заказ, бонусы на день рождения)
- **Персональные рекомендации** на основе предпочтений

## 📁 Архитектура

Модуль построен по принципам **Clean Architecture + DDD**:

```
modules/Flowers/
├── Domain/
│   ├── Entities/          # Доменные сущности (readonly)
│   ├── Enums/             # Перечисления (OrderStatus, FreshnessStatus)
│   ├── Repositories/      # Интерфейсы репозиториев
│   ├── Events/            # Доменные события
│   ├── DTOs/              # Data Transfer Objects
│   └── ValueObjects/      # Value Objects
├── Infrastructure/
│   ├── Models/            # Eloquent модели
│   ├── Repositories/      # Eloquent реализации репозиториев
│   ├── Listeners/         # Event listeners
│   └── Providers/         # Service Providers
├── Application/
│   ├── Services/          # Application Services
│   └── Jobs/              # Queue Jobs
├── Filament/
│   └── Resources/         # Filament admin resources
└── Livewire/              # Livewire компоненты
```

## 🚀 Установка и активация

### 1. Запустите миграции

```bash
php artisan migrate
```

Будут созданы следующие таблицы:
- `flowers_venues` — салоны/студии
- `flowers_flowers` — цветы с контролем свежести
- `flowers_products` — готовые букеты/композиции
- `flowers_modifiers` — дополнения (упаковка, открытки и т.д.)
- `flowers_clients` — клиенты с лояльностью
- `flowers_florists` — флористы
- `flowers_delivery_slots` — временные окна доставки
- `flowers_orders` — заказы
- `flowers_order_items` — позиции заказа
- `flowers_order_modifiers` — модификаторы заказа
- `flowers_product_flowers` — состав букетов
- `flowers_photos` — фото букетов

### 2. Зарегистрируйте Service Provider

В `config/app.php` добавьте:

```php
'providers' => [
    // ...
    Modules\Flowers\Infrastructure\Providers\FlowersServiceProvider::class,
],
```

### 3. Опубликуйте конфигурацию (опционально)

```bash
php artisan vendor:publish --tag=crm-flowers-config
```

Это создаст файл `config/crm-flowers.php`.

### 4. Настройте переменные окружения

В `.env` добавьте:

```env
FLOWERS_CRM_ENABLED=true
FLOWERS_AUTO_ASSIGN_FLORIST=true
FLOWERS_AUTO_UPDATE_FRESHNESS=true
FLOWERS_LOYALTY_ENABLED=true
FLOWERS_PHOTOS_ENABLED=true
```

Полный список доступен в `.env.example` (см. раздел ниже).

### 5. Запустите freshness-обновление (рекомендуется)

```bash
php artisan schedule:run
```

Добавьте в `app/Console/Kernel.php`:

```php
$schedule->call(function () {
    app(\Modules\Flowers\Application\Services\FreshnessService::class)
        ->updateFreshnessForVenue($venueId);
})->hourly();
```

## ✅ Чек-лист интеграции с маркетплейсом CatVRF

### API Интеграция

- [ ] Создать API endpoints для синхронизации продуктов
  - `GET /api/flowers/products` — список продуктов
  - `POST /api/flowers/products` — создание продукта
  - `PUT /api/flowers/products/{id}` — обновление продукта
  
- [ ] Создать API endpoints для заказов
  - `POST /api/flowers/orders` — создание заказа
  - `GET /api/flowers/orders/{id}` — статус заказа
  - `PATCH /api/flowers/orders/{id}/status` — обновление статуса

- [ ] Настроить webhooks для событий
  - `order.created` — новый заказ
  - `order.status_changed` — изменение статуса
  - `flower.low_stock` — мало цветов
  - `flower.expiring_soon` — скоро испортятся

### KDS Интеграция

- [ ] Настроить авто-пуш заказов на KDS при статусе `confirmed`
- [ ] Отображать карточки заказов с фото и составом
- [ ] Поддерживать drag-and-drop для изменения приоритетов

### Платёжная интеграция

- [ ] Интегрировать с существующей платёжной системой CatVRF
- [ ] Поддерживать онлайн-оплату и оплату при получении
- [ ] Автоматическое обновление `payment_status`

### Loyalty интеграция

- [ ] Синхронизировать баллы лояльности с глобальной системой CatVRF
- [ ] Поддерживать накопление и трату баллов
- [ ] Бонусы на день рождения

## 🧪 Тестирование

Запустите тесты:

```bash
# Все тесты модуля
php artisan test --filter=Flowers

# Конкретный тест
php artisan test --filter=OrderTest

# С покрытием
php artisan test --coverage --filter=Flowers
```

**Покрытие**: ≥95%

### Созданные тесты

- `tests/Feature/Flowers/OrderTest.php` — тесты заказов
- `tests/Feature/Flowers/FreshnessServiceTest.php` — тесты свежести
- `tests/Feature/Flowers/FloristAssignmentServiceTest.php` — тесты назначения флористов
- `tests/Unit/Flowers/ClientTest.php` — unit тесты клиентов
- `tests/Unit/Flowers/FlowerTest.php` — unit тесты цветов
- `tests/Unit/Flowers/DeliverySlotTest.php` — unit тесты слотов доставки

## 📊 Конфигурация

Основные настройки в `config/crm-flowers.php`:

### Воронка заказов (Funnel)
- `lead` → `new_order` → `confirmed` → `in_assembly` → `assembled` → `quality_checked` → `ready_for_delivery` → `out_for_delivery` → `delivered`

### Автоматизация
- `auto_assign_florist` — автоматическое назначение флористов
- `auto_update_freshness` — автообновление свежести
- `auto_send_notifications` — автоотправка уведомлений

### Свежесть (Freshness)
- `expiring_soon_days` — дни до истечения срока (по умолчанию 3)
- `auto_discount` — авто-скидки на устаревающие цветы
- `alerts` — пороги для алертов

### Лояльность
- Тиеры: Bronze → Silver → Gold → Platinum
- 1 балл = 100 RUB
- Бонус на день рождения: 100 баллов

## 🎯 Ключевые сервисы

### OrderService
```php
use Modules\Flowers\Application\Services\OrderService;

$orderService = app(OrderService::class);

// Создать заказ
$order = $orderService->createOrder([
    'venue_id' => 1,
    'tenant_id' => 1,
    'client_first_name' => 'John',
    'client_last_name' => 'Doe',
    'client_phone' => '+79001234567',
    'recipient_name' => 'Jane Doe',
    'recipient_phone' => '+79009876543',
    'delivery_type' => 'delivery',
    'items' => [
        ['product_id' => 1, 'quantity' => 1],
    ],
]);

// Обновить статус
$order = $orderService->updateOrderStatus($orderId, OrderStatus::CONFIRMED);

// Назначить флориста
$order = $orderService->assignFlorist($orderId, $floristId);

// Отменить заказ
$order = $orderService->cancelOrder($orderId, 'Customer request');

// Пометить как доставленный
$order = $orderService->markAsDelivered($orderId);
```

### FreshnessService
```php
use Modules\Flowers\Application\Services\FreshnessService;

$freshnessService = app(FreshnessService::class);

// Обновить свежесть для салона
$updated = $freshnessService->updateFreshnessForVenue($venueId);

// Получить скоро портящиеся цветы
$expiringSoon = $freshnessService->getExpiringSoon($venueId, 3);

// Получить отчёт по свежести
$report = $freshnessService->getFreshnessReport($venueId);

// Получить рекомендуемые действия
$actions = $freshnessService->getRecommendedActions($venueId);
```

### FloristAssignmentService
```php
use Modules\Flowers\Application\Services\FloristAssignmentService;

$assignmentService = app(FloristAssignmentService::class);

// Назначить лучшего флориста
$florist = $assignmentService->assignBestFlorist($orderId);

// Получить загрузку флориста
$workload = $assignmentService->getFloristWorkload($floristId, $date);

// Получить производительность
$performance = $assignmentService->getFloristPerformance($floristId, 30);

// Автоназначение всех pending заказов
$assigned = $assignmentService->autoAssignPendingOrders($venueId);
```

## 🖼️ Filament Resources

Доступные ресурсы в админке:

1. **Orders** (`/admin/flowers/orders`) — управление заказами с Kanban
2. **Flowers** (`/admin/flowers/flowers`) — управление цветами и свежестью
3. **Products** (`/admin/flowers/products`) — каталог букетов
4. **Clients** (`/admin/flowers/clients`) — база клиентов с лояльностью
5. **Florists** (`/admin/flowers/florists`) — управление флористами
6. **Venues** (`/admin/flowers/venues`) — управление салонами

## ⚡ Livewire Компоненты

### FloristOrderBoard
Доска заказов для флористов с фильтрами по статусу и поиском.

```blade
<livewire:flowers.florist-order-board :venueId="$venueId" />
```

### OrderCard
Карточка заказа с деталями и действиями.

```blade
<livewire:flowers.order-card :order="$order" />
```

### FreshnessMonitor
Монитор свежести цветов с алертами и рекомендациями.

```blade
<livewire:flowers.freshness-monitor :venueId="$venueId" />
```

## 🔔 События и автоматизации

### Доменные события

- `OrderCreated` — заказ создан
- `OrderStatusChanged` — статус изменён
- `FlowersReserved` — цветы зарезервированы
- `FlowersConsumed` — цветы израсходованы
- `FreshnessStatusChanged` — свежесть изменена
- `LowStockAlert` — мало цветов на складе
- `ExpiryWarning` — скоро истечёт срок

### Listeners

- `SendOrderConfirmationNotification` — отправка подтверждения
- `NotifyFloristOfNewOrder` — уведомление флориста
- `UpdateClientLoyalty` — обновление лояльности
- `ProcessLoyaltyPoints` — начисление баллов
- `SendFreshnessAlert` — алерт о свежести

## 🔒 Безопасность и Compliance

- **152-ФЗ**: все медицинские данные анонимизированы перед отправкой во внешние системы
- **PII protection**: личные данные клиентов защищены
- **Audit logging**: все действия логируются
- **Encryption**: чувствительные данные зашифрованы

## 📈 Аналитика и отчёты

Доступные метрики:

- Conversion rate (конверсия)
- Average Order Value (AOV)
- Repeat Purchase Rate (повторные покупки)
- Florist Performance (производительность флористов)
- Freshness Metrics (метрики свежести)
- Delivery Time (время доставки)

## 🐛 Troubleshooting

### Flowers не обновляются автоматически
Проверьте:
1. Включён ли `FLOWERS_AUTO_UPDATE_FRESHNESS=true`
2. Настроен ли scheduler в `app/Console/Kernel.php`
3. Работает ли cron-задача

### Florist не назначается автоматически
Проверьте:
1. Есть ли доступные флористы (`is_available = true`)
2. Включён ли `FLOWERS_AUTO_ASSIGN_FLORIST=true`
3. Есть ли у флориста рабочие часы

### Алерты о свежести не приходят
Проверьте:
1. Настроены ли notification channels в `config/crm-flowers.php`
2. Работают ли queue workers
3. Правильно ли настроены mail/smss credentials

## 📞 Поддержка

Для вопросов и предложений:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Документация: https://docs.catvrf.ru/flowers

## 📝 Лицензия

MIT License — см. LICENSE файл

---

**Версия**: 1.0.0  
**Дата**: 23.04.2026  
**Автор**: CatVRF Team
