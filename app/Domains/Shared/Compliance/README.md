# Compliance Domain

## Overview
The Compliance domain handles regulatory compliance verification including MDLP (medicine marking) and Mercury (veterinary documents) verification for Russian market requirements.

## Architecture Layers

### Layer 1: Models
- **ComplianceRecord** - Compliance record model with tenant scoping

### Layer 2: DTOs
- (None - services use direct parameters)

### Layer 3: Services
- **MdlpService** - MDLP KIZ (Identification Mark) verification and withdrawal
- **MercuryService** - Mercury VSD (Veterinary Document) verification and extinguishing

### Layer 4: Requests
- **VerifyKizRequest** - Form request for KIZ verification
- **VerifyVsdRequest** - Form request for VSD verification

### Layer 5: Resources
- **ComplianceRecordResource** - API resource for compliance records

### Layer 6: Events
- **ComplianceVerifiedEvent** - Dispatched when compliance is verified
- **ComplianceFailedEvent** - Dispatched when compliance check fails

### Layer 7: Listeners
- **ComplianceVerifiedListener** - Handles successful compliance verification
- **ComplianceFailedListener** - Handles failed compliance checks

### Layer 8: Jobs
- **ComplianceSyncJob** - Scheduled job for syncing compliance data
- **ComplianceCleanupJob** - Scheduled job for cleaning old compliance records

### Layer 9: Filament Resources
- **ComplianceRecordResource** - Admin UI for compliance monitoring

## Database Schema

### compliance_records Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `type` - Type (mdlp, mercury)
- `document_id` - Document ID (KIZ code or VSD ID)
- `status` - Status (pending, verified, failed, completed)
- `verified_at` - Verification timestamp
- `response_data` - JSON response data from external API
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

## External APIs

### MDLP API
- Base URL: `https://mdlp.crpt.ru/api/v1`
- Verification endpoint: `/kiz/verify`
- Withdrawal endpoint: `/kiz/withdraw`
- Authentication: Bearer token

### Mercury API
- Base URL: `https://api.vetrf.ru/mercury/v1`
- Verification endpoint: `/vsd/{vsdId}`
- Extinguish endpoint: `/vsd/{vsdId}/extinguish`
- Authentication: X-Mercury-Token header

## Dependencies
- `Illuminate\Http\Client\Factory` - HTTP client for external APIs
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Carbon\CarbonInterface` - Date/time operations
- `Illuminate\Support\Str` - UUID generation

## Usage Examples

### Verify KIZ (MDLP)
```php
$isValid = $mdlpService->verifyKiz(
    kizCode: '01046000123456789021ABCD1234567890',
    token: 'mdlp_api_token',
    correlationId: $correlationId,
);
```

### Withdraw from Circulation (MDLP)
```php
$isSuccess = $mdlpService->withdrawFromCirculation(
    kizCode: '01046000123456789021ABCD1234567890',
    token: 'mdlp_api_token',
    correlationId: $correlationId,
);
```

### Verify VSD (Mercury)
```php
$isValid = $mercuryService->verifyVsd(
    vsdId: '1-ABCDEF1234567890',
    token: 'mercury_api_token',
    correlationId: $correlationId,
);
```

### Extinguish VSD (Mercury)
```php
$isSuccess = $mercuryService->extinguishVsd(
    vsdId: '1-ABCDEF1234567890',
    token: 'mercury_api_token',
    correlationId: $correlationId,
);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter ComplianceDomain
```

## Queue Configuration
All jobs use the `compliance` queue as defined in `config/domain_queues.php`.
