# B2B Domain

## Overview
The B2B domain handles business-to-business operations including API keys, business groups, credit limits, wholesale pricing, and B2B order management.

## Architecture Layers

### Layer 1: Models
- **BusinessGroup** - Business group model with tenant scoping
- **B2BApiKey** - B2B API key model with permission management

### Layer 2: DTOs
- **CreateApiKeyDto** - Data transfer object for API key creation
- **CreateOrderDto** - Data transfer object for B2B order creation

### Layer 3: Services
- **B2BService** - B2B operations including API key management and order processing

### Layer 4: Requests
- **CreateApiKeyRequest** - Form request for API key creation
- **CreateOrderRequest** - Form request for order creation

### Layer 5: Resources
- **BusinessGroupResource** - API resource for business groups
- **B2BApiKeyResource** - API resource for API keys

### Layer 6: Events
- **B2BOrderCreatedEvent** - Dispatched when B2B order is created
- **B2BApiKeyCreatedEvent** - Dispatched when API key is created

### Layer 7: Listeners
- **B2BOrderCreatedListener** - Handles B2B order creation
- **B2BApiKeyCreatedListener** - Handles API key creation

### Layer 8: Jobs
- **B2BOrderProcessingJob** - Queued job for processing B2B orders
- **CreditLimitCheckJob** - Scheduled job for credit limit checks

### Layer 9: Filament Resources
- **BusinessGroupResource** - Admin UI for business group management
- **B2BApiKeyResource** - Admin UI for API key management

## Database Schema

### business_groups Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `name` - Business group name
- `inn` - INN (Tax ID)
- `kpp` - KPP
- `legal_address` - Legal address
- `actual_address` - Actual address
- `contact_person` - Contact person
- `phone` - Phone number
- `email` - Email
- `credit_limit_kopecks` - Credit limit in kopecks
- `used_credit_kopecks` - Used credit in kopecks
- `b2b_tier` - B2B tier (bronze, silver, gold, platinum)
- `is_verified` - Verification status
- `is_active` - Active status
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

### b2b_api_keys Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `business_group_id` - Business group (foreign key)
- `name` - API key name
- `hashed_key` - Hashed API key
- `permissions` - JSON array of permissions
- `expires_at` - Expiration timestamp
- `last_used_at` - Last usage timestamp
- `last_ip` - Last IP address
- `is_active` - Active status
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

## Business Rules

### B2B Determination
B2B mode is determined by presence of INN and business_card_id in request:
```php
$isB2B = $request->has('inn') && $request->has('business_card_id');
```

### Pricing Differences
- B2C: Retail prices, 14% commission, full prepayment, 4-7 day payout
- B2B: Wholesale prices (lower), 8-12% commission (tier-based), advance + 7-30 day credit, 7-14 day payout

### Credit Limits
- Bronze: No credit
- Silver: 100,000 rub credit
- Gold: 500,000 rub credit
- Platinum: 2,000,000 rub credit

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request
- `Illuminate\Contracts\Auth\Guard` - Authentication
- `Carbon\CarbonInterface` - Date/time operations
- `Illuminate\Support\Str` - UUID generation

## Usage Examples

### Create API Key
```php
$dto = new CreateApiKeyDto(
    businessGroupId: 5,
    name: 'Production API Key',
    permissions: ['read', 'write', 'orders'],
    expiresAt: now()->addMonths(12),
);
$result = $b2bService->createApiKey($dto, $correlationId);
```

### Validate API Key
```php
$businessGroup = $b2bService->validateApiKey($rawKey, 'orders');
```

### Revoke API Key
```php
$b2bService->revokeApiKey($apiKeyId, $correlationId);
```

### Create B2B Order
```php
$dto = new CreateOrderDto(
    businessGroupId: 5,
    items: [
        ['product_id' => 123, 'quantity' => 10],
        ['product_id' => 124, 'quantity' => 5],
    ],
    useCredit: true,
    deliveryAddress: 'Business Address',
);
$result = $b2bService->createOrder($dto, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter B2BDomain
```

## Queue Configuration
All jobs use the `b2b` queue as defined in `config/domain_queues.php`.
