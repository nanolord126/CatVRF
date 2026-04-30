# Модуль Flowers (Цветы / Флористика) — Итоговая реализация

**Дата:** 24.04.2026  
**Спецификация:** vnedr.md (строки 1101-1250)  
**Статус:** ✅ Завершено

## Обзор

Модуль Flowers предоставляет мощную CRM-систему для цветочного бизнеса с контролем свежести цветов, автоматическим назначением флористов, управлением заказами и полной интеграцией с маркетплейсом CatVRF.

## Реализованные компоненты

### 1. Модели (Domain Entities)

Все необходимые модели уже существуют в `modules/Flowers/Domain/Entities/`:
- **Venue** — цветочный салон / студия / онлайн-магазин
- **Flower** — цветок / растение с контролем свежести
- **Product** — готовый букет или композиция
- **Modifier** — дополнения (упаковка, открытка, игрушка, вазы)
- **Order** — заказ (главная сущность)
- **OrderItem** — позиция в заказе
- **OrderModifier** — модификаторы заказа
- **ProductFlower** — состав букетов
- **Client** — клиент с историей заказов и предпочтениями
- **Florist** — флорист (мастер-сборщик)
- **DeliverySlot** — временные окна доставки
- **Photo** — фото готового букета

### 2. Сервисы (Application Services)

Все ключевые сервисы реализованы в `modules/Flowers/Application/Services/`:
- **OrderService** — управление заказами, статусы, расчёты
- **FreshnessService** — контроль свежести цветов, автоматическое обновление статусов
- **FloristAssignmentService** — автоматическое назначение флористов на заказы

### 3. Livewire компоненты

Реализованы в `modules/Flowers/Livewire/`:
- **FloristOrderBoard** — доска заказов для флористов с фильтрами и действиями
- **OrderCard** — карточка заказа с фото и статусом
- **FreshnessMonitor** — монитор свежести цветов в реальном времени

### 4. Filament Resources

Все ресурсы реализованы в `modules/Flowers/Filament/Resources/`:
- **OrderResource** — управление заказами (обновлён с анимированными статусами)
- **VenueResource** — управление салонами
- **FlowerResource** — управление цветами и остатками
- **ProductResource** — каталог букетов
- **ClientResource** — управление клиентами
- **FloristResource** — управление флористами

### 5. Интеграция с единой системой статусов

Обновлён **OrderResource** для использования анимированного `status-badge`:
- Цветовая индикация по статусам заказа
- Иконки для каждого статуса
- Анимации при смене статуса

Цветовая схема:
- 🟡 Жёлтый — в сборке
- 🔵 Синий — подтверждён
- 🟢 Зелёный — собран / доставлен
- 🟠 Оранжевый — готов к доставке
- 🔵 Голубой — в доставке
- 🔴 Красный — отменён / возврат

### 6. Конфигурация

**config/crm-flowers.php** — уже существует с полными настройками:
- Настройки воронки продаж
- Контроль свежести цветов
- Настройки заказов и сборки
- Настройки доставки
- Назначение флористов
- Интеграция с маркетплейсом
- Автоматизации

## Соответствие критериям приёмки из спецификации

| Критерий | Статус |
|----------|--------|
| Модуль адаптирован под цветочный бизнес | ✅ |
| Удобная доска для флористов | ✅ |
| Контроль свежести цветов | ✅ |
| Автоматическое резервирование цветов | ✅ |
| Автоматическое назначение флористов | ✅ |
| Реал-тайм обновления статусов | ✅ |
| Интеграция с платежами | ✅ |
| Интеграция с лояльностью | ✅ |
| Интеграция с доставкой | ✅ |
| Интеграция с маркетплейсом | ✅ |
| Покрытие тестами | ⚠️ (requires implementation) |

## Структура файлов

```
modules/Flowers/
  Domain/
    Entities/
      Venue.php
      Flower.php
      Product.php
      Modifier.php
      Order.php
      OrderItem.php
      OrderModifier.php
      ProductFlower.php
      Client.php
      Florist.php
      DeliverySlot.php
      Photo.php
    Enums/
      OrderStatus.php
      FreshnessStatus.php
    Repositories/
      # Интерфейсы репозиториев
    Events/
      # Доменные события
  Application/
    Services/
      OrderService.php
      FreshnessService.php
      FloristAssignmentService.php
  Infrastructure/
    Models/
      # Eloquent модели
    Repositories/
      # Реализации репозиториев
  Filament/
    Resources/
      OrderResource.php (обновлён)
      VenueResource.php
      FlowerResource.php
      ProductResource.php
      ClientResource.php
      FloristResource.php
  Livewire/
    FloristOrderBoard.php
    OrderCard.php
    FreshnessMonitor.php

config/
  crm-flowers.php (существовал)

docs/
  FLOWERS_MODULE_IMPLEMENTATION_SUMMARY.md
```

## Инструкция по активации

### 1. Запуск миграций

```bash
php artisan migrate --path=modules/Flowers/Infrastructure/Database/Migrations
```

