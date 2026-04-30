# CRM Integration Guide — B2C & B2B for All Verticals

**Created:** 2026-04-27  
**Status:** Complete  
**Version:** 1.0

## Overview

Complete CRM system for both B2C and B2B operations across all verticals with full integrations:
- **Staff/Personnel** — Auto-assignment, task creation, manager selection
- **Warehouse** — Inventory reservation, stock allocation, warehouse linking
- **Inventory** — Quote requests, availability checks, forecasting

## Architecture

```
modules/CatCRM/
├── Domain/
│   ├── Entities/
│   │   ├── Customer.php (B2C)
│   │   ├── Deal.php (B2C)
│   │   ├── B2BLead.php (NEW)
│   │   ├── B2BContact.php (NEW)
│   │   └── B2BDeal.php (NEW)
│   ├── Enums/
│   │   ├── LeadStatus.php (NEW)
│   │   ├── ContactType.php (NEW)
│   │   └── DealStatus.php
│   └── Events/
│       ├── B2BLeadCreated.php (NEW)
│       ├── B2BLeadConverted.php (NEW)
│       └── B2BDealWon.php (NEW)
├── Application/
│   └── Services/
│       ├── CustomerService.php
│       ├── DealService.php
│       ├── B2BLeadService.php (NEW)
│       ├── CRMStaffIntegrationService.php (NEW)
│       ├── CRMWarehouseIntegrationService.php (NEW)
│       ├── CRMInventoryIntegrationService.php (NEW)
│       └── UnifiedCRMService.php (NEW)
└── Infrastructure/
    ├── Listeners/
    │   ├── AutoAssignLeadToStaff.php (NEW)
    │   ├── CreateInventoryQuoteOnLead.php (NEW)
    │   └── UpdateCustomerLTVOnDealWon.php (NEW)
    └── Jobs/
        ├── ProcessInventoryQuoteJob.php (NEW)
        └── SendLeadFollowUpJob.php (NEW)
```

## Database Schema

### New Tables

- `crm_b2b_leads` — B2B leads with company info, budget, warehouse link
- `crm_b2b_contacts` — Contact persons for B2B leads/deals
- `crm_b2b_deals` — B2B deals converted from leads
- `crm_b2b_lead_tags` — Tag junction table

### Modified Tables

- `crm_tasks` — Added `entity_type` and `entity_id` for polymorphic relations
- `crm_interactions` — Added `entity_type` and `entity_id` for polymorphic relations
- `inventory_requests` — Added `b2b_lead_id` foreign key

## Usage Examples

### Create B2C Customer

```php
use Modules\CatCRM\Application\Services\UnifiedCRMService;

$crm = new UnifiedCRMService($correlationId, $auditService, ...);

$customer = $crm->createB2CCustomer([
    'tenant_id' => $tenantId,
    'first_name' => 'Иван',
    'last_name' => 'Иванов',
    'email' => 'ivan@example.com',
    'phone' => '+79001234567',
]);
```

### Create B2B Lead with Auto-Assignments

```php
$lead = $crm->createB2BLead([
    'tenant_id' => $tenantId,
    'vertical_id' => 'restaurant',
    'company_name' => 'ООО Ресторан',
    'contact_person' => 'Петр Петров',
    'contact_email' => 'petr@restaurant.ru',
    'contact_phone' => '+79001234567',
    'requirement' => 'Оптовые поставки продуктов',
    'budget_range' => '500 000 - 2 000 000 ₽',
    'category' => 'wholesale',
    'source' => 'Сайт',
    'assign_to_staff' => $managerId,
    'warehouse_id' => $warehouseId,
    'inventory_items' => [
        ['sku' => 'PROD001', 'quantity' => 100],
        ['sku' => 'PROD002', 'quantity' => 50],
    ],
]);
```

### Convert Lead to Deal

```php
$deal = $crm->convertLeadToDeal($lead);
```

### Process Deal Lifecycle

```php
// Reserve inventory
$deal = $crm->processDealLifecycle($deal, 'reserve_inventory', [
    'items' => [
        ['sku' => 'PROD001', 'quantity' => 100],
    ],
]);

// Confirm contract
$deal = $crm->processDealLifecycle($deal, 'confirm_contract', [
    'start_date' => now(),
    'end_date' => now()->addYear(),
]);

// Win deal
$deal = $crm->processDealLifecycle($deal, 'win_deal');
```

### Get Customer 360 View

