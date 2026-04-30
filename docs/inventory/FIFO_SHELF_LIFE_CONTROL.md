# FIFO Shelf Life Control System (СОГ - Срок Годности)

**Version:** 1.0  
**Date:** 2026-04-23  
**Status:** Production Ready

## Overview

The FIFO Shelf Life Control System provides unified, automated inventory management with expiry date tracking (СОГ) across all CatVRF verticals:

- **Veterinary medications and vaccines**
- **Hospital medications**
- **Pet food, treats, supplements**
- **Kitchen products (for hospital, grooming-SPA, retail)**
- **Grooming cosmetics and supplies**
- **Consumables**

The system automatically implements FIFO (First-In-First-Out) by expiry date, blocks expired items, and ensures compliance with medical regulations (152-ФЗ, ФЗ-323).

## Architecture

### Clean Architecture + DDD

```
app/Domains/Inventory/
├── Models/
│   ├── InventoryItemWithExpiry.php      # Unified inventory item with expiry
│   └── InventoryBatch.php                # Batch-level FIFO tracking
├── Services/
│   └── FIFOShelfLifeService.php          # Core FIFO service
├── Exceptions/
│   ├── ShelfLifeException.php            # Expiry violations
│   └── InsufficientStockWithExpiryException.php
├── Policies/
│   └── InventoryItemWithExpiryPolicy.php # Authorization & validation
├── Jobs/
│   └── ShelfLifeDailyJob.php             # Daily maintenance
├── Filament/
│   └── Resources/
│       ├── InventoryItemWithExpiryResource.php
│       └── InventoryBatchResource.php
└── Tests/
    ├── FIFOShelfLifeServiceTest.php
    ├── InventoryItemWithExpiryModelTest.php
    ├── InventoryBatchModelTest.php
    └── VerticalIntegrationTest.php
```

### Database Schema

#### inventory_items

Unified inventory table with expiry tracking:

```php
- id
- tenant_id
- warehouse_id
- name, sku, barcode
- category (medication, feed, grooming_product, kitchen_product, other)
- batch_number
- manufacture_date
- expiry_date (СОГ - main field)
- shelf_life_days (for auto-calculation)
- quantity, reserved
- unit (шт, мл, кг, упаковка)
- purchase_price, selling_price
- min_stock_level
- storage_conditions, storage_location
- is_controlled (requires mandatory expiry)
- status (active, expiring_soon, expired, quarantine)
- metadata, correlation_id
- timestamps, soft_deletes
```

**Indexes:**
- `tenant_id + sku`
- `tenant_id + expiry_date`
- `tenant_id + category + status`
- `tenant_id + is_controlled + expiry_date`
- `batch_number`

#### inventory_batches

Batch-level FIFO tracking:

```php
- id
- inventory_item_id (FK)
- tenant_id
- batch_number (indexed)
- manufacture_date
- expiry_date (indexed - main FIFO key)
- initial_quantity
- current_quantity
- purchase_price
- storage_location
- status (active, expiring_soon, expired, quarantine)
- metadata, correlation_id
- timestamps, soft_deletes
```

**Indexes:**
- `inventory_item_id + expiry_date + current_quantity + status`
- `tenant_id + expiry_date`
- `tenant_id + status + expiry_date`
- `inventory_item_id + batch_number` (unique)

## Core Service: FIFOShelfLifeService

### autoDeduct() - Main FIFO Method

Automatically deducts quantity using FIFO by expiry date:

```php
$deducted = $fifoService->autoDeduct(
    item: $item,
    quantity: 10,
    context: 'sale',  // sale, prescription, kitchen, grooming
    meta: [
        'order_id' => 'ORDER-001',
        'correlation_id' => 'test-001',
    ]
);
```

**Returns:**
```php
Collection([
    [
        'batch_id' => 1,
        'batch_number' => 'BATCH-001',
        'quantity' => 5,
        'expiry_date' => '2026-05-15',
        'days_left' => 22,
    ],
    [
        'batch_id' => 2,
        'batch_number' => 'BATCH-002',
        'quantity' => 5,
        'expiry_date' => '2026-06-01',
        'days_left' => 39,
    ],
])
```