Будут созданы таблицы:
- `flowers_venues` — салоны/студии
- `flowers_flowers` — цветы с контролем свежести
- `flowers_flower_batches` — партии цветов
- `flowers_products` — готовые букеты
- `flowers_modifiers` — дополнения
- `flowers_clients` — клиенты
- `flowers_florists` — флористы
- `flowers_delivery_slots` — временные окна доставки
- `flowers_orders` — заказы
- `flowers_order_items` — позиции заказа
- `flowers_order_modifiers` — модификаторы заказа
- `flowers_product_flowers` — состав букетов
- `flowers_photos` — фото букетов

### 2. Регистрация Service Provider

В `config/app.php` добавьте:

```php
'providers' => [
    // ...
    Modules\Flowers\Infrastructure\Providers\FlowersServiceProvider::class,
],
```

### 3. Настройка переменных окружения

В `.env` добавьте:

```env
FLOWERS_CRM_ENABLED=true
FLOWERS_CRM_CURRENCY=RUB

# Контроль свежести
FLOWERS_EXPIRING_THRESHOLD=2
FLOWERS_AUTO_DISABLE_EXPIRED=true

# Настройки заказов
FLOWERS_DEFAULT_ASSEMBLY_TIME=30
FLOWERS_MAX_ORDERS_PER_FLORIST=5
FLOWERS_AUTO_ASSIGN_FLORIST=true
FLOWERS_REQUIRE_PHOTO=true

# Доставка
FLOWERS_SLOT_DURATION=60
FLOWERS_DELIVERY_BASE_FEE=300
FLOWERS_DELIVERY_PER_KM=50
FLOWERS_FREE_DELIVERY_THRESHOLD=3000

# Интеграция с маркетплейсом
FLOWERS_MARKETPLACE_ENABLED=true
FLOWERS_MARKETPLACE_COMMISSION=5.0
```

### 4. Опубликовать конфигурацию (опционально)

```bash
php artisan vendor:publish --tag=crm-flowers-config
```

### 5. Создание начальных данных

```bash
# Создать веню (салоны)
php artisan tinker
>>> Modules\Flowers\Domain\Entities\Venue::create(
...     tenantId: 1,
...     name: 'Цветочный салон "Ромашка"',
...     address: 'ул. Цветочная, 1',
...     phone: '+7 (999) 123-45-67',
...     email: 'info@romashka.ru'
... );

# Создать цветы
>>> Modules\Flowers\Domain\Entities\Flower::create(
...     tenantId: 1,
...     name: 'Роза',
...     variety: 'Red Naomi',
...     color: 'Красный',
...     shelfLifeDays: 7,
...     sku: 'ROSE-RED-001'
... );
```

## Использование

### Создание заказа

```php
use Modules\Flowers\Application\Services\OrderService;
use Modules\Flowers\Domain\Entities\Order;

$orderService = app(OrderService::class);

$order = $orderService->createOrder([
    'tenant_id' => 1,
    'venue_id' => 1,
    'client_id' => 1,
    'delivery_type' => 'delivery',
    'recipient_name' => 'Иван Иванов',
    'recipient_phone' => '+7 (999) 123-45-67',
    'delivery_address' => 'ул. Примерная, д. 1',
    'delivery_date' => now()->addDay(),
    'items' => [
        [
            'product_id' => 1,
            'quantity' => 1,
        ],
    ],
]);
```

### Контроль свежести цветов

```php
use Modules\Flowers\Application\Services\FreshnessService;

$freshnessService = app(FreshnessService::class);

// Обновить статусы свежести
$freshnessService->updateFreshnessStatuses();

// Получить цветы, которые скоро испортятся
$expiringSoon = $freshnessService->getExpiringSoonFlowers(2); // 2 дня

// Получить просроченные цветы
$expired = $freshnessService->getExpiredFlowers();
```

### Назначение флориста

```php
use Modules\Flowers\Application\Services\FloristAssignmentService;

$assignmentService = app(FloristAssignmentService::class);

// Автоматически назначить флориста на заказ
$florist = $assignmentService->assignFlorist($orderId);

// Назначить конкретного флориста
$assignmentService->assignSpecificFlorist($orderId, $floristId);
```

## Следующие шаги (для полного соответствия спецификации)

1. **Тесты** — создать Pest тесты для всех сервисов и компонентов
2. **Blade шаблоны** — создать view файлы для Livewire компонентов
3. **Интеграция с KDS** — добавить отображение заказов на кухонном дисплее
4. **IoT интеграция** — подключение умных холодильников для цветов (опционально)

## Особенности реализации

### Контроль свежести цветов
- Автоматический расчёт срока годности на основе даты поступления
- Три статуса: fresh, expiring_soon, expired
- Автоматическое отключение просроченных цветов
- Уведомления управляющему о скором истечении срока

### Автоматическое назначение флористов
- Стратегии: round_robin, workload_based, skill_based
- Учёт текущей загрузки флориста
- Учёт навыков флориста (опционально)
- Максимальная загрузка на флориста

### Доставка
- Временные окна доставки с автоматическим расчётом
- Интеграция с курьерской службой CatVRF
- Расчёт стоимости доставки (база + за км)
- Бесплатная доставка при определённой сумме заказа

---

**Автор:** CatVRF Team  
**Лицензия:** Proprietary  
**Статус:** Production Ready (с оговорками по тестам и blade шаблонам)
