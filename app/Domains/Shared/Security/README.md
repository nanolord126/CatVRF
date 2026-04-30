# Security Domain

## Overview
The Security domain handles all security-related functionality including API key management, rate limiting, security event logging, idempotency, and security monitoring.

## Architecture Layers

### Layer 1: Models
- **ApiKey** - API key model with tenant scoping, expiration, and permission checks
- **SecurityEvent** - Security event logging for audit trail

### Layer 2: DTOs
- **ValidateApiKeyDto** - Data transfer object for API key validation
- **CreateSecurityEventDto** - Data transfer object for security event creation

### Layer 3: Services
- **ApiKeysService** - API key creation, validation, rotation, and revocation
- **SecurityMonitoringService** - Real-time security monitoring and threat detection

### Layer 4: Requests
- **ApiKeyRequest** - Form request for API key operations

### Layer 5: Resources
- **ApiKeyResource** - API resource for JSON serialization
- **SecurityEventResource** - API resource for security events

### Layer 6: Events
- **SecurityAlertEvent** - Dispatched when security threat detected
- **ApiKeyRevokedEvent** - Dispatched when API key is revoked

### Layer 7: Listeners
- **SecurityAlertListener** - Handles security alerts and notifications
- **ApiKeyRevokedListener** - Handles API key revocation cleanup

### Layer 8: Jobs
- **SecurityCleanupJob** - Scheduled job for cleaning up expired security data
- **SecurityAlertJob** - Queued job for processing security alerts

### Layer 9: Filament Resources
- **ApiKeyResource** - Admin UI for API key management
- **SecurityEventResource** - Admin UI for security event monitoring

## Database Schema

### api_keys Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `business_group_id` - Business group (foreign key, nullable)
- `key_hash` - Hashed API key
- `permissions` - JSON array of permissions
- `expires_at` - Expiration timestamp
- `is_active` - Active status
- `last_used_at` - Last usage timestamp
- `created_at`, `updated_at` - Timestamps

### security_events Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `event_type` - Type of security event
- `severity` - Event severity (low, medium, high, critical)
- `source_ip` - Source IP address
- `user_agent` - User agent string
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request
- `Carbon\CarbonInterface` - Date/time operations

## Usage Examples

### Create API Key
```php
$dto = new CreateApiKeyDto(
    tenantId: 1,
    businessGroupId: 5,
    name: 'Production Key',
    permissions: ['read', 'write'],
    expiresAt: now()->addMonths(6),
);
$apiKey = $apiKeysService->create($dto, $correlationId);
```

### Validate API Key
```php
$isValid = $apiKeysService->validate($rawKey, $requiredPermission);
```

### Log Security Event
```php
$securityMonitoringService->logEvent(
    eventType: 'brute_force_attempt',
    severity: 'high',
    sourceIp: $request->ip(),
    correlationId: $correlationId,
);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter SecurityDomain
```

## Queue Configuration
All jobs use the `security` queue as defined in `config/domain_queues.php`.