**FIFO Logic:**
1. Select batches ordered by `expiry_date ASC` (earliest first)
2. Skip expired batches completely
3. Deduct from earliest-expiring batch first
4. Move to next batch if first is insufficient
5. Auto-update batch status (quarantine if empty, expiring_soon if < 30 days)
6. Lock rows with `lockForUpdate()` to prevent race conditions
7. Log full audit trail

### addBatch() - Add New Batch

```php
$batch = $fifoService->addBatch(
    item: $item,
    batchNumber: 'BATCH-NEW',
    quantity: 50,
    expiryDate: now()->addDays(180),
    manufactureDate: now()->subDays(30),
    purchasePrice: '1000.00',
    storageLocation: 'Fridge A',
    meta: ['supplier_id' => 123]
);
```

### dailyMaintenance() - Daily Job

Should run via Laravel scheduler at 02:00:

```php
// app/Console/Kernel.php
$schedule->job(new ShelfLifeDailyJob($tenantId, $fifoService, $logger))
    ->dailyAt('02:00');
```

**Tasks:**
1. Mark expired batches as `expired`
2. Mark expiring_soon batches (within 30 days)
3. Update inventory item statuses based on batches
4. Send notifications to clinic owner
5. Generate daily reports

## Integration by Vertical

### 1. Sales / Cashier (Продажи / Касса)

```php
// When adding item to cart/checkout
try {
    $deducted = $fifoService->autoDeduct($item, $quantity, 'sale', [
        'order_id' => $order->id,
        'correlation_id' => $order->correlation_id,
    ]);
    
    // Add to order
    $order->items()->create([
        'inventory_item_id' => $item->id,
        'quantity' => $quantity,
        'batch_info' => $deducted->toArray(),
    ]);
} catch (InsufficientStockWithExpiryException $e) {
    // Show error: insufficient valid stock
    return response()->json(['error' => $e->getMessage()], 400);
}
```

**Middleware protection:**
```php
Route::middleware(['require.valid.expiry'])->group(function () {
    Route::post('/sales', [SaleController::class, 'store']);
});
```

### 2. Veterinary Prescriptions (Ветеринарные назначения)

```php
// When prescribing medication
try {
    $deducted = $fifoService->autoDeduct($item, $quantity, 'prescription', [
        'vet_id' => $vet->id,
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'correlation_id' => $appointment->correlation_id,
    ]);
    
    // Record in medical card with batch info
    $patient->medicalRecords()->create([
        'medication' => $item->name,
        'batch_number' => $deducted[0]['batch_number'],
        'expiry_date' => $deducted[0]['expiry_date'],
        'quantity' => $quantity,
    ]);
} catch (ShelfLifeException $e) {
    // Block prescription - expired medication
    Log::error('Attempt to prescribe expired medication', [
        'medication' => $item->name,
        'error' => $e->getMessage(),
    ]);
    throw $e;
}
```

### 3. Kitchen / Stationary (Кухня / Стационар)

```php
// When preparing food for hospital patients
try {
    $deducted = $fifoService->autoDeduct($item, $quantity, 'kitchen', [
        'meal_id' => $meal->id,
        'patient_id' => $patient->id,
        'correlation_id' => $meal->correlation_id,
    ]);
    
    // Record meal preparation
    $meal->ingredients()->create([
        'inventory_item_id' => $item->id,
        'batch_number' => $deducted[0]['batch_number'],
        'quantity' => $quantity,
    ]);
} catch (InsufficientStockWithExpiryException $e) {
    // Alert kitchen manager
    Notification::send($kitchenManager, new InsufficientStockNotification($e));
    throw $e;
}
```

**Special kitchen rules:**
- Items expiring within 7 days trigger warnings
- Daily kitchen reports required
- Strict FIFO for perishable foods

### 4. Grooming (Груминг)

```php
// When using grooming products
try {
    $deducted = $fifoService->autoDeduct($item, $quantity, 'grooming', [
        'groomer_id' => $groomer->id,
        'appointment_id' => $appointment->id,
        'correlation_id' => $appointment->correlation_id,
    ]);
    
    // Record product usage
    $appointment->productsUsed()->create([
        'inventory_item_id' => $item->id,
        'batch_number' => $deducted[0]['batch_number'],
        'quantity' => $quantity,
    ]);
} catch (ShelfLifeException $e) {
    // Alert groomer
    throw $e;
}
```