```php
$view = $crm->getCustomer360($customerId);
/*
[
    'customer' => Customer model,
    'ltv' => [...],
    'deals' => [...],
    'interactions' => [...],
    'pending_tasks' => [...],
    'activity_timeline' => [...],
]
*/
```

### Get Lead 360 View

```php
$view = $crm->getLead360($leadId);
/*
[
    'lead' => B2BLead model,
    'contacts' => [...],
    'estimated_value' => 500000,
    'inventory_quote' => InventoryRequest,
    'pending_tasks' => [...],
    'activity_timeline' => [...],
]
*/
```

## Integration Points

### Staff Integration

```php
use Modules\CatCRM\Application\Services\CRMStaffIntegrationService;

$staffService = new CRMStaffIntegrationService();

// Assign lead to staff
$staffService->assignLeadToStaff($lead, $manager, $team);

// Get available staff for vertical
$staff = $staffService->getAvailableStaffForVertical($tenantId, 'restaurant');

// Auto-assign lead
$manager = $staffService->autoAssignLead($lead);
```

### Warehouse Integration

```php
use Modules\CatCRM\Application\Services\CRMWarehouseIntegrationService;

$warehouseService = new CRMWarehouseIntegrationService();

// Link lead to warehouse
$warehouseService->linkLeadToWarehouse($lead, $warehouseId);

// Get available warehouses
$warehouses = $warehouseService->getAvailableWarehouses($tenantId, 'restaurant');

// Reserve inventory for deal
$warehouseService->reserveInventoryForDeal($deal, $items);
```

### Inventory Integration

```php
use Modules\CatCRM\Application\Services\CRMInventoryIntegrationService;

$inventoryService = new CRMInventoryIntegrationService();

// Create quote request
$quote = $inventoryService->createQuoteRequest($lead, $items);

// Process quote
$result = $inventoryService->processQuoteRequest($quote);

// Create reservation
$inventoryService->createInventoryReservation($lead, $items, $warehouseId);

// Get forecast
$forecast = $inventoryService->getInventoryForecast($tenantId, 'restaurant', 30);
```

## Event Listeners

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \Modules\CatCRM\Domain\Events\B2BLeadCreated::class => [
        \Modules\CatCRM\Infrastructure\Listeners\AutoAssignLeadToStaff::class,
        \Modules\CatCRM\Infrastructure\Listeners\CreateInventoryQuoteOnLead::class,
    ],
    \Modules\CatCRM\Domain\Events\B2BLeadConverted::class => [
        // Add listeners for lead conversion
    ],
    \Modules\CatCRM\Domain\Events\B2BDealWon::class => [
        \Modules\CatCRM\Infrastructure\Listeners\UpdateCustomerLTVOnDealWon::class,
    ],
];
```

## Vertical-Specific Logic

Each vertical can have custom logic in the `Domain/Verticals/` directory:

```
Domain/Verticals/
├── Restaurant/
│   ├── RestaurantLead.php
│   └── RestaurantDeal.php
├── Beauty/
│   ├── BeautyLead.php
│   └── BeautyDeal.php
└── ...
```

## Migration

Run migration to create new tables:

```bash
php artisan migrate
```

## API Endpoints

Add to `routes/api.php`:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // B2B Leads
    Route::apiResource('b2b-leads', B2BLeadController::class);
    Route::post('b2b-leads/{lead}/convert', [B2BLeadController::class, 'convert']);
    Route::post('b2b-leads/{lead}/assign', [B2BLeadController::class, 'assign']);
    
    // B2B Deals
    Route::apiResource('b2b-deals', B2BDealController::class);
    Route::post('b2b-deals/{deal}/process', [B2BDealController::class, 'process']);
    
    // 360 Views
    Route::get('customers/{id}/360', [CustomerController::class, 'get360']);
    Route::get('b2b-leads/{id}/360', [B2BLeadController::class, 'get360']);
});
```

## Testing

```bash
php artisan test --filter=CRM
```

## Performance Considerations

- All inventory operations use database row locking
- Auto-assignment queries are optimized with indexes
- 360 views use eager loading
- Heavy operations are queued (inventory processing, follow-ups)

## Security

- All operations are tenant-scoped
- Audit logging via WithAuditLogging trait
- PII data encryption for contacts
- Correlation IDs for traceability

## Next Steps

1. Create API controllers for B2B endpoints
2. Create Filament resources for admin panel
3. Add vertical-specific business logic
4. Implement AI-based lead scoring
5. Add webhooks for external integrations
