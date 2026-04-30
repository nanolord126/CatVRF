# Audit Domain

## Overview
The Audit domain handles audit logging for all mutations across the platform with tenant scoping, correlation IDs, and ClickHouse integration for long-term storage.

## Architecture Layers

### Layer 1: Models
- **AuditLog** - Audit log model with tenant scoping

### Layer 2: DTOs
- **CreateAuditLogDto** - Data transfer object for audit log creation

### Layer 3: Services
- **AuditService** - Audit recording and retrieval

### Layer 4: Requests
- **AuditLogRequest** - Form request for audit log operations

### Layer 5: Resources
- **AuditLogResource** - API resource for audit logs

### Layer 6: Events
- **AuditLogCreatedEvent** - Dispatched when audit log is created

### Layer 7: Listeners
- **AuditLogCreatedListener** - Handles audit log creation and ClickHouse sync

### Layer 8: Jobs
- **AuditSyncJob** - Scheduled job for syncing audit logs to ClickHouse
- **AuditCleanupJob** - Scheduled job for cleaning old audit logs

### Layer 9: Filament Resources
- **AuditLogResource** - Admin UI for audit log monitoring

## Database Schema

### audit_logs Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `business_group_id` - Business group (foreign key)
- `user_id` - User (foreign key)
- `action` - Action performed
- `subject_type` - Subject type
- `subject_id` - Subject ID
- `old_values` - JSON old values
- `new_values` - JSON new values
- `ip_address` - IP address
- `device_fingerprint` - Device fingerprint hash
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request

## Usage Examples

### Record Audit Log
```php
$auditService->record(
    action: 'order_created',
    subjectType: 'Order',
    subjectId: 123,
    oldValues: [],
    newValues: ['status' => 'pending', 'amount' => 99900],
    correlationId: $correlationId,
);
```

### Get Logs for Subject
```php
$logs = $auditService->getLogsForSubject('Order', 123, 100);
```

### Get Logs by Correlation ID
```php
$logs = $auditService->getLogsByCorrelationId($correlationId);
```

### Get User Logs
```php
$logs = $auditService->getLogsForUser($userId, 100);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter AuditDomain
```

## Queue Configuration
All jobs use the `audit` queue as defined in `config/domain_queues.php`.
