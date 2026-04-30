# FIFO Shelf Life Control System

Единая система контроля срока годности (СОГ) и FIFO-управления для всех вертикалей CatVRF.

## Обзор

Система обеспечивает автоматический контроль срока годности и FIFO (First-In-First-Out) управление партиями товаров по всей CRM:

- **Ветеринария**: препараты, вакцины, лекарственные средства
- **Груминг**: косметика, шампуни, масла, расходные материалы
- **Кухня**: продукты питания для стационара и продаж
- **Розница**: корма, лакомства, добавки

## Архитектура

Система следует Clean Architecture + DDD:

```
modules/Inventory/
├── Application/
│   ├── Jobs/
│   │   └── ShelfLifeDailyJob.php          # Ежедневное обслуживание
│   ├── Policies/
│   │   └── InventoryItemPolicy.php        # Политики доступа
│   └── Services/
│       └── FIFOShelfLifeService.php       # Главный сервис FIFO
├── Domain/
│   ├── Entities/
│   │   ├── InventoryItem.php              # Сущность товара
│   │   └── InventoryBatch.php             # Сущность партии
│   ├── Enums/
│   │   ├── InventoryCategory.php          # Категории товаров
│   │   ├── ItemStatus.php                 # Статусы товаров
│   │   └── BatchStatus.php                # Статусы партий
│   ├── Exceptions/
│   │   ├── ShelfLifeException.php         # Исключения СОГ
│   │   └── InsufficientStockWithExpiryException.php
│   └── ValueObjects/
│       └── BatchDeductionResult.php       # Результат списания
├── Infrastructure/
│   ├── Models/
│   │   ├── InventoryItemModel.php         # Eloquent модель товара
│   │   └── InventoryBatchModel.php        # Eloquent модель партии
│   └── Providers/
│       └── InventoryServiceProvider.php   # Провайдер модуля
└── Filament/
    └── Resources/
        ├── InventoryItemResource.php      # Ресурс товара
        └── InventoryBatchResource.php     # Ресурс партии
```

## База данных

### inventory_items

Основная таблица товаров с информацией о сроке годности:

```php
- id
- tenant_id
- name
- sku (уникальный)
- barcode
- category (medication, feed, grooming_product, kitchen_product, other)
- batch_number
- manufacture_date
- expiry_date (СОГ - главное поле)
- shelf_life_days
- quantity
- reserved
- unit
- purchase_price
- selling_price
- min_stock_level
- storage_conditions
- storage_location
- is_controlled (требует обязательного СОГ)
- status (active, expiring_soon, expired, quarantine)
```

### inventory_batches

Партионный учёт для FIFO:

```php
- id
- inventory_item_id
- tenant_id
- batch_number (уникальный в рамках товара)
- manufacture_date
- expiry_date (ключевой для FIFO)
- initial_quantity
- current_quantity
- purchase_price
- storage_location
- status (active, expiring_soon, expired, quarantine)
```

## FIFO Логика

### Принцип работы

1. **Автоматический выбор партии**: При списании система автоматически выбирает партию с самым ранним `expiry_date`
2. **Блокировка просроченных**: Просроченные партии полностью исключаются из выбора
3. **Приоритет истекающих**: Партии с `expiring_soon` используются первыми
4. **Защита от race conditions**: Используется `lockForUpdate()` для атомарности

### Пример FIFO-списания

```php
use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

$service = app(FIFOShelfLifeService::class);
$item = InventoryItemModel::find(1);
$domainItem = $item->toDomain();

// Списать 10 единиц - автоматически выберет партию с ранним СОГ
$result = $service->autoDeduct(
    $domainItem,
    10,
    'sale', // контекст: sale, prescription, kitchen, grooming
    ['order_id' => 123]
);

// $result->deductedBatches содержит информацию о использованных партиях
```

## Использование по вертикалям

### Ветеринария

```php
// При выписке рецепта
$service->autoDeduct($medication, 5, 'prescription', [
    'vet_id' => auth()->id(),
    'patient_id' => $patient->id,
    'appointment_id' => $appointment->id,
]);

// Проверка перед назначением
$service->validateBeforeSale($medication, 5);
```

**Правила**:
- Все лекарства (`category: medication`) по умолчанию `is_controlled = true`
- Обязательно указание `expiry_date` при создании
- Запрет назначения просроченных препаратов
- Автоматическое логирование партии для медицинской отчётности

### Груминг

```php
// При использовании шампуня/масла
$service->autoDeduct($shampoo, 50, 'grooming', [
    'groomer_id' => auth()->id(),
    'appointment_id' => $appointment->id,
]);
```

