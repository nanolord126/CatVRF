# Чек-лист интеграции модуля CatCRM — Restaurants с маркетплейсом CatVRF

## Обзор

Этот чек-лист описывает шаги для полной интеграции ресторанного модуля CatCRM с маркетплейсом CatVRF, обеспечивая seamless синхронизацию заказов, клиентов и аналитики.

## ✅ Предварительные требования

- [ ] Модуль CatCRM установлен и настроен
- [ ] Модуль Restaurant установлен и миграции запущены
- [ ] Конфигурация `config/crm-restaurant.php` настроена
- [ ] Event Listeners зарегистрированы в `EventServiceProvider`
- [ ] Broadcasting каналы настроены в `config/channels.php`

---

## 1. Синхронизация заказов

### 1.1 Автоматическое создание CRM сделки из заказа маркетплейса

**Файл:** `app/Listeners/CRM/CreateRestaurantOrderFromMarketplace.php`

```php
<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\OrderCreated;
use Modules\Restaurant\Services\OrderService;
use Modules\Restaurant\Enums\OrderType;
use Modules\CatCRM\Application\Services\DealService;

final class CreateRestaurantOrderFromMarketplace
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly DealService $dealService
    ) {}

    public function handle(OrderCreated $event): void
    {
        $marketplaceOrder = $event->order;
        
        // Проверяем, что это ресторанный заказ
        if ($marketplaceOrder->vertical !== 'restaurant') {
            return;
        }

        // Создаём CRM сделку
        $deal = $this->dealService->createDealFromMarketplaceOrder([
            'order_id' => $marketplaceOrder->id,
            'vertical' => 'restaurant',
            'total' => $marketplaceOrder->total_amount,
            'first_name' => $marketplaceOrder->customer_first_name,
            'last_name' => $marketplaceOrder->customer_last_name,
            'email' => $marketplaceOrder->customer_email,
            'phone' => $marketplaceOrder->customer_phone,
            'items' => $marketplaceOrder->items,
        ], $marketplaceOrder->tenant_id);

        // Создаём ресторанный заказ
        $this->orderService->createOrder([
            'tenant_id' => $marketplaceOrder->tenant_id,
            'restaurant_id' => $marketplaceOrder->restaurant_id,
            'type' => $this->mapOrderType($marketplaceOrder->delivery_type),
            'crm_deal_id' => $deal->id,
            'guest_phone' => $marketplaceOrder->customer_phone,
            'guest_email' => $marketplaceOrder->customer_email,
            'delivery_address' => $marketplaceOrder->delivery_address,
            'items' => $this->mapOrderItems($marketplaceOrder->items),
        ]);
    }

    private function mapOrderType(string $deliveryType): OrderType
    {
        return match ($deliveryType) {
            'delivery' => OrderType::DELIVERY,
            'pickup' => OrderType::PICKUP,
            default => OrderType::DINE_IN,
        };
    }

    private function mapOrderItems(array $items): array
    {
        return array_map(fn($item) => [
            'menu_item_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price'],
        ], $items);
    }
}
```

- [ ] Создать listener `CreateRestaurantOrderFromMarketplace`
- [ ] Зарегистрировать в `EventServiceProvider`
- [ ] Протестировать автоматическое создание заказа

### 1.2 Синхронизация статусов

**Файл:** `app/Listeners/CRM/SyncRestaurantOrderStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\OrderStatusChanged;
use Modules\Restaurant\Models\Order;
use Modules\Restaurant\Enums\OrderStatus;

final class SyncRestaurantOrderStatus
{
    public function handle(OrderStatusChanged $event): void
    {
        $marketplaceOrder = $event->order;
        
        if ($marketplaceOrder->vertical !== 'restaurant') {
            return;
        }

        $restaurantOrder = Order::where('crm_deal_id', $marketplaceOrder->crm_deal_id)->first();
        
        if (!$restaurantOrder) {
            return;
        }

        $newStatus = $this->mapStatus($event->newStatus);
        
        if ($newStatus && $restaurantOrder->status->canTransitionTo($newStatus)) {
            $restaurantOrder->transitionTo($newStatus);
            $restaurantOrder->save();
        }
    }

    private function mapStatus(string $marketplaceStatus): ?OrderStatus
    {
        return match ($marketplaceStatus) {
            'confirmed' => OrderStatus::CONFIRMED,
            'preparing' => OrderStatus::PREPARING,
            'ready' => OrderStatus::READY,
            'delivered' => OrderStatus::DELIVERED,
            'cancelled' => OrderStatus::CANCELLED,
            default => null,
        };
    }
}
```

- [ ] Создать listener `SyncRestaurantOrderStatus`
- [ ] Зарегистрировать в `EventServiceProvider`
- [ ] Протестировать双向 синхронизацию статусов

---

## 2. Синхронизация клиентов

### 2.1 Автоматическое создание/обновление гостя

- [ ] При создании заказа маркетплейса автоматически создавать Guest
- [ ] Синхронизировать PII данные (имя, телефон, email)
- [ ] Обновлять историю заказов

### 2.2 Синхронизация программы лояльности

- [ ] При достижении LTV порога автоматически начислять бонусы
- [ ] Синхронизировать баллы между маркетплейсом и CRM
- [ ] Обновлять уровень лояльности

---

## 3. Синхронизация меню

### 3.1 Импорт меню из маркетплейса

**Файл:** `app/Console/Commands/SyncRestaurantMenu.php`

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Restaurant\Models\MenuItem;
use Modules\Restaurant\Models\Restaurant;

