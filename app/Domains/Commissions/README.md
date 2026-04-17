# Commissions Domain

## Overview
The Commissions domain handles commission calculation, recording, statistics, payout marking, and commission transaction management with tier-based rates.

## Architecture Layers

### Layer 1: Models
- **CommissionRecord** - Commission record model with tenant scoping

### Layer 2: DTOs
- **CalculateCommissionDto** - Data transfer object for commission calculation
- **RecordCommissionDto** - Data transfer object for recording commissions

### Layer 3: Services
- **CommissionService** - Commission calculation and management

### Layer 4: Requests
- **CalculateCommissionRequest** - Form request for commission calculation
- **RecordCommissionRequest** - Form request for recording commissions

### Layer 5: Resources
- **CommissionRecordResource** - API resource for commission records

### Layer 6: Events
- **CommissionRecordedEvent** - Dispatched when commission is recorded
- **CommissionPaidOutEvent** - Dispatched when commission is marked as paid

### Layer 7: Listeners
- **CommissionRecordedListener** - Handles commission recording
- **CommissionPaidOutListener** - Handles commission payout

### Layer 8: Jobs
- **CommissionPayoutJob** - Scheduled job for commission payouts
- **CommissionCalculationJob** - Scheduled job for batch commission calculation

### Layer 9: Filament Resources
- **CommissionRecordResource** - Admin UI for commission management

## Database Schema

### commission_records Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `business_group_id` - Business group (foreign key)
- `seller_id` - Seller (foreign key)
- `order_id` - Order (foreign key)
- `amount_kopecks` - Commission amount in kopecks
- `rate_percent` - Commission rate percentage
- `status` - Status (pending, paid)
- `paid_at` - Paid timestamp
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Carbon\CarbonInterface` - Date/time operations

## Usage Examples

### Calculate Commission
```php
$dto = new CalculateCommissionDto(
    tenantId: 1,
    vertical: 'beauty',
    amount: 99900, // 999 rubles
    context: ['is_b2b' => true, 'b2b_tier' => 'gold'],
);
$commission = $commissionService->calculate($dto, $correlationId);
```

### Record Commission
```php
$dto = new RecordCommissionDto(
    tenantId: 1,
    businessGroupId: 5,
    sellerId: 10,
    orderId: 123,
    amountKopecks: $commission,
    ratePercent: 14,
    correlationId: $correlationId,
);
$record = $commissionService->record($dto, $correlationId);
```

### Mark as Paid
```php
$commissionService->markAsPaid($recordId, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter CommissionsDomain
```

## Queue Configuration
All jobs use the `commissions` queue as defined in `config/domain_queues.php`.