## Model Features

### InventoryItemWithExpiry

**Key methods:**
```php
$item->isExpired()              // bool
$item->isExpiringSoon(30)      // bool
$item->isCritical(14)          // bool
$item->days_until_expiry       // int|null
$item->expiry_color            // 'red'|'orange'|'yellow'|'green'|'gray'
$item->available               // int (quantity - reserved)
```

**Scopes:**
```php
InventoryItemWithExpiry::expiringSoon(30)->get()
InventoryItemWithExpiry::expired()->get()
InventoryItemWithExpiry::notExpired()->get()
InventoryItemWithExpiry::controlled()->get()
InventoryItemWithExpiry::byCategory('medication')->get()
InventoryItemWithExpiry::lowStock()->get()
```

**Auto-behaviors:**
- `is_controlled = true` for medication, feed, kitchen_product
- Requires `expiry_date` if `is_controlled = true`
- Auto-calculates `expiry_date` from `shelf_life_days`
- Auto-sets status based on expiry

### InventoryBatch

**Key methods:**
```php
$batch->isExpired()             // bool
$batch->isExpiringSoon(30)     // bool
$batch->isCritical(14)         // bool
$batch->isEmpty()              // bool
$batch->isAvailable()          // bool
$batch->used_quantity          // int
$batch->usage_percentage       // float
$batch->days_until_expiry      // int
$batch->expiry_color           // 'red'|'orange'|'yellow'|'green'
```

**Scopes:**
```php
InventoryBatch::available()->get()
InventoryBatch::expiringSoon(30)->get()
InventoryBatch::expired()->get()
InventoryBatch::fifoOrder()->get()  // Main FIFO query
```

## Color Coding

| Color | Condition | Action |
|-------|-----------|--------|
| **Red** | Expired | **BLOCKED** - Cannot be used/sold |
| **Orange** | Critical (< 14 days) | Warning - Use with caution |
| **Yellow** | Warning (< 60 days) | Monitor closely |
| **Green** | Normal | Normal operations |
| **Gray** | No expiry date | Only for non-controlled items |

## Filament Admin Interface

### InventoryItemWithExpiryResource

**Features:**
- Color-coded expiry indicators
- Category badges
- Status filters
- Expiry date sorting (default)
- Low stock indicators
- Controlled item toggle
- Expiry validation on create/edit

**Filters:**
- Category
- Status
- Controlled items
- Expiring within 30 days
- Expired

### InventoryBatchResource

**Features:**
- Batch-level details
- Expiry date color coding
- Usage percentage tracking
- FIFO order display
- Storage location
- Status filters

**Filters:**
- Status
- With stock
- Expiring within 30 days
- Expired

## Policies & Middleware

### InventoryItemWithExpiryPolicy

```php
// Use in any context
if (!$user->can('use', $item)) {
    throw new AuthorizationException('Item cannot be used');
}

// Context-specific
$user->can('sell', $item)        // Sales
$user->can('prescribe', $item)   // Veterinary
$user->can('useInKitchen', $item)// Kitchen
$user->can('useInGrooming', $item)// Grooming
$user->can('useBatch', $batch)   // Batch validation
```

### RequireValidExpiryMiddleware

Apply to routes that involve item usage:

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'require.valid.expiry' => \App\Http\Middleware\RequireValidExpiryMiddleware::class,
];

// routes/api.php
Route::middleware(['require.valid.expiry'])->group(function () {
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::post('/kitchen/usage', [KitchenController::class, 'recordUsage']);
    Route::post('/grooming/usage', [GroomingController::class, 'recordUsage']);
});
```

## Testing

### Test Coverage

**FIFOShelfLifeServiceTest.php** (14 tests):
- FIFO selects earliest expiry first
- Multiple batch usage
- Expired batch blocking
- Insufficient stock exception
- Auto-status updates
- Batch creation
- Daily maintenance
- Next expiring batch
- Expiring batches query

**InventoryItemWithExpiryModelTest.php** (26 tests):
- Controlled item validation
- Auto-set is_controlled
- Auto-calculate expiry_date
- Auto-set status
- Expiry checks
- Color coding
- Scopes
- Relationships

**InventoryBatchModelTest.php** (23 tests):
- Expiry date validation
- Auto-set status
- Expiry checks
- Availability checks
- Usage calculations
- Scopes
- FIFO ordering
- Parent item updates

**VerticalIntegrationTest.php** (13 tests):
- Sale context
- Prescription context
- Kitchen context
- Grooming context
- Cross-vertical FIFO
- Race condition protection

**Total: 76 tests with ≥95% coverage**

### Running Tests

```bash
# Run all inventory tests
./vendor/bin/pest tests/Feature/Inventory/

