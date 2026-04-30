# Warehouse Vertical - WMS (Warehouse Management System)

**Purpose:** Полнофункциональная система управления складами (WMS) для CatVRF с детальным учетом товаров, партионным учетом, инвентаризацией и управлением движениями.

**Версия:** 1.0.0  
**Дата:** 28.04.2026  
**Статус:** Active Development

## Обзор

Warehouse Vertical предоставляет комплексное решение для управления складскими операциями:

- **Multi-warehouse support**: Центральные, региональные, локальные, транзитные склады
- **Zone management**: Приёмка, хранение, комплектация, упаковка, отгрузка, карантин, возвраты
- **Bin-level tracking**: Точное отслеживание до уровня ячейки хранения
- **Batch tracking**: Полный партионный учет с контролем срока годности
- **Stock movements**: Все типы движений (приход, расход, перемещение, корректировка)
- **Inventory counting**: Полная, частичная, циклическая, точечная инвентаризация
- **B2B/B2C separation**: Разделение запасов по типам заказов
- **Reservation system**: Резервирование товаров под заказы

## Архитектура

Система следует Clean Architecture + DDD:

```
modules/Warehouse/
├── Domain/
│   ├── Entities/              # Доменные сущности (readonly)
│   │   ├── Warehouse.php
│   │   ├── WarehouseZone.php
│   │   ├── Bin.php
│   │   ├── Product.php
│   │   ├── Batch.php
│   │   ├── InventoryItem.php
│   │   ├── StockMovement.php
│   │   └── InventoryCount.php
│   ├── Enums/                 # Перечисления
│   │   ├── WarehouseTypeEnum.php
│   │   ├── ZoneTypeEnum.php
│   │   ├── MovementTypeEnum.php
│   │   └── OrderTypeEnum.php
│   ├── ValueObjects/          # Value Objects
│   │   ├── WarehouseId.php
│   │   ├── ZoneId.php
│   │   ├── BinId.php
│   │   ├── ProductId.php
│   │   ├── BatchId.php
│   │   └── InventoryCountId.php
│   ├── Events/                # Доменные события
│   │   ├── WarehouseCreated.php
│   │   ├── StockMoved.php
│   │   ├── InventoryCounted.php
│   │   ├── BatchExpiringSoon.php
│   │   └── LowStockAlert.php
│   ├── Exceptions/            # Исключения
│   │   ├── InsufficientStockException.php
│   │   ├── InvalidLocationException.php
│   │   ├── BatchNotFoundException.php
│   │   ├── ProductNotFoundException.php
│   │   └── InventoryCountException.php
│   └── Repositories/          # Repository interfaces
│       ├── WarehouseRepositoryInterface.php
│       ├── ZoneRepositoryInterface.php
│       ├── BinRepositoryInterface.php
│       ├── ProductRepositoryInterface.php
│       ├── BatchRepositoryInterface.php
│       ├── InventoryItemRepositoryInterface.php
│       ├── StockMovementRepositoryInterface.php
│       └── InventoryCountRepositoryInterface.php
├── Infrastructure/
│   ├── Models/                # Eloquent модели
│   │   ├── WarehouseModel.php
│   │   ├── WarehouseZoneModel.php
│   │   ├── BinModel.php
│   │   ├── ProductModel.php
│   │   ├── BatchModel.php
│   │   ├── InventoryItemModel.php
│   │   ├── StockMovementModel.php
│   │   ├── InventoryCountModel.php
│   │   └── InventoryCountItemModel.php
│   ├── Repositories/          # Repository implementations
│   └── Providers/             # Service Providers
├── Application/
│   ├── DTOs/                  # Data Transfer Objects
│   └── Services/              # Application Services
├── Filament/
│   └── Resources/             # Filament admin resources
└── README.md
```

## База данных

### Таблицы

#### warehouses
Основная таблица складов:
```php
- id (UUID, PK)
- tenant_id (FK → tenants)
- name
- address
- branch_id (nullable)
- type (enum: central, regional, local, transit, returns)
- capacity (integer)
- current_stock (integer)
- is_active (boolean)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[tenant_id, is_active]`
- `[tenant_id, branch_id]`
- `[tenant_id, type]`

