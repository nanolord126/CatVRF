# Warehouse & Inventory Vertical - CatVRF

## Overview

The Warehouse & Inventory vertical provides comprehensive warehouse management and inventory tracking capabilities for the CatVRF multi-vertical marketplace platform. It implements full compliance with Russian regulatory requirements (ФЗ-323, ФЗ-61, 152-ФЗ) and supports pharmaceutical-grade cold chain monitoring.

## Architecture

### Clean Architecture Layers

```
app/
├── Domains/
│   └── Logistics/
│       └── WarehouseRentals/
│           └── Models/
│               └── Warehouse.php          # Domain entity
├── Models/
│   ├── WarehouseProduct.php              # Product catalog
│   ├── WarehouseBatch.php                # Batch tracking (FIFO)
│   ├── WarehouseBin.php                  # Storage bins
│   ├── WarehouseZone.php                 # Functional zones
│   ├── WarehouseStockMovement.php        # Movement audit trail
│   ├── WarehouseLicense.php              # Compliance licenses
│   ├── WarehouseDocument.php             # Document workflow
│   ├── WarehouseChestnyZnakDocument.php # Честный ЗНАК integration
│   ├── ColdChainReading.php             # Temperature monitoring
│   ├── TemperatureAlert.php              # Temperature alerts
│   ├── TemperatureViolation.php          # Compliance violations
│   └── InventoryItem.php                # Inventory items
├── Services/
│   └── Inventory/
│       ├── FIFOShelfLifeService.php     # FIFO allocation logic
│       ├── InventoryManagementService.php
│       ├── WarehouseTransferService.php
│       ├── WarehouseLocationService.php
│       ├── WarehouseCapacityService.php
│       └── ... (50+ services)
├── Repositories/
│   ├── Contracts/                       # Repository interfaces
│   │   ├── WarehouseRepositoryInterface.php
│   │   ├── InventoryItemRepositoryInterface.php
│   │   └── StockMovementRepositoryInterface.php
│   └── Eloquent/                        # Eloquent implementations
│       ├── WarehouseRepository.php
│       ├── InventoryItemRepository.php
│       └── StockMovementRepository.php
├── DTOs/
│   └── Inventory/
│       ├── AllocateStockDto.php
│       ├── CreateTransferDto.php
│       └── UpdateStockDto.php
└── Events/
    └── Inventory/
        ├── StockMoved.php
        ├── StockLowAlert.php
        └── StockExpired.php
```

### Design Patterns

- **Repository Pattern**: Clean separation between data access and business logic
- **DTO Pattern**: Immutable data transfer objects for service boundaries
- **Event-Driven**: Async event system for cache invalidation and notifications
- **Tenant-Scoped**: Multi-tenant isolation via global scopes
- **Optimistic Locking**: Version-based concurrency control

## Setup Instructions

### 1. Database Migrations

Run migrations to create warehouse and inventory tables:

```bash
php artisan migrate
```

Key migrations:
- `2026_04_27_212700_create_warehouses_table.php`
- `2026_04_27_212700_create_warehouse_products_table.php`
- `2026_04_27_212700_create_warehouse_batches_table.php`
- `2026_04_27_212700_create_warehouse_bins_table.php`
- `2026_04_27_212700_create_warehouse_zones_table.php`
- `2026_04_27_212700_create_warehouse_stock_movements_table.php`
- `2026_04_28_000005_create_warehouse_licenses_table.php`
- `2026_04_28_000001_create_warehouse_documents_table.php`
- `2026_04_28_000008_create_cold_chain_readings_table.php`

### 2. Configuration

Configure warehouse settings in `config/warehouse.php`:

```php
return [
    'pii' => [
        'salt' => env('WAREHOUSE_PII_SALT'),
        'auto_anonymize_logs' => true,
        'encrypt_sensitive_fields' => true,
    ],
    'retention' => [
        'logs' => '1 year',
        'inventory_counts' => '5 years',
        'stock_movements' => '7 years',
    ],
    'cold_chain' => [
        'temperature_ranges' => [
            'standard' => ['min' => 2.0, 'max' => 25.0],
            'refrigerated' => ['min' => 2.0, 'max' => 8.0],
            'frozen' => ['min' => -25.0, 'max' => -10.0],
        ],
        'monitoring_interval' => 15, // minutes
    ],
];
```

Configure inventory settings in `config/inventory.php`:

```php
return [
    'cache' => [
        'ttl_dynamic' => 60,
        'ttl_stable' => 300,
        'prefix' => 'inventory:stock:',
    ],
    'low_stock' => [
        'check_interval' => 'daily',
        'check_time' => '08:00',
        'notification_enabled' => true,
    ],
];
```

### 3. Environment Variables

Add to `.env`:

```env
# Warehouse PII Protection
WAREHOUSE_PII_SALT=your_secure_salt_here
WAREHOUSE_PII_AUTO_ANONYMIZE=true
WAREHOUSE_PII_ENCRYPT=true

# Data Retention
WAREHOUSE_RETENTION_LOGS=1 year
WAREHOUSE_RETENTION_INVENTORY_COUNTS=5 years
WAREHOUSE_RETENTION_STOCK_MOVEMENTS=7 years

# Cold Chain Monitoring
WAREHOUSE_COLD_CHAIN_INTERVAL=15

# Chestny ZNAK Integration
WAREHOUSE_CHESTNY_ZNAK_ENABLED=false
WAREHOUSE_CHESTNY_ZNAK_API_URL=
WAREHOUSE_CHESTNY_ZNAK_API_KEY=
WAREHOUSE_CHESTNY_ZNAK_CERT_PATH=

# EGISZ Integration
WAREHOUSE_EGISZ_ENABLED=false
WAREHOUSE_EGISZ_API_URL=
WAREHOUSE_EGISZ_API_KEY=
```

### 4. Service Providers

Register repository bindings in `AppServiceProvider`:

```php
$this->app->bind(
    \App\Repositories\Contracts\WarehouseRepositoryInterface::class,
    \App\Repositories\Eloquent\WarehouseRepository::class
);

$this->app->bind(
    \App\Repositories\Contracts\InventoryItemRepositoryInterface::class,
    \App\Repositories\Eloquent\InventoryItemRepository::class
);

$this->app->bind(
    \App\Repositories\Contracts\StockMovementRepositoryInterface::class,
    \App\Repositories\Eloquent\StockMovementRepository::class
);
```

Register event listeners in `EventServiceProvider`:

```php
protected $listen = [
    \App\Events\Inventory\StockMoved::class => [
        \App\Listeners\Inventory\InvalidateInventoryCache::class,
    ],
    \App\Events\Inventory\StockLowAlert::class => [
        \App\Listeners\Inventory\SendLowStockNotification::class,
    ],
    \App\Events\Inventory\StockExpired::class => [
        \App\Listeners\Inventory\HandleExpiredStock::class,
    ],
];
```

## Compliance Requirements

### ФЗ-323 (Healthcare Compliance)

- **License Management**: Pharmaceutical licenses tracked with expiry dates
- **Cold Chain**: Temperature monitoring for sensitive products
- **Batch Tracking**: FIFO allocation with expiry date control
- **Document Workflow**: Approval workflow for all operations
- **Audit Trail**: Complete audit logging with correlation IDs

### ФЗ-61 (Chestny ZNAK)

- **Marking System**: Integration with Честный ЗНАК API
- **Document Lifecycle**: Track document status from creation to signing
- **Signature Support**: Electronic signature storage
- **Synchronization**: Automatic sync with government system

### 152-ФЗ (Personal Data)

- **PII Anonymization**: Automatic PII masking in logs
- **Encryption**: Sensitive fields encrypted at rest
- **Access Control**: Role-based access to PII data
- **Data Retention**: Configurable retention periods per law

## API Endpoints Overview

### Warehouse Management

```
GET    /api/v1/warehouses
POST   /api/v1/warehouses
GET    /api/v1/warehouses/{id}
PUT    /api/v1/warehouses/{id}
DELETE /api/v1/warehouses/{id}

GET    /api/v1/warehouses/{id}/zones
POST   /api/v1/warehouses/{id}/zones
GET    /api/v1/warehouses/{id}/bins
GET    /api/v1/warehouses/{id}/licenses
GET    /api/v1/warehouses/{id}/documents
```

### Inventory Management

```
GET    /api/v1/inventory/items
POST   /api/v1/inventory/items
GET    /api/v1/inventory/items/{id}
PUT    /api/v1/inventory/items/{id}
POST   /api/v1/inventory/items/{id}/stock
POST   /api/v1/inventory/items/{id}/reserve
POST   /api/v1/inventory/items/{id}/release

GET    /api/v1/inventory/batches
POST   /api/v1/inventory/batches
GET    /api/v1/inventory/batches/{id}
PUT    /api/v1/inventory/batches/{id}

GET    /api/v1/inventory/transfers
POST   /api/v1/inventory/transfers
GET    /api/v1/inventory/transfers/{id}
POST   /api/v1/inventory/transfers/{id}/execute
```

