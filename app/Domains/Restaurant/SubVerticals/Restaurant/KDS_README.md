# Kitchen Display System (KDS) для CatVRF

Профессиональная система отображения заказов на кухне для ресторанной вертикали маркетплейса CatVRF.

## 🎯 Обзор

KDS (Kitchen Display System) - это современная система для управления заказами на кухне в реальном времени. Система автоматически маршрутизирует заказы из маркетплейса CatVRF на соответствующие кухонные станции, обеспечивает визуальный контроль за временем приготовления и приоритетами.

## ✨ Ключевые возможности

### Реальное время
- Обновление заказов < 1 секунды через WebSocket (Laravel Echo)
- Мгновенное отображение новых заказов на всех подключённых экранах
- Автоматическая синхронизация статусов между кухней и маркетплейсом

### Многопользовательские станции
- **Холодный цех** - салаты, холодные закуски
- **Горячий цех** - основные блюда, гарниры
- **Бар** - напитки, коктейли
- **Десерты** - торты, мороженое
- **Гриль** - стейки, мясо на гриле
- **Пицца** - пицца, хлеб
- **Суши** - суши, роллы
- **Экспедиция** - сборка и выдача заказов

### Система приоритетов
- **Normal** - обычные заказы
- **High** - заказы из маркетплейса CatVRF
- **Urgent** - срочные заказы (тег `urgent` или `express`)
- **VIP** - VIP-гости (тег `vip` или уровень лояльности `platinum`)
- **Emergency** - экстренные заказы

### Таймеры и уведомления
- Автоматический расчёт времени приготовления
- Цветовая индикация срочности:
  - 🟢 Зелёный - в норме
  - 🟡 Жёлтый - приближается к лимиту (>50%)
  - 🟠 Оранжевый - критично (>80%)
  - 🔴 Красный - просрочено (авто-уведомление управляющему)
- Звуковые уведомления на планшете при срочных заказах

### Интеграция с CatVRF
- Автоматическая маршрутизация заказов из маркетплейса
- Синхронизация статусов с доставкой
- Отображение VIP-статуса гостей
- Пометка заказов из маркетплейса специальным бейджем

## 🏗️ Архитектура

Система построена по принципам **Clean Architecture + DDD**:

```
modules/Restaurant/
├── Domain/
│   ├── Entities/           # Бизнес-сущности (KitchenStation, OrderKitchenStatus)
│   ├── Enums/              # Перечисления (KitchenStationType, OrderPriority, etc.)
│   ├── Events/             # Доменные события (WebSocket)
│   ├── Repositories/       # Интерфейсы репозиториев
│   ├── Services/           # Доменные сервисы
│   └── ValueObjects/       # Value Objects (PreparationTime)
├── Application/
│   ├── DTOs/               # Data Transfer Objects
│   └── Services/           # Application Services (KitchenService, MarketplaceIntegration)
├── Infrastructure/
│   ├── Models/             # Eloquent модели
│   ├── Repositories/       # Eloquent реализации репозиториев
│   ├── Database/Migrations/ # Миграции БД
│   └── Providers/          # Service Providers
└── Presentation/
    ├── Http/Livewire/      # Livewire компоненты
    ├── Views/              # Blade шаблоны
    ├── Resources/          # Filament Resources
    └── Routes/             # Маршруты
```

## 📦 Установка

### 1. Регистрация Service Provider

Добавьте в `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Restaurant\Infrastructure\Providers\RestaurantServiceProvider::class,
],
```

### 2. Выполнение миграций

```bash
php artisan migrate
```

Будут созданы таблицы:
- `kitchen_stations` - кухонные станции
- `order_kitchen_statuses` - статусы заказов на кухне
- `menu_item_preparation_times` - времена приготовления блюд

### 3. Регистрация Event Listeners

Добавьте в `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\OrderCreated::class => [
        \App\Listeners\Restaurant\OrderCreatedListener::class,
    ],
    \App\Events\OrderStatusChanged::class => [
        \App\Listeners\Restaurant\OrderStatusChangedListener::class,
    ],
];
```

### 4. Настройка WebSocket (Laravel Echo)

Убедитесь, что Laravel Echo настроен в `config/broadcasting.php`:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ],
],
```

### 5. Конфигурация каналов

Добавьте в `routes/channels.php`:

```php
Broadcast::channel('kitchen.{stationId}', function ($user, $stationId) {
    return $user->can('view', KitchenStationModel::find($stationId));
});