#### warehouse_zones
Зоны хранения внутри склада:
```php
- id (UUID, PK)
- warehouse_id (FK → warehouses)
- name
- type (enum: receiving, storage, picking, packing, shipping, quarantine, returns)
- capacity (integer)
- current_stock (integer)
- branch_id (nullable)
- is_active (boolean)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[warehouse_id, is_active]`
- `[warehouse_id, type]`
- `[warehouse_id, branch_id]`

#### warehouse_bins
Ячейки хранения (bins):
```php
- id (UUID, PK)
- warehouse_id (FK → warehouses)
- zone_id (FK → warehouse_zones)
- code (unique)
- name
- coordinates (nullable)
- capacity (integer, default 100)
- current_stock (integer)
- is_active (boolean)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[zone_id, is_active]`
- `[warehouse_id, code]`
- `[zone_id, code]` (unique)

#### warehouse_products
Каталог товаров:
```php
- id (UUID, PK)
- sku (unique)
- name
- barcode (unique, nullable)
- description (text, nullable)
- unit (default 'шт')
- weight (decimal 8,3, default 0)
- weight_unit (default 'kg')
- dimensions (JSON, nullable)
- is_hazardous (boolean, default false)
- is_fragile (boolean, default false)
- requires_temperature_control (boolean, default false)
- min_temperature (decimal 5,2, nullable)
- max_temperature (decimal 5,2, nullable)
- category (nullable)
- brand (nullable)
- is_active (boolean)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[sku, is_active]`
- `[barcode]`
- `[category]`
- `[brand]`

#### warehouse_batches
Партионный учет:
```php
- id (UUID, PK)
- product_id (FK → warehouse_products)
- product_sku
- batch_number
- lot_number
- manufacture_date (date)
- expiry_date (date)
- initial_quantity (integer)
- current_quantity (integer)
- purchase_price (decimal 10,2)
- warehouse_id (FK → warehouses)
- zone_id (FK → warehouse_zones, nullable)
- bin_id (FK → warehouse_bins, nullable)
- supplier_id (nullable)
- supplier_name (nullable)
- certificate_number (nullable)
- status (enum: active, expiring_soon, expired, depleted, quarantine)
- notes (text, nullable)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[product_id, expiry_date, current_quantity, status]`
- `[warehouse_id, expiry_date]`
- `[warehouse_id, status, expiry_date]`
- `[product_id, batch_number]` (unique)

#### warehouse_inventory_items
Запасы товаров на складах:
```php
- id (UUID, PK)
- warehouse_id (FK → warehouses)
- zone_id (FK → warehouse_zones, nullable)
- product_sku
- product_name
- quantity (integer)
- reserved_quantity (integer)
- order_type (enum: b2b, b2c, nullable)
- branch_id (nullable)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[warehouse_id, zone_id]`
- `[warehouse_id, product_sku]`
- `[warehouse_id, order_type]`
- `[product_sku, branch_id]`

#### warehouse_stock_movements
Движения товаров:
```php
- id (UUID, PK)
- warehouse_id (FK → warehouses)
- from_zone_id (FK → warehouse_zones, nullable)
- to_zone_id (FK → warehouse_zones, nullable)
- inventory_item_id (FK → warehouse_inventory_items)
- product_sku
- quantity (integer)
- movement_type (enum: receipt, transfer, picking, packing, shipment, return, adjustment, damage, loss, conversion)
- order_type (enum: b2b, b2c, nullable)
- order_id (nullable)
- branch_id (nullable)
- reason (text, nullable)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[warehouse_id, created_at]`
- `[inventory_item_id, created_at]`
- `[product_sku, created_at]`
- `[movement_type, created_at]`
- `[order_id]`

#### warehouse_inventory_counts
Инвентаризации:
```php
- id (UUID, PK)
- warehouse_id (FK → warehouses)
- zone_id (FK → warehouse_zones, nullable)
- count_number (unique)
- count_type (enum: full, partial, cycle, spot)
- status (enum: scheduled, in_progress, completed, approved, cancelled)
- scheduled_date (datetime)
- started_at (datetime, nullable)
- completed_at (datetime, nullable)
- total_items_expected (integer, nullable)
- total_items_counted (integer, nullable)
- discrepancies_found (integer, nullable)
- performed_by (nullable)
- approved_by (nullable)
- approved_at (datetime, nullable)
- notes (text, nullable)
- metadata (JSON)
- correlation_id (indexed)
- timestamps
- soft_deletes
```