**Правила**:
- Косметика (`category: grooming_product`) контролируется
- Отслеживание расходных материалов
- Предупреждение при использовании партий близких к просрочке

### Кухня / Стационар

```php
// При списании продуктов для питания
$service->autoDeduct($food, 2, 'kitchen', [
    'kitchen_id' => $kitchen->id,
    'meal_type' => 'breakfast',
]);
```

**Правила**:
- Жёсткий контроль продуктов питания (`category: kitchen_product`)
- Ежедневный отчёт по кухне
- Приоритетное использование продуктов с ранним сроком
- Блокировка просроченных продуктов

### Розница / Касса

```php
// При продаже в кассе
$service->autoDeduct($item, $quantity, 'sale', [
    'order_id' => $order->id,
    'cashier_id' => auth()->id(),
]);
```

**Правила**:
- Автоматическая проверка СОГ при добавлении в чек
- Блокировка продажи просроченных товаров
- FIFO по партиям для всех продаж

## Ежедневное обслуживание

### ShelfLifeDailyJob

Запускается ежедневно (рекомендуется в 00:00) для:

1. Обновления статусов просроченных партий
2. Перевода партий в `expiring_soon` (истекают в течение 30 дней)
3. Обновления статусов товаров на основе партий
4. Отправки уведомлений владельцу и ответственному

### Настройка в config/console.php

```php
$schedule->job(new \Modules\Inventory\Application\Jobs\ShelfLifeDailyJob())
    ->daily()
    ->at('00:00')
    ->onQueue('inventory');
```

## Цветовая индикация в Filament

### Статусы товаров

- **Зелёный** (`active`) - нормальный срок годности
- **Жёлтый** (`expiring_soon`) - истекает в течение 30 дней
- **Оранжевый** (`expiring_soon`) - истекает в течение 14 дней
- **Красный** (`expired`) - просрочен (блокировка)
- **Серый** (`quarantine`) - карантин или пусто

### Статусы партий

- **Зелёный** (`active`) - нормальный срок, есть остатки
- **Жёлтый** (`expiring_soon`) - истекает в течение 30 дней
- **Красный** (`expired`) - просрочен
- **Серый** (`quarantine`) - пусто или в карантине

## API сервисов

### FIFOShelfLifeService

```php
namespace Modules\Inventory\Application\Services;

class FIFOShelfLifeService
{
    // Главное: автоматическое FIFO-списание
    public function autoDeduct(
        InventoryItem $item,
        int $quantity,
        string $context, // sale, prescription, kitchen, grooming
        array $meta = []
    ): BatchDeductionResult;

    // Проверка перед продажей/использованием
    public function validateBeforeSale(
        InventoryItem $item,
        int $quantity
    ): void;

    // Получить следующую истекающую партию
    public function getNextExpiringBatch(int $itemId): ?InventoryBatchModel;

    // Ежедневное обслуживание
    public function dailyMaintenance(): void;
}
```

### Исключения

```php
// Товар просрочен
throw new ShelfLifeException::expiredItem($name, $expiryDate);

// Не указан срок годности
throw new ShelfLifeException::expiryDateRequired($name);

// Недостаточно товара с действующим СОГ
throw new InsufficientStockWithExpiryException::create($remaining);
```

## Тестирование

### Запуск тестов

```bash
php artisan test tests/Unit/Inventory/FIFOShelfLifeServiceTest.php
```

### Покрытие тестами

- FIFO по `expiry_date` (самое близкое к просрочке первым)
- Блокировка просроченных партий
- Приоритет `expiring_soon`
- Защита от race conditions (`lockForUpdate`)
- Интеграция с продажей, назначением и кухней
- Ежедневное обслуживание

## Правила по вертикалям

### Ветеринария

| Тип товара | Обязательный СОГ | FIFO | Уведомления |
|------------|------------------|------|-------------|
| Препараты  | Да               | Да   | 14 дней     |
| Вакцины    | Да               | Да   | 30 дней     |
| Расходные  | Нет              | Нет  | -           |

### Груминг

| Тип товара | Обязательный СОГ | FIFO | Уведомления |
|------------|------------------|------|-------------|
| Косметика  | Да               | Да   | 14 дней     |
| Шампуни    | Да               | Да   | 30 дней     |
| Инструменты| Нет              | Нет  | -           |

### Кухня