### Cold Chain Monitoring

```
GET    /api/v1/cold-chain/readings
POST   /api/v1/cold-chain/readings
GET    /api/v1/cold-chain/alerts
GET    /api/v1/cold-chain/alerts/{id}/resolve
GET    /api/v1/cold-chain/violations
```

## Key Services

### FIFOShelfLifeService

Implements First-In-First-Out allocation logic for products with expiry dates.

```php
use App\Services\Inventory\FIFOShelfLifeService;

$fifoService = app(FIFOShelfLifeService::class);

$result = $fifoService->allocateFromFIFO(
    itemId: 123,
    quantity: 50,
    context: 'sale',
    userId: 1,
    tenantId: 1
);
```

### WarehouseTransferService

Manages stock transfers between warehouses.

```php
use App\Services\Inventory\WarehouseTransferService;

$transferService = app(WarehouseTransferService::class);

$transferId = $transferService->createTransferOrder(
    fromWarehouseId: 1,
    toWarehouseId: 2,
    items: [['item_id' => 123, 'quantity' => 50]],
    reason: 'Stock rebalancing',
    userId: 1,
    tenantId: 1
);
```

### InventoryManagementService

Core inventory management operations.

```php
use App\Services\Inventory\InventoryManagementService;

$inventoryService = app(InventoryManagementService::class);

$currentStock = $inventoryService->getCurrentStock(123);
```

## Testing

Run tests:

```bash
# Unit tests
php artisan test --testsuite=Unit --filter=Warehouse

# Feature tests
php artisan test --testsuite=Feature --filter=Inventory

# Integration tests
php artisan test tests/Integration/WarehouseIntegrationTest.php
```

## Monitoring

### Key Metrics

- **Warehouse Utilization**: Current stock / capacity percentage
- **Stock Accuracy**: Physical count vs system count
- **Pick Rate**: Orders picked per hour
- **Cycle Count Accuracy**: Discrepancy percentage
- **Cold Chain Compliance**: Temperature violation rate

### Alerting

- **Low Stock**: When items fall below minimum threshold
- **Expiry Alerts**: 30, 14, 7 days before expiry
- **Temperature Violations**: When outside allowed range
- **License Expiry**: 90, 30, 7 days before license expiry

## Performance Optimization

### Caching

Inventory queries use cache tags for efficient invalidation:

```php
Cache::tags(['inventory', 'item:' . $itemId])->remember(300, function () {
    return InventoryItem::find($itemId);
});
```

### Database Indexes

Key indexes for performance:
- `warehouses(tenant_id, is_active)`
- `warehouse_batches(product_id, expiry_date, current_quantity, status)`
- `warehouse_stock_movements(warehouse_id, created_at)`
- `cold_chain_readings(warehouse_id, recorded_at)`

### Queue Processing

Heavy operations use queues:
- Stock synchronization
- Low stock notifications
- Expiry checks
- Cold chain alerts

## Security

### Fraud Detection

All stock operations pass through FraudControlService:

```php
$fraudResult = $fraudService->check([
    'action' => 'inventory_allocate',
    'item_id' => $itemId,
    'quantity' => $quantity,
    'user_id' => $userId,
    'tenant_id' => $tenantId,
]);
```

### Audit Logging

All operations logged via AuditService with correlation IDs:

```php
$this->logAction(
    'inventory_stock_updated',
    'InventoryItem',
    $itemId,
    ['quantity' => $quantity],
    $userId,
    $tenantId
);
```

## Troubleshooting

### Common Issues

1. **Race Conditions**: Use optimistic locking with version column
2. **Cache Staleness**: Event-driven cache invalidation
3. **Tenant Isolation**: Global scopes ensure tenant separation
4. **PII Leaks**: EncryptedPIICast and log masking

### Debug Mode

Enable detailed logging:

```php
config(['logging.channels.inventory.level' => 'debug']);
```

## Support

For issues or questions:
- Documentation: `docs/WAREHOUSE_README.md`
- API Docs: `docs/WAREHOUSE_API.md`
- Architecture: See Clean Architecture section above

---

**Version**: 2026.04.28  
**Author**: CatVRF Team  
**License**: Proprietary
