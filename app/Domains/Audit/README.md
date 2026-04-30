# Audit Vertical - Complete Implementation Guide

## Overview

The Audit Vertical provides centralized, production-ready audit logging for the CatVRF marketplace. It follows Clean Architecture/DDD principles with full support for multi-tenancy, GDPR compliance, and distributed tracing.

## Architecture

### Domain Layer (`app/Domains/Audit/`)

```
Audit/
├── DTOs/
│   └── CreateAuditLogDto.php          # Immutable DTO for audit log creation
├── Events/
│   ├── ModelAuditedEvent.php          # Base audit event
│   ├── ModelCreatedEvent.php          # Fired on model creation
│   ├── ModelUpdatedEvent.php          # Fired on model update
│   └── ModelDeletedEvent.php          # Fired on model deletion
├── Jobs/
│   ├── AsyncAuditLogger.php           # Async queue job for logging
│   └── AuditPruneJob.php              # Scheduled job for retention cleanup
├── Models/
│   └── AuditLog.php                   # Audit log model with scopes
├── Controllers/
│   └── AuditApiController.php         # REST API controller
├── Facades/
│   └── Audit.php                      # Laravel facade
├── Listeners/
│   └── AuditModelListener.php         # Event subscriber for auto-logging
├── Providers/
│   └── AuditServiceProvider.php       # Service provider
├── Resources/
│   └── AuditLogResource.php           # API resource
├── Filament/
│   └── Resources/
│       └── AuditLogResource/          # Filament admin UI
│           ├── Pages/
│           │   ├── ListAuditLogs.php
│           │   └── ViewAuditLog.php
│           └── AuditLogResource.php
├── Traits/
│   └── Auditable.php                  # Trait for automatic model auditing
└── routes/
    └── audit.php                      # API routes
```

## Features

### Core Features
- **Async Queue Logging**: All audit logs written via Redis/Database queue
- **Multi-Tenant Support**: Automatic tenant ID extraction
- **Field Masking**: Sensitive fields (password, card_number, etc.) automatically masked
- **Correlation IDs**: Distributed tracing across microservices
- **Device Fingerprinting**: SHA256 hash of IP + User-Agent for fraud detection
- **Retention Policy**: Automatic pruning of old logs
- **GDPR Compliance**: Right to be forgotten with user/subject deletion

### Security
- PII anonymization before storage
- Configurable field masking
- Encrypted sensitive data support
- Audit trail for all deletions

### Performance
- Async queue to prevent blocking
- Indexed database queries
- Batch pruning operations
- ClickHouse integration for analytics (optional)

## Usage

### 1. Manual Audit Logging

Using the AuditService:

```php
use App\Domains\Audit\Services\AuditService;

class OrderService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function createOrder(array $data): Order
    {
        $order = Order::create($data);

        $this->auditService->record(
            action: 'order_created',
            subjectType: Order::class,
            subjectId: $order->id,
            oldValues: [],
            newValues: $data,
        );

        return $order;
    }
}
```

Using the Facade:

```php
use App\Domains\Audit\Facades\Audit;

Audit::record('order_created', Order::class, $order->id, [], $data);
```

### 2. Automatic Model Auditing

Add the Auditable trait to any model:

```php
use App\Domains\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use Auditable;

    protected $fillable = ['status', 'amount', 'user_id'];

    // Exclude specific fields from auditing
    protected static $auditExcludedFields = ['updated_at'];
}
```

All create/update/delete operations will automatically trigger audit logging.

### 3. Payment Logging

```php
Audit::logPayment('init', [
    'payment_id' => $payment->id,
    'amount' => 15000,
    'currency' => 'RUB',
    'status' => 'pending',
]);
```

### 4. Wallet Operations

```php
Audit::logWallet('hold', [
    'wallet_id' => $wallet->id,
    'amount' => 5000,
    'reason' => 'order_hold',
]);
```

### 5. Fraud Check Logging

```php
Audit::logFraudCheck([
    'operation_type' => 'payment',
    'score' => 0.85,
    'decision' => 'review',
    'user_id' => $userId,
]);
```

### 6. Error Logging

```php
try {
    // Some operation
} catch (\Exception $e) {
    Audit::logError('payment_processing', $e);
}
```

### 7. Querying Audit Logs