| Тип товара | Обязательный СОГ | FIFO | Уведомления |
|------------|------------------|------|-------------|
| Продукты   | Да               | Да   | 7 дней      |
| Мясо       | Да               | Да   | 3 дня       |
| Овощи      | Да               | Да   | 5 дней      |

### Розница

| Тип товара | Обязательный СОГ | FIFO | Уведомления |
|------------|------------------|------|-------------|
| Корма      | Да               | Да   | 30 дней     |
| Лакомства  | Да               | Да   | 60 дней     |
| Добавки    | Да               | Да   | 90 дней     |

## Интеграция с существующим кодом

### Добавление товара в каталог

```php
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

$item = InventoryItemModel::create([
    'tenant_id' => auth()->user()->tenant_id,
    'name' => 'Вакцина от бешенства',
    'sku' => 'VAC-RAB-001',
    'category' => 'medication',
    'is_controlled' => true, // автоматически для medication
    'expiry_date' => '2026-12-31', // обязательно для controlled
    'quantity' => 50,
    'unit' => 'шт',
    'selling_price' => 1500.00,
]);
```

### Создание партии при поступлении

```php
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;

$batch = InventoryBatchModel::create([
    'inventory_item_id' => $item->id,
    'tenant_id' => $item->tenant_id,
    'batch_number' => 'BATCH-2024-001',
    'manufacture_date' => '2024-01-15',
    'expiry_date' => '2026-01-15', // ключевой для FIFO
    'initial_quantity' => 50,
    'current_quantity' => 50,
    'purchase_price' => 800.00,
    'storage_location' => 'Холодильник A, полка 2',
    'status' => 'active',
]);

// Обновить общее количество товара
$item->increment('quantity', $batch->initial_quantity);
```

## Производительность

### Индексы

```sql
-- inventory_items
INDEX (tenant_id, sku)
INDEX (tenant_id, expiry_date)
INDEX (tenant_id, category, status)
INDEX (tenant_id, is_controlled, expiry_date)
INDEX (batch_number)

-- inventory_batches
INDEX (inventory_item_id, expiry_date, current_quantity, status)
INDEX (tenant_id, expiry_date)
INDEX (tenant_id, status, expiry_date)
UNIQUE (inventory_item_id, batch_number)
```

### Оптимизации

- `lockForUpdate()` для предотвращения race conditions
- Композитные индексы для FIFO-запросов
- Кэширование статусов товаров
- Асинхронные уведомления через очереди

## Безопасность и Compliance

### 152-ФЗ и ФЗ-323

- Анонимизация медицинских данных в логах
- Обязательный аудит всех движений
- Хранение информации о партиях для трассировки

### Права доступа

- Политика `InventoryItemPolicy` контролирует доступ
- Разграничение прав по tenant_id
- Аудит всех действий пользователей

## Мониторинг

### Логи

Все FIFO-операции логируются:

```php
Log::info('FIFO списание товара', [
    'item_id' => $item->id,
    'item_name' => $item->name,
    'quantity' => $quantity,
    'context' => $context,
    'batches_used' => $deductedBatches->count(),
    'batch_details' => $deductedBatches,
]);
```

### Метрики

Рекомендуемые метрики для мониторинга:

- Количество просроченных товаров
- Количество товаров с истекающим сроком
- Процент использования FIFO
- Время выполнения FIFO-операций

## Troubleshooting

### Проблема: Товар не продаётся

**Причина**: Товар просрочен или все партии просрочены

**Решение**:
```php
// Проверить статус
$item = InventoryItemModel::find($id);
dd($item->status, $item->isExpired());

// Проверить партии
$batches = $item->batches()->usable()->get();
dd($batches);
```

### Проблема: FIFO не работает

**Причина**: Неправильные индексы или статусы партий

**Решение**:
```php
// Запустить ежедневное обслуживание
app(FIFOShelfLifeService::class)->dailyMaintenance();

// Проверить индексы
Schema::hasIndex('inventory_batches', ['inventory_item_id', 'expiry_date', 'current_quantity', 'status']);
```

## Будущие улучшения

- [ ] Интеграция с системой уведомлений (Telegram, Email)
- [ ] Автоматическое формирование заказов на пополнение
- [ ] Прогнозирование спроса на основе FIFO-данных
- [ ] Интеграция с поставщиками для автоматического обновления СОГ
- [ ] Мобильное приложение для складского персонала

## Контакты

Для вопросов по системе FIFO обращайтесь к команде разработки CatVRF.

---

**Версия**: 1.0.0  
**Дата**: 23.04.2026  
**Автор**: CatVRF Development Team