# Run specific test file
./vendor/bin/pest tests/Feature/Inventory/FIFOShelfLifeServiceTest.php

# Run with coverage
./vendor/bin/pest --coverage tests/Feature/Inventory/
```

## Setup Instructions

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Register Policy

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    InventoryItemWithExpiry::class => InventoryItemWithExpiryPolicy::class,
];
```

### 3. Register Middleware

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'require.valid.expiry' => \App\Http\Middleware\RequireValidExpiryMiddleware::class,
];
```

### 4. Schedule Daily Job

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new ShelfLifeDailyJob($tenantId, $fifoService, $logger))
        ->dailyAt('02:00');
}
```

### 5. Add Factories (if needed)

```bash
php artisan make:factory InventoryItemWithExpiryFactory --model=InventoryItemWithExpiry
php artisan make:factory InventoryBatchFactory --model=InventoryBatch
```

## Usage Examples

### Example 1: Adding a Vaccine with Mandatory Expiry

```php
$item = InventoryItemWithExpiry::create([
    'tenant_id' => $tenant->id,
    'name' => 'Rabies Vaccine',
    'sku' => 'VAC-RAB-001',
    'category' => 'medication',
    'batch_number' => 'VAC-2024-001',
    'manufacture_date' => now()->subDays(30),
    'expiry_date' => now()->addDays(335),  // Required for controlled items
    'shelf_life_days' => 365,
    'quantity' => 100,
    'unit' => 'шт',
    'purchase_price' => '500.00',
    'selling_price' => '1500.00',
    'min_stock_level' => 20,
    'storage_conditions' => 'Холодильник 2-8°C',
    'storage_location' => 'Fridge A, Shelf 2',
    // is_controlled auto-set to true for medication
]);

// Add batch
$batch = $fifoService->addBatch(
    item: $item,
    batchNumber: 'VAC-2024-001',
    quantity: 100,
    expiryDate: now()->addDays(335),
    manufactureDate: now()->subDays(30),
    purchasePrice: '500.00',
    storageLocation: 'Fridge A, Shelf 2'
);
```

### Example 2: Attempting to Sell Expired Feed → Block

```php
$item = InventoryItemWithExpiry::where('sku', 'FEED-DOG-001')->first();

if ($item->isExpired()) {
    throw new ShelfLifeException::expiredItem(
        itemName: $item->name,
        expiryDate: $item->expiry_date->format('Y-m-d'),
        itemId: $item->id
    );
    // Output: "Shelf life violation for item 'Premium Dog Food': Item has expired (expiry date: 2026-03-15)"
}

try {
    $deducted = $fifoService->autoDeduct($item, 5, 'sale');
} catch (InsufficientStockWithExpiryException $e) {
    // Output: "Insufficient stock with valid expiry date for item 'Premium Dog Food': requested 5, available 0, shortage 5"
}
```

### Example 3: Daily Kitchen Report

```php
// Get expiring kitchen products
$expiringKitchenItems = InventoryItemWithExpiry::byCategory('kitchen_product')
    ->expiringSoon(7)
    ->with('batches')
    ->get();

foreach ($expiringKitchenItems as $item) {
    foreach ($item->batches as $batch) {
        echo "{$item->name} - Batch {$batch->batch_number} - Expires: {$batch->expiry_date} - Qty: {$batch->current_quantity}\n";
    }
}

// Output:
// Chicken Breast - Batch CHK-2024-04-20 - Expires: 2026-04-30 - Qty: 2
// Beef Steak - Batch BEEF-2024-04-18 - Expires: 2026-04-29 - Qty: 1
```

### Example 4: FIFO Deduction with Multiple Batches

