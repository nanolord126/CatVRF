# Payout Domain

## Overview
The Payout domain handles payout request creation, processing, batch processing, cancellation, status retrieval, and payout history with fraud checks and audit logging.

## Architecture Layers

### Layer 1: Models
- **PayoutRequest** - Payout request model with tenant scoping

### Layer 2: DTOs
- **CreatePayoutRequestDto** - Data transfer object for payout request creation

### Layer 3: Services
- **PayoutService** - Payout request and payment processing

### Layer 4: Requests
- **CreatePayoutRequest** - Form request for payout creation

### Layer 5: Resources
- **PayoutRequestResource** - API resource for payout requests

### Layer 6: Events
- **PayoutRequestCreatedEvent** - Dispatched when payout request is created
- **PayoutProcessedEvent** - Dispatched when payout is processed
- **PayoutCancelledEvent** - Dispatched when payout is cancelled

### Layer 7: Listeners
- **PayoutRequestCreatedListener** - Handles payout request creation
- **PayoutProcessedListener** - Handles payout processing

### Layer 8: Jobs
- **ProcessPayoutJob** - Queued job for processing individual payouts
- **BatchPayoutJob** - Scheduled job for batch payout processing
- **PayoutCleanupJob** - Scheduled job for cleaning old payout requests

### Layer 9: Filament Resources
- **PayoutRequestResource** - Admin UI for payout management

## Database Schema

### payout_requests Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `business_group_id` - Business group (foreign key)
- `amount_cents` - Amount in cents
- `status` - Status (pending, processing, completed, failed, cancelled)
- `bank_details` - JSON bank details
- `cancellation_reason` - Cancellation reason
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request
- `Illuminate\Support\Str` - UUID generation

## Usage Examples

### Create Payout Request
```php
$dto = new CreatePayoutRequestDto(
    tenantId: 1,
    businessGroupId: 5,
    amountCents: 1000000, // 10000 rubles
    bankDetails: [
        'account_number' => '1234567890',
        'bic' => '044525225',
        'bank_name' => 'Sberbank',
    ],
);
$request = $payoutService->createRequest($dto, $correlationId);
```

### Process Payout
```php
$payoutService->process($requestId, $correlationId);
```

### Cancel Payout
```php
$payoutService->cancel($requestId, 'User request', $correlationId);
```

### Batch Process Payouts
```php
$requestIds = [1, 2, 3, 4, 5];
$batchId = $payoutService->processBatch($requestIds, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter PayoutDomain
```

## Queue Configuration
All jobs use the `payout` queue as defined in `config/domain_queues.php`.