```php
// Get logs for a subject
$logs = Audit::getLogsForSubject(Order::class, $orderId);

// Get logs by correlation ID
$logs = Audit::getLogsByCorrelationId($correlationId);

// Get logs for a user
$logs = Audit::getLogsForUser($userId);

// Search by payload
$logs = Audit::searchByPayload('user@example.com');

// Get logs in date range
$logs = Audit::getLogsInDateRange(
    Carbon::now()->subDays(7),
    Carbon::now()
);
```

### 8. GDPR Compliance

```php
// Delete all logs for a user (Right to be forgotten)
$deletedCount = Audit::deleteLogsForUser($userId);

// Delete logs for a specific subject
$deletedCount = Audit::deleteLogsForSubject(Order::class, $orderId);
```

## Configuration

Edit `config/audit.php`:

```php
return [
    'enabled' => env('AUDIT_ENABLED', true),
    'async' => env('AUDIT_ASYNC', true),
    'retention_months' => env('AUDIT_RETENTION_MONTHS', 12),
    'masked_fields' => [
        'password', 'card_number', 'cvv', 'token', 'api_key',
    ],
    'excluded_fields' => [
        'id', 'created_at', 'updated_at', 'remember_token',
    ],
];
```

## Environment Variables

Add to `.env`:

```env
# Audit Configuration
AUDIT_ENABLED=true
AUDIT_ASYNC=true
AUDIT_QUEUE=audit-logs
AUDIT_RETENTION_MONTHS=12
AUDIT_PRUNE_SCHEDULE="0 0 * * *"
AUDIT_DEVICE_FINGERPRINTING=true
```

## Service Provider Registration

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Domains\Audit\Providers\AuditServiceProvider::class,
],
```

## Scheduled Tasks

The retention policy job is automatically scheduled. To schedule manually:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('audit:prune')->daily();
}
```

## API Endpoints

All endpoints require authentication:

```
GET    /api/v1/audit/logs                    # List audit logs
GET    /api/v1/audit/logs/{id}               # Get single log
GET    /api/v1/audit/logs/subject/{type}/{id?} # Get by subject
GET    /api/v1/audit/logs/correlation/{id}   # Get by correlation ID
GET    /api/v1/audit/logs/user/{userId}      # Get by user
DELETE /api/v1/audit/logs/user/{userId}      # Delete by user (GDPR)
DELETE /api/v1/audit/logs/subject/{type}/{id?} # Delete by subject
POST   /api/v1/audit/logs/prune              # Manual prune
```

## Filament Admin UI

Access at `/admin/audit-logs`:

- Filter by action, subject type, IP address, date range
- View detailed audit log with masked values
- Navigate by correlation ID
- Bulk delete operations

## Testing

Run tests:

```bash
# Feature tests
php artisan test --filter AuditServiceTest

# Unit tests
php artisan test --filter AuditableTraitTest

# All audit tests
php artisan test --filter Audit
```

## Performance Considerations

1. **Async Queue**: Always use async in production to prevent blocking
2. **Retention Policy**: Set appropriate retention to prevent table bloat
3. **Indexing**: Database has composite indexes for common queries
4. **ClickHouse**: Use for long-term analytics on large datasets

## Security Best Practices

1. **Field Masking**: Always mask sensitive fields in config
2. **GDPR**: Implement right to be forgotten endpoints
3. **Access Control**: Restrict audit log viewing to authorized users
4. **Encryption**: Consider encryption for highly sensitive audit data

## Troubleshooting

### Logs not appearing
- Check `AUDIT_ENABLED=true` in .env
- Verify queue worker is running: `php artisan queue:work`
- Check queue configuration in `config/queue.php`

### Queue jobs failing
- Check logs: `storage/logs/laravel.log`
- Verify queue connection: `config/audit.php` → `queue_connection`
- Check job retries configuration

### Performance issues
- Switch to async mode: `AUDIT_ASYNC=true`
- Reduce retention period
- Implement ClickHouse for historical data
- Add database indexes if needed

## Migration

To migrate from existing audit system:

1. Update model references from `App\Models\AuditLog` to `App\Domains\Audit\Models\AuditLog`
2. Update service references to use new AuditService
3. Update facade references to `App\Domains\Audit\Facades\Audit`
4. Run migration to ensure schema is up to date

## Support

For issues or questions:
- Check documentation in `docs/audit/`
- Review configuration in `config/audit.php`
- Check logs in `storage/logs/audit.log`