final class SyncRestaurantMenu extends Command
{
    protected $signature = 'restaurant:sync-menu {restaurant_id}';
    protected $description = 'Sync menu from marketplace to restaurant CRM';

    public function handle(): int
    {
        $restaurantId = (int) $this->argument('restaurant_id');
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            $this->error('Restaurant not found');
            return self::FAILURE;
        }

        // Получаем меню из маркетплейса
        $marketplaceMenu = $this->getMenuFromMarketplace($restaurantId);

        foreach ($marketplaceMenu as $item) {
            MenuItem::updateOrCreate(
                [
                    'restaurant_id' => $restaurantId,
                    'uuid' => $item['uuid'],
                ],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'price' => $item['price'],
                    'is_available' => $item['is_available'],
                    'preparation_time' => $item['preparation_time'],
                    'calories' => $item['calories'],
                    'allergens' => $item['allergens'],
                    'image_url' => $item['image_url'],
                ]
            );
        }

        $this->info('Menu synced successfully');
        return self::SUCCESS;
    }

    private function getMenuFromMarketplace(int $restaurantId): array
    {
        // TODO: Реализовать API вызов к маркетплейсу
        return [];
    }
}
```

- [ ] Создать команду `restaurant:sync-menu`
- [ ] Добавить в cron для периодической синхронизации
- [ ] Протестировать импорт меню

### 3.2 Экспорт изменений меню в маркетплейс

- [ ] При изменении меню в CRM отправлять webhooks
- [ ] Обрабатывать batch обновления
- [ ] Логировать ошибки синхронизации

---

## 4. Интеграция с платежами

### 4.1 Синхронизация статусов оплаты

- [ ] При успешной оплате в маркетплейсе обновлять `payment_status` в заказе
- [ ] При возврате обновлять статус и создавать транзакцию возврата
- [ ] Логировать все платежные операции

### 4.2 Разделение счёта

- [ ] Поддержка split payments через маркетплейс
- [ ] Синхронизация частичных оплат
- [ ] Учёт чаевых в CRM

---

## 5. Интеграция с доставкой

### 5.1 Синхронизация с курьерской службой CatVRF

- [ ] При создании заказа доставки автоматически назначать курьера
- [ ] Трекинг позиции курьера в реальном времени
- [ ] Обновление ETA в CRM

### 5.2 Геолокация

- [ ] Автоматический расчёт времени доставки на основе адреса
- [ ] Определение зоны доставки
- [ ] Проверка доступности доставки для адреса

---

## 6. Аналитика и отчёты

### 6.1 Синхронизация метрик

- [ ] Ежедневная агрегация данных в ClickHouse
- [ ] Расчёт LTV и retention
- [ ] Синхронизация с аналитикой маркетплейса

### 6.2 Отчёты

- [ ] Ежедневные отчёты по выручке
- [ ] Отчёты по эффективности кухни
- [ ] Отчёты по программе лояльности

---

## 7. Fraud Detection

### 7.1 Интеграция с FraudControlService

- [ ] Проверка заказов на подозрительную активность
- [ ] Блокировка массовых бронирований
- [ ] Проверка по чёрному списку

### 7.2 Behavioral Biometrics

- [ ] Отслеживание паттернов заказов
- [ ] Обнаружение аномалий в поведении
- [ ] Автоматическое помечение подозрительных заказов

---

## 8. Webhooks

### 8.1 Настройка webhooks

В `.env`:

```env
CRM_RESTAURANT_WEBHOOK_URL=https://your-domain.com/webhooks/restaurant
```

### 8.2 Обработка webhooks

**Файл:** `routes/api.php`

```php
Route::post('/webhooks/restaurant', function (Request $request) {
    // Валидация подписи
    // Обработка события
    // Ответ 200 OK
});
```

- [ ] Настроить endpoint для webhooks
- [ ] Реализовать валидацию подписи
- [ ] Обработать все типы событий
- [ ] Логировать все webhook вызовы

---

## 9. Тестирование интеграции

### 9.1 Unit тесты

- [ ] Тесты для CreateRestaurantOrderFromMarketplace
- [ ] Тесты для SyncRestaurantOrderStatus
- [ ] Тесты для синхронизации меню
- [ ] Тесты для лояльности

### 9.2 Integration тесты

- [ ] E2E тест создания заказа из маркетплейса
- [ ] Тест синхронизации статусов
- [ ] Тест начисления баллов
- [ ] Тест вебхуков

### 9.3 Load тесты

- [ ] Тест на 100 заказов/минуту
- [ ] Тест на 1000 concurrent пользователей
- [ ] Тест на пиковую нагрузку

---

## 10. Мониторинг

### 10.1 Метрики

- [ ] Prometheus метрики для всех операций
- [ ] Grafana дашборды для мониторинга
- [ ] Alerts для критических ошибок

### 10.2 Логи

- [ ] Структурированные логи в ClickHouse
- [ ] Audit лог для всех операций
- [ ] Error tracking (Sentry)

---

## ✅ Чек-лист готовности к продакшену

Перед деплоем в продакшен убедитесь:

- [ ] Все миграции протестированы на staging
- [ ] Все Event Listeners зарегистрированы и работают
- [ ] Webhooks настроены и протестированы
- [ ] Fraud detection включён и настроен
- [ ] Мониторинг и алерты настроены
- [ ] Backup стратегия протестирована
- [ ] Документация обновлена
- [ ] Команда обучена работе с модулем
- [ ] Rollback план готов

---

## Поддержка

При проблемах с интеграцией:
1. Проверьте логи: `storage/logs/restaurant.log`
2. Проверьте метрики в Grafana
3. Создайте issue на GitHub с деталями ошибки