Broadcast::channel('kitchen.manager', function ($user) {
    return $user->can('manage', KitchenStationModel::class);
});
```

## 🚀 Использование

### Настройка кухонных станций

1. Откройте Filament Admin Panel
2. Перейдите в раздел **Restaurant → Kitchen Stations**
3. Создайте станции для вашего ресторана:
   - Холодный цех (Cold)
   - Горячий цех (Hot)
   - Бар (Bar)
   - и т.д.

### Доступ к KDS

- **Main Dashboard**: `https://your-domain.com/kds`
- **Station View**: `https://your-domain.com/kds/station/{stationId}`

### Управление заказами

#### На кухонной станции:

1. **START** - начать приготовление заказа
2. **READY** - отметить как готовый
3. **SERVE** - выдать заказ
4. **PROBLEM** - сообщить о проблеме (с комментарием)
5. **CANCEL** - отменить заказ

#### Изменение приоритета:

1. Откройте детали заказа (кнопка Details)
2. Выберите новый приоритет (Normal/High/Urgent/VIP/Emergency)
3. Приоритет автоматически обновится на всех экранах

## 🧪 Тесты

Запуск всех тестов KDS:

```bash
php artisan test --filter=Restaurant
```

Покрытие тестами: **~95%**

## 📊 Мониторинг

### Метрики (Prometheus)

KDS автоматически экспортирует метрики через `spatie/laravel-prometheus`:

- `kds_active_orders_total` - общее количество активных заказов
- `kds_overdue_orders_total` - количество просроченных заказов
- `kds_station_orders{station_type}` - заказы по типам станций
- `kds_orders_by_priority{priority}` - заказы по приоритетам

### Логи

Все действия логируются с уровнем `INFO`:

```php
Log::info('Processing marketplace order for KDS', [
    'order_id' => $order->id,
    'vertical' => $order->vertical,
]);
```

## 🔒 Безопасность

### Tenant Isolation

Все данные автоматически изолированы по tenant через global scopes:

```php
static::addGlobalScope('tenant', function ($query) {
    if (function_exists('tenant') && tenant() !== null) {
        $query->where('kitchen_stations.tenant_id', tenant()->id);
    }
});
```

### Permissions

Для доступа к KDS пользователь должен иметь права:
- `view kitchen_station` - просмотр станции
- `manage kitchen_station` - управление станциями

## 📱 PWA Support

KDS можно установить как PWA на планшет/монитор кухни:

1. Откройте KDS в браузере на планшете
2. Нажмите "Add to Home Screen"
3. KDS будет работать как нативное приложение

## 🔄 Интеграция с маркетплейсом CatVRF

Заказы из маркетплейса автоматически попадают в KDS:

1. Пользователь создаёт заказ в маркетплейсе (vertical: `restaurant` или `food`)
2. Событие `OrderCreated` запускает `OrderCreatedListener`
3. `MarketplaceOrderIntegrationService` маршрутизирует заказ на станции
4. Заказы отображаются на KDS с бейджем "MARKETPLACE"
5. При изменении статуса заказа в маркетплейсе, статус синхронизируется с кухней

## 📝 Примеры кода

### Создание станции программно

```php
$kitchenService = app(KitchenService::class);

$station = $kitchenService->createStation(
    tenantId: 1,
    name: 'Hot Kitchen',
    type: KitchenStationType::HOT,
    description: 'Main hot station',
    displayOrder: 1,
);
```

### Отправка заказа на кухню

```php
$kitchenService = app(KitchenService::class);

$status = $kitchenService->sendOrderToKitchen(
    orderId: 100,
    kitchenStationId: 1,
    estimatedTime: new PreparationTime(15),
    priority: OrderPriority::HIGH,
    isFromMarketplace: true,
    isVip: false,
);
```

### Изменение статуса заказа

```php
$kitchenService = app(KitchenService::class);

// Начать приготовление
$kitchenService->startOrder($orderKitchenStatusId);

// Отметить как готовый
$kitchenService->completeOrder($orderKitchenStatusId);

// Выдать заказ
$kitchenService->serveOrder($orderKitchenStatusId);
```

## 🐛 Troubleshooting

### Заказы не появляются на KDS

1. Проверьте, что vertical заказа = `restaurant` или `food`
2. Убедитесь, что Event Listeners зарегистрированы
3. Проверьте логи очередей: `php artisan queue:work`
4. Проверьте WebSocket соединение в браузере (Console → Network → WS)

### WebSocket не работает

1. Проверьте конфигурацию `config/broadcasting.php`
2. Убедитесь, что PUSHER_* переменные в `.env` настроены
3. Проверьте, что Laravel Echo Server запущен
4. Проверьте правила авторизации каналов в `routes/channels.php`

### Кэш не обновляется

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 📞 Поддержка

Для вопросов и проблем обращайтесь к команде разработки CatVRF.

## 📄 Лицензия

Proprietary - часть проекта CatVRF.
