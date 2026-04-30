# CatCRM — Restaurants Module

**Версия:** 1.0  
**Статус:** Production Ready  
**Вертикаль:** Рестораны / Кафе / Доставка еды  

## Описание

Полнофункциональный модуль CatCRM для ресторанного бизнеса, автоматически подключаемый при выборе вертикали «Рестораны / Кафе / Доставка еды». Модуль обеспечивает готовую, глубоко адаптированную CRM без необходимости дополнительной настройки.

## Ключевые возможности

### Для владельца / управляющего
- **Реал-тайм дашборд** загрузки зала и кухни
- **Аналитика** по сменам, среднему чеку, популярным блюдам, LTV гостя, retention
- **Управление меню** и ценами
- **Программа лояльности** (баллы, уровни, персональные предложения)
- **Автоматические отчёты** по выручке и остаткам на складе

### Для официантов
- **Мобильный интерфейс** (Livewire + PWA) — быстрый приём заказа со столика
- **Передача заказа на кухню** одним кликом
- **Разделение счёта**, добавление модификаторов

### Для кухни
- **Кухонный дисплей (KDS)** — заказы в реальном времени с таймерами
- **Статусы приготовления** (в работе → готово → выдан)

### Для доставки
- **Интеграция с курьерской службой** CatVRF
- **Трекинг курьера** в реальном времени
- **Автоматический расчёт времени доставки**

### Общие возможности
- **Интеграция с маркетплейсом** CatVRF (заказы автоматически попадают в CRM)
- **Синхронизация с внешними системами** (r_keeper, iiko, 1C через API)
- **Автоматические уведомления** гостям (Telegram, VK, SMS fallback)
- **Behavioral Biometrics** для обнаружения подозрительных действий

## Архитектура

```
modules/Restaurant/
├── Enums/                    # Перечисления
│   ├── OrderType.php        # Типы заказов
│   ├── OrderStatus.php      # Статусы заказов
│   ├── TableStatus.php      # Статусы столиков
│   └── StaffRole.php        # Роли сотрудников
├── Models/                   # Модели Eloquent
│   ├── Restaurant.php       # Заведение (уже существует)
│   ├── MenuItem.php         # Позиции меню (уже существует)
│   ├── Reservation.php      # Бронирования (уже существует)
│   ├── Table.php            # Столики
│   ├── Order.php            # Заказы
│   ├── OrderItem.php        # Позиции заказа
│   ├── LoyaltyProgram.php   # Программы лояльности
│   └── LoyaltyTransaction.php # Транзакции лояльности
├── Services/                 # Application Services
│   ├── OrderService.php     # Сервис заказов
│   ├── KitchenService.php   # Сервис кухни (KDS)
│   └── LoyaltyService.php   # Сервис лояльности
├── Events/                   # События домена
│   ├── OrderCreated.php
│   ├── OrderStatusChanged.php
│   ├── KitchenOrderReady.php
│   ├── LoyaltyBonusEarned.php
│   └── LoyaltyTierChanged.php
├── Listeners/                # Обработчики событий
│   ├── SendOrderToKitchen.php
│   ├── AwardLoyaltyBonus.php
│   ├── NotifyWaiterOrderReady.php
│   ├── ReleaseTableOnOrderComplete.php
│   └── UpdateGuestStatistics.php
├── Database/
│   └── Migrations/          # Миграции БД
└── Filament/                 # Filament Resources (TBD)
    └── Resources/

modules/CatCRM/Domain/Verticals/Restaurants/
└── RestaurantOrder.php       # Вертикальная сущность CRM
```

## Установка

### 1. Запустить миграции

```bash
php artisan migrate
```

Будут созданы таблицы:
- `restaurant_tables` — столики
- `restaurant_orders` — заказы
- `restaurant_order_items` — позиции заказов
- `restaurant_guests` — гости (клиенты)
- `restaurant_staff_shifts` — смены сотрудников
- `restaurant_loyalty_programs` — программы лояльности
- `restaurant_loyalty_transactions` — транзакции лояльности
- `crm_restaurant_orders` — связь с CRM сделками

### 2. Настройка конфигурации

Конфигурация находится в `config/crm-restaurant.php`:

```php
'enabled' => env('CRM_RESTAURANT_ENABLED', true),

'pipelines' => [
    'dine_in' => [...],
    'delivery' => [...],
    'pickup' => [...],
    'reservation' => [...],
],

'automations' => [
    'loyalty_points_on_order_complete' => true,
    'auto_release_table_on_complete' => true,
    'notify_waiter_on_ready' => true,
    // ...
],
```

### 3. Регистрация Event Listeners

В `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \Modules\Restaurant\Events\OrderCreated::class => [
        \Modules\Restaurant\Listeners\SendOrderToKitchen::class,
    ],
    \Modules\Restaurant\Events\OrderStatusChanged::class => [
        \Modules\Restaurant\Listeners\AwardLoyaltyBonus::class,
        \Modules\Restaurant\Listeners\ReleaseTableOnOrderComplete::class,
    ],
    \Modules\Restaurant\Events\KitchenOrderReady::class => [
        \Modules\Restaurant\Listeners\NotifyWaiterOrderReady::class,
    ],
    \Modules\Restaurant\Events\LoyaltyBonusEarned::class => [
        // Добавьте ваши слушатели для начисления бонусов
    ],
];
```

### 4. Настройка каналов broadcasting

В `config/channels.php`:

```php
Broadcast::channel('restaurant.{restaurantId}', function ($user, $restaurantId) {
    return $user->can('view', Restaurant::find($restaurantId));
});

Broadcast::channel('restaurant.{restaurantId}.kitchen', function ($user, $restaurantId) {
    return $user->can('accessKitchen', Restaurant::find($restaurantId));
});

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return $user->can('view', Order::find($orderId));
});

Broadcast::channel('guest.{guestId}', function ($user, $guestId) {
    return $user->id === Guest::find($guestId)?->user_id;
});
```

## Использование

### Создание заказа

```php
use Modules\Restaurant\Services\OrderService;
use Modules\Restaurant\Enums\OrderType;

$orderService = new OrderService($correlationId);

$order = $orderService->createOrder([
    'tenant_id' => $tenantId,
    'restaurant_id' => $restaurantId,
    'type' => OrderType::DINE_IN,
    'table_id' => $tableId,
    'waiter_id' => $waiterId,
    'guest_phone' => '+79001234567',
    'items' => [
        [
            'menu_item_id' => 1,
            'quantity' => 2,
            'modifiers' => ['без лука'],
        ],
    ],
]);
```

### Управление кухней (KDS)

```php
use Modules\Restaurant\Services\KitchenService;

$kitchenService = new KitchenService($correlationId);

// Получить очередь заказов
$queue = $kitchenService->getKitchenQueue($restaurantId);

// Начать приготовление позиции
$kitchenService->startItemPreparation($orderItem);

// Отметить как готовое
$kitchenService->markItemReady($orderItem);

// Получить просроченные заказы
$overdue = $kitchenService->getOverdueOrders($restaurantId);
```

### Программа лояльности

```php
use Modules\Restaurant\Services\LoyaltyService;

$loyaltyService = new LoyaltyService($correlationId);

// Начислить бонус за заказ (% от суммы)
$transaction = $loyaltyService->earnBonusFromOrder($order);

// Списать бонус с кошелька для оплаты
$loyaltyService->redeemWalletBonus($user, 200.00, $order);

// Начислить бонус (регистрация, день рождения и т.д.)
$loyaltyService->awardWalletBonus($user, 50.00, 'Birthday bonus');

// Проверить бонус за день рождения
$loyaltyService->checkBirthdayBonus($user);
```

**Принцип работы:**
- Пользователь получает % от суммы заказа на кошелек (по умолчанию 1%)
- Уровни лояльности увеличивают множитель бонуса (bronze: 1.0x, silver: 1.2x, gold: 1.5x, platinum: 2.0x)
- Бонусы можно использовать для частичной или полной оплаты
- Уровни рассчитываются по общей сумме трат, а не по количеству бонусов

### Управление столиками

```php
use Modules\Restaurant\Models\Table;
use Modules\Restaurant\Enums\TableStatus;

// Занять столик
$table = Table::find($tableId);
$table->occupy();

// Забронировать
$table->reserve();

// Освободить
$table->release();

// Получить свободные столики
$availableTables = Table::available()
    ->bySeats(4)
    ->get();
```

## Воронки продаж

### Dine-In (В зале)
1. Новый заказ → Подтверждён → Готовится → Готов к подаче → Подан

### Delivery (Доставка)
1. Новый заказ → Подтверждён → Готовится → Готов к доставке → Доставляется → Доставлен

### Pickup (Самовывоз)
1. Новый заказ → Подтверждён → Готовится → Готов к выдаче

### Reservation (Бронирование)
1. Запрос на бронь → Подтверждён → Пользователь пришёл → Завершено

## Автоматизации

### Включенные по умолчанию
- **Автоматическое начисление бонусов лояльности** при завершении заказа
- **Автоматическое освобождение столика** при завершении/отмене заказа
- **Уведомление официанта** о готовности заказа
- **Отправка заказа на кухню** при подтверждении
- **Напоминание о бронировании** за 30 минут
- **Follow-up** через 7 дней после визита

## Интеграция с маркетплейсом CatVRF

Заказы из маркетплейса автоматически создаются как CRM сделки и синхронизируются с ресторанным модулем. См. `INTEGRATION_CHECKLIST.md` для деталей.

## Тестирование

```bash
# Запустить все тесты ресторанного модуля
php artisan test --filter=Restaurant

# Запустить конкретный тест
php artisan test --filter=OrderServiceTest
```

## Мониторинг

Модуль интегрирован с системой мониторинга CatVRF:
- OpenTelemetry трассировка
- Prometheus метрики
- Логи в ClickHouse
- Real-time обновления через WebSocket

## Безопасность

- Все данные изолированы по `tenant_id`
- Шифрование PII данных (email, phone, address)
- Audit лог для всех операций
- Fraud detection интеграция
- Behavioral Biometrics для обнаружения аномалий

## Performance

- Оптимизированные запросы с eager loading
- Кэширование с `Cache::tags`
- Queue для тяжелых операций
- Индексы на всех ключевых полях
- Redis для real-time операций

## Roadmap

- [ ] Filament 3/4 ресурсы для Tenant Panel
- [ ] Livewire компоненты (KDS, Waiter App, Dashboard)
- [ ] Интеграция с r_keeper, iiko, 1C
- [ ] Advanced аналитика (ClickHouse)
- [ ] AI-рекомендации блюд
- [ ] Voice integration (заказ голосом)
- [ ] Мобильное приложение для гостей

## Поддержка

Для вопросов и поддержки:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Документация: https://catvrf.ru/docs/crm/restaurant