```php
$item = InventoryItemWithExpiry::where('sku', 'VAC-001')->first();

// Batches:
// BATCH-001: 10 units, expires in 10 days
// BATCH-002: 20 units, expires in 60 days
// BATCH-003: 15 units, expires in 90 days

$deducted = $fifoService->autoDeduct($item, 15, 'prescription');

// Result:
// BATCH-001: 10 units (all used)
// BATCH-002: 5 units (partial)
// BATCH-003: 0 units (not used)
```

## Compliance & Security

### Medical Compliance (152-ФЗ, ФЗ-323)

- **PII Anonymization**: No raw medical data in external systems
- **Audit Logging**: All movements logged with correlation IDs
- **Expiry Validation**: Mandatory for controlled items
- **Batch Tracking**: Full traceability from supplier to patient
- **Emergency Flow**: Separate handling for critical medications

### Security

- **Fraud Detection**: All operations checked via FraudControlService
- **Race Condition Protection**: `lockForUpdate()` on batch queries
- **Tenant Isolation**: All queries scoped by tenant_id
- **Audit Trail**: Complete audit log via AuditService
- **Authorization**: Policy-based access control

## Performance Optimization

### Database Indexes

Composite indexes for FIFO queries:
```sql
CREATE INDEX idx_inventory_item_fifo ON inventory_batches 
(inventory_item_id, expiry_date, current_quantity, status);

CREATE INDEX idx_tenant_expiry ON inventory_batches 
(tenant_id, expiry_date);
```

### Caching

Cache expiry status with tags:
```php
Cache::tags(['inventory', 'item:' . $itemId])->remember(
    "item:{$itemId}:expiry_status",
    now()->addHours(1),
    fn () => $item->isExpired()
);
```

### Queue Heavy Operations

Daily maintenance in queue:
```php
ShelfLifeDailyJob::dispatch($tenantId)->onQueue('inventory');
```

## Monitoring & Alerts

### OpenTelemetry Metrics

```php
// Track FIFO deductions
Metrics::increment('inventory.fifo.deduction', [
    'context' => $context,
    'vertical' => $item->category,
]);

// Track expired items blocked
Metrics::increment('inventory.expiry.blocked', [
    'category' => $item->category,
]);
```

### Alerts

- **Expired items**: Alert to clinic owner
- **Critical expiry (< 14 days)**: Alert to warehouse manager
- **Low stock**: Alert to purchasing manager
- **Failed FIFO deduction**: Alert to system admin

## Troubleshooting

### Common Issues

**Issue**: "Controlled item requires expiry date (СОГ)"
- **Solution**: Ensure `expiry_date` is set for medication, feed, kitchen_product

**Issue**: "Insufficient stock with valid expiry date"
- **Solution**: Check if all batches are expired or empty. Use `getExpiringBatches()` to see available stock.

**Issue**: Race conditions in concurrent deductions
- **Solution**: Ensure `lockForUpdate()` is used (built into FIFOShelfLifeService)

**Issue**: Status not updating after batch changes
- **Solution**: Check model events are registered. Batch saved event should update parent item.

### Debug Queries

```php
// Check FIFO order
DB::enableQueryLog();
$batches = InventoryBatch::fifoOrder()->get();
dd(DB::getQueryLog());

// Check expiring batches
$expiring = $fifoService->getExpiringBatches($tenantId, 30);
dd($expiring->toArray());
```

## Future Enhancements

- [ ] Integration with supplier APIs for automatic batch updates
- [ ] ML-based expiry prediction for optimal ordering
- [ ] Mobile app alerts for field staff
- [ ] Blockchain-based batch traceability
- [ ] Integration with national veterinary registry
- [ ] Automated reordering based on expiry patterns

## Support

For issues or questions:
- Check test files for usage examples
- Review Filament resources for UI patterns
- Consult policy documentation for authorization rules
- Check logs for detailed error messages

## Changelog

### v1.0 (2026-04-23)
- Initial release
- FIFO by expiry date implementation
- Batch-level tracking
- Cross-vertical integration
- Filament admin interface
- Comprehensive test suite (76 tests, ≥95% coverage)
- Daily maintenance job
- Policy & middleware protection
- Full audit trail