**Индексы:**
- `[warehouse_id, status]`
- `[warehouse_id, scheduled_date]`
- `[status, scheduled_date]`

#### warehouse_inventory_count_items
Позиции инвентаризации:
```php
- id (UUID, PK)
- inventory_count_id (FK → warehouse_inventory_counts)
- product_sku
- product_name
- expected_quantity (integer)
- counted_quantity (integer)
- discrepancy (integer)
- bin_code (nullable)
- batch_number (nullable)
- notes (text, nullable)
- metadata (JSON)
- timestamps
- soft_deletes
```

**Индексы:**
- `[inventory_count_id, product_sku]`
- `[inventory_count_id, discrepancy]`

## Domain Entities

### Warehouse
Склад с вместимостью и текущими запасами.

**Методы:**
- `create()` - создание нового склада
- `updateStock()` - обновление общего запаса
- `activate()` / `deactivate()` - управление активностью
- `getUtilizationPercentage()` - процент заполнения

### WarehouseZone
Зона хранения внутри склада.

**Типы зон:**
- `receiving` - зона приёмки
- `storage` - зона хранения
- `picking` - зона комплектации
- `packing` - зона упаковки
- `shipping` - зона отгрузки
- `quarantine` - карантинная зона
- `returns` - зона возвратов

### Bin
Ячейка хранения с координатами.

### Product
Каталог товаров с характеристиками.

**Особенности:**
- Поддержка опасных грузов (`is_hazardous`)
- Поддержка хрупких товаров (`is_fragile`)
- Температурный контроль (`requires_temperature_control`)
- Размеры и вес товара

### Batch
Партия товара с контролем срока годности.

**Методы:**
- `isExpired()` - проверка просрочки
- `isExpiringSoon()` - истекает скоро
- `getDaysUntilExpiry()` - дней до истечения
- `deductQuantity()` - списание из партии
- `setLocation()` - установка местоположения

### InventoryItem
Запас товара на складе с резервированием.

**Методы:**
- `getAvailableQuantity()` - доступное количество
- `reserveQuantity()` - резервирование
- `releaseReservation()` - снятие резерва
- `convertToOrderType()` - конвертация в B2B/B2C

### StockMovement
Движение товара между зонами.

**Типы движений:**
- `receipt` - приход
- `transfer` - перемещение
- `picking` - комплектация
- `packing` - упаковка
- `shipment` - отгрузка
- `return` - возврат
- `adjustment` - корректировка
- `damage` - повреждение
- `loss` - потеря
- `conversion` - конверсия

### InventoryCount
Инвентаризация с этапами.

**Типы инвентаризации:**
- `full` - полная
- `partial` - частичная
- `cycle` - циклическая
- `spot` - точечная

**Статусы:**
- `scheduled` - запланирована
- `in_progress` - в процессе
- `completed` - завершена
- `approved` - утверждена
- `cancelled` - отменена

## Domain Events

### WarehouseCreated
Событие создания склада.

### StockMoved
Событие движения товара.

### InventoryCounted
Событие завершения инвентаризации.

### BatchExpiringSoon
Событие о скором истечении срока годности партии.

### LowStockAlert
Событие о низком остатке товара.

## Использование

### Создание склада

```php
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;

$warehouse = Warehouse::create(
    name: 'Центральный склад Москва',
    address: 'г. Москва, ул. Складская, 1',
    branchId: 'branch-123',
    type: WarehouseTypeEnum::CENTRAL,
    capacity: 10000
);

// Сохранение через repository
$repository->save($warehouse);
```

### Создание партии товара

```php
use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;

$batch = Batch::create(
    productId: ProductId::generate(),
    productSku: 'MED-001',
    batchNumber: 'BATCH-2024-001',
    lotNumber: 'LOT-001',
    manufactureDate: new \DateTimeImmutable('2024-01-01'),
    expiryDate: new \DateTimeImmutable('2026-01-01'),
    initialQuantity: 1000,
    purchasePrice: 150.00,
    warehouseId: WarehouseId::generate(),
    supplierId: 'SUPP-001',
    supplierName: 'Поставщик ООО'
);
```

