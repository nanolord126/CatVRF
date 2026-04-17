# Bonuses Domain

## Overview
The Bonuses domain handles bonus management including awarding, unlocking, spending, and bonus transactions with loyalty rules.

## Architecture Layers

### Layer 1: Models
- **BonusTransaction** - Bonus transaction model with tenant scoping

### Layer 2: DTOs
- **AwardBonusDto** - Data transfer object for awarding bonuses
- **ConsumeBonusDto** - Data transfer object for consuming bonuses

### Layer 3: Services
- **BonusService** - Bonus awarding, unlocking, and spending

### Layer 4: Requests
- **AwardBonusRequest** - Form request for awarding bonuses
- **ConsumeBonusRequest** - Form request for consuming bonuses

### Layer 5: Resources
- **BonusTransactionResource** - API resource for bonus transactions

### Layer 6: Events
- **BonusAwardedEvent** - Dispatched when bonus is awarded
- **BonusConsumedEvent** - Dispatched when bonus is consumed
- **BonusUnlockedEvent** - Dispatched when bonus is unlocked

### Layer 7: Listeners
- **BonusAwardedListener** - Handles bonus award
- **BonusConsumedListener** - Handles bonus consumption

### Layer 8: Jobs
- **BonusUnlockJob** - Scheduled job for unlocking bonuses after hold period
- **BonusExpirationJob** - Scheduled job for expiring old bonuses

### Layer 9: Filament Resources
- **BonusTransactionResource** - Admin UI for bonus transaction management

## Database Schema

### bonus_transactions Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `type` - Transaction type (referral, turnover, promo, loyalty)
- `amount` - Bonus amount in kopecks
- `status` - Status (pending, unlocked, expired)
- `hold_until` - Hold period end timestamp
- `source_type` - Source type
- `source_id` - Source ID
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `App\Services\WalletService` - Wallet integration
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Carbon\CarbonInterface` - Date/time operations

## Usage Examples

### Award Bonus
```php
$dto = new AwardBonusDto(
    userId: 5,
    tenantId: 1,
    amount: 50000, // 500 rubles
    type: 'loyalty',
    sourceType: 'Order',
    sourceId: 123,
    correlationId: $correlationId,
);
$transaction = $bonusService->award($dto, $correlationId);
```

### Unlock Bonus
```php
$bonusService->unlock($transactionId, $correlationId);
```

### Consume Bonus
```php
$dto = new ConsumeBonusDto(
    userId: 5,
    tenantId: 1,
    amount: 30000,
    correlationId: $correlationId,
);
$bonusService->consume($dto, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter BonusesDomain
```

## Queue Configuration
All jobs use the `bonuses` queue as defined in `config/domain_queues.php`.