### FIFO-списание из партий

```php
// Получение ближайшей истекающей партии
$batch = $batchRepository->findActiveByProductSku('MED-001')[0];

if ($batch->isExpiringSoon()) {
    event(new BatchExpiringSoon(
        $batch,
        $batch->getId(),
        $batch->getWarehouseId(),
        $batch->getDaysUntilExpiry()
    ));
}

// Списание
$updatedBatch = $batch->deductQuantity(50);
$batchRepository->save($updatedBatch);
```

### Создание инвентаризации

```php
use Modules\Warehouse\Domain\Entities\InventoryCount;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;

$inventoryCount = InventoryCount::create(
    warehouseId: $warehouseId,
    zoneId: null, // null для полной инвентаризации
    countType: 'full',
    scheduledDate: new \DateTimeImmutable('+1 week'),
    notes: 'Годовая инвентаризация'
);

// Начало инвентаризации
$inventoryCount = $inventoryCount->start('user-123');

// Завершение
$inventoryCount = $inventoryCount->complete(1500, 25);

// Утверждение
$inventoryCount = $inventoryCount->approve('manager-456');
```

## Интеграция с AuditService

Все сервисы Warehouse вертикали интегрированы с `AuditService` через трейт `WithAuditLogging`:

```php
use App\Traits\WithAuditLogging;

class WarehouseService
{
    use WithAuditLogging;

    public function __construct(
        private WarehouseRepositoryInterface $repository,
        private AuditService $auditService
    ) {}

    public function createWarehouse(CreateWarehouseDTO $dto): Warehouse
    {
        $warehouse = Warehouse::create(...);
        $this->repository->save($warehouse);
        
        $this->logCreated('warehouse', $warehouse->getId()->toString(), [
            'name' => $warehouse->getName(),
            'type' => $warehouse->getType()->value,
        ]);
        
        return $warehouse;
    }
}
```

## Performance

### Оптимизации

- **Композитные индексы** для FIFO-запросов по партиям
- **UUID primary keys** для распределённых систем
- **Soft deletes** для сохранения истории
- **JSON metadata** для гибкости
- **Correlation IDs** для трассировки

### Рекомендуемые индексы

```sql
-- Для FIFO по партиям
CREATE INDEX idx_batches_fifo ON warehouse_batches 
(product_id, expiry_date, current_quantity, status);

-- Для движений по времени
CREATE INDEX idx_movements_time ON warehouse_stock_movements 
(warehouse_id, created_at);

-- Для инвентаризаций
CREATE INDEX idx_counts_status ON warehouse_inventory_counts 
(warehouse_id, status, scheduled_date);
```

## Security & Compliance

### Федеральные Законы (Compliance)

Система обеспечивает compliance с российскими федеральными законами:

#### 152-ФЗ (Персональные данные)
- **PIIProtectionService** - автоматическая анонимизация PII в логах
- **DataRetentionService** - политика хранения данных (1-7 лет)
- **Конфигурация** - настройка через `config/warehouse.php`
- **Анонимизация**: имена, email, телефоны, адреса, ИНН/КПП/ОГРН

```php
$piiService->anonymizeForLogs('Иванов Иван Иванович', 'name');
// Результат: И*** И*** И***

$piiService->logWithPIIProtection('Stock movement created', $context);
// Автоматическая анонимизация PII в логах
```

#### ФЗ-323 (Об основах охраны здоровья)
- **LicenseManagementService** - проверка лицензий на фармацевтическую деятельность
- **Cold Chain Control** - мониторинг температурного режима для лекарств
- **Expiry Control** - блокировка партий за 30 дней до истечения срока годности
- **Storage Requirements** - проверка требований к помещениям

```php
$licenseService->validateWarehouseLicense($warehouse, 'pharmaceutical');
$licenseService->validateTemperatureRequirements($product, 5.0, 60.0);
$licenseService->validateBatchExpiry($batch);
```

#### ФЗ-61 (Обращение лекарственных средств)
- **ChestnyZnakService** - интеграция с системой Честный ЗНАК
- **MarkedProduct** entity - товары с кодом маркировки DataMatrix
- **Registration** - регистрация прихода/отгрузки/списания в системе маркировки
- **Validation** - проверка формата кодов маркировки

```php
$chestnyZnak->checkMarkingCode('(01)04600000000000(21)1234567(17)260426(10)ABC123)');
$documentId = $chestnyZnak->registerReceipt($documentNumber, $documentDate, $markedProducts);
```

#### Контроль наркотических/психотропных веществ
- **ControlledSubstancesService** - управление Списками I, II, III
- **Dual Control** - двойной контроль доступа для критических операций
- **Limits** - дневные и месячные лимиты выдачи
- **FSB Reporting** - генерация отчетов для ФСБ

```php
$controlledService->isControlledSubstance('narcotic');
$list = $controlledService->determineControlledList('psychotropic');
$controlledService->logControlledOperation($userId, $secondUserId, 'issue', $productId, $quantity, $reason);
```

### RBAC (Role-Based Access Control)

Реализованы Policies для всех сущностей:
- **WarehousePolicy** - управление складами
- **InventoryItemPolicy** - управление запасами
- **StockMovementPolicy** - управление движениями
- **InventoryCountPolicy** - управление инвентаризациями
- **BatchPolicy** - управление партиями
- **ProductPolicy** - управление товарами
- **BinPolicy** - управление ячейками

**Segregation of Duties:**
- Создание и утверждение инвентаризации - разные роли
- Работа с контролируемыми веществами - только менеджеры
- Двойной контроль для Списков I и II

### Audit Trail

**AuditTrailService** обеспечивает детальный аудит:
- Кто изменил (user_id)
- Что изменил (old_values, new_values, changes)
- Когда изменил (timestamp)
- С какого IP (ip_address)
- С какой причиной (reason)

**Типы событий:**
- `warehouse_created` / `warehouse_updated`
- `stock_movement` / `batch_deducted` / `batch_quarantined`
- `inventory_count_started` / `inventory_count_completed` / `inventory_count_approved`
- `pii_access` / `unauthorized_attempt`

### Права доступа

- Tenant isolation через `tenant_id`
- Branch-level фильтрация через `branch_id`
- Audit-лог всех действий через `correlation_id`
- PII защита через автоматическую анонимизацию

### Дополнительная документация

- **COMPLIANCE_README.md** - детальная документация по compliance
- **COMPLIANCE_MIGRATION.md** - миграции для compliance таблиц

## Monitoring

### Метрики

Рекомендуемые метрики для мониторинга:

- **Warehouse utilization** - процент заполнения складов
- **Stock accuracy** - точность запасов (по результатам инвентаризаци)
- **Batch expiry rate** - процент просроченных партий
- **Movement velocity** - скорость оборота товаров
- **Pick accuracy** - точность комплектации

### Логи

Все операции логируются с контекстом:

```php
Log::info('Stock movement created', [
    'movement_id' => $movement->getId(),
    'product_sku' => $movement->getProductSku(),
    'quantity' => $movement->getQuantity(),
    'movement_type' => $movement->getMovementType()->value,
    'warehouse_id' => $movement->getWarehouseId()->toString(),
    'correlation_id' => $correlationId,
]);
```

## Будущие улучшения

- [ ] Application Services (WarehouseService, StockService, MovementService, InventoryService)
- [ ] Repository implementations
- [ ] Application DTOs
- [ ] Filament Resources
- [ ] Service Provider
- [ ] API Endpoints
- [ ] WebSocket для real-time обновлений
- [ ] Интеграция с модулями других вертикалей
- [ ] Автоматическое пополнение на основе прогнозов
- [ ] RFSC-сканеры для быстрой инвентаризации
- [ ] Интеграция с IoT датчиками

## Интеграция с другими вертикалями

### Pharmacy Vertical
- Автоматическое списание лекарственных препаратов
- Контроль срока годности (152-ФЗ)
- Партионный учет для трассировки

### Restaurant Vertical
- Управление запасами продуктов
- FIFO по сроку годности
- Инвентаризация kitchens

### BeautyMasters Vertical
- Управление расходными материалами
- Контроль косметики с сроком годности

## Контакты

Для вопросов по Warehouse вертикали обращайтесь к команде разработки CatVRF.

---

**Версия:** 1.0.0  
**Дата:** 28.04.2026  
**Автор:** CatVRF Development Team
