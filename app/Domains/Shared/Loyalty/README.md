# CatVRF Loyalty System

A comprehensive, cross-vertical loyalty system for the CatVRF marketplace that works seamlessly across restaurants, hotels, beauty salons, and other verticals.

## Features

- **Cross-Vertical Support**: Works for restaurants, hotels, beauty, auto, fashion, jewelry, and more
- **Flexible Point Calculation**: Configurable rules for earning points based on orders, visits, items, time, and custom conditions
- **Tier System**: Automatic tier upgrades (Bronze, Silver, Gold, Platinum) with different multipliers and privileges
- **Reward System**: Flexible rewards (discounts, free items, upgrades, services, vouchers)
- **Real-time Display**: Livewire components for KDS and order displays
- **Filament Admin**: Full admin interface for managing programs, tiers, rules, and rewards
- **Clean Architecture**: DDD with Domain, Infrastructure, and Application layers
- **Event-Driven**: Events for points earned, tier upgrades, and reward redemptions
- **Cache-Optimized**: Redis caching for high performance

## Architecture

```
modules/Loyalty/
├── Domain/
│   ├── Entities/           # Domain entities (LoyaltyProgram, LoyaltyTier, etc.)
│   ├── Enums/              # Enums (LoyaltyTransactionType, LoyaltyRuleType, etc.)
│   ├── Events/             # Domain events (PointsEarned, TierUpgraded, etc.)
│   ├── Repositories/       # Repository interfaces
│   ├── Services/           # Domain services
│   └── ValueObjects/       # Value objects (Points, CurrencyAmount)
├── Infrastructure/
│   ├── Database/
│   │   └── Migrations/     # Database migrations
│   ├── Models/             # Eloquent models
│   └── Repositories/       # Repository implementations
├── Application/
│   ├── DTOs/               # Data transfer objects
│   └── Services/           # Application services (LoyaltyService, etc.)
├── Filament/
│   └── Resources/          # Filament admin resources
├── Livewire/               # Livewire components
└── resources/views/        # Blade templates
```

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

The following tables will be created:
- `loyalty_programs` - Loyalty programs per tenant
- `loyalty_tiers` - Tier levels (Bronze, Silver, Gold, Platinum)
- `guest_loyalty_profiles` - Guest loyalty profiles
- `loyalty_transactions` - Point transactions
- `loyalty_rules` - Point earning rules
- `loyalty_rewards` - Available rewards

### 2. Configure Environment

Add these variables to your `.env` file:

```env
# Loyalty System Configuration
LOYALTY_DEFAULT_VERTICAL=restaurant
LOYALTY_BASE_POINTS_PER_CURRENCY=1.0
LOYALTY_POINTS_TO_CURRENCY_RATE=0.01
LOYALTY_CACHE_ENABLED=true
LOYALTY_AUTO_ENROLLMENT_ENABLED=true
LOYALTY_POINTS_EXPIRE=false
LOYALTY_POINTS_EXPIRATION_DAYS=365
LOYALTY_QUEUE_ENABLED=true
LOYALTY_QUEUE_NAME=loyalty
LOYALTY_TESTING_MODE=false
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=loyalty-config
```

## Quick Start

### 1. Create a Loyalty Program

Navigate to Filament Admin → Loyalty → Loyalty Programs and create a new program:

- **Name**: e.g., "Restaurant Rewards"
- **Vertical Type**: restaurant, hotel, beauty, etc.
- **Base Points Per Currency**: Points earned per currency unit (default: 1.0)
- **Signup Bonus Points**: Bonus points for registration
- **Birthday Bonus Points**: Bonus points on birthday

### 2. Configure Tiers

Navigate to Loyalty → Loyalty Tiers and create tiers:

- **Bronze**: 0 points, 1.0x multiplier, 0% discount
- **Silver**: 1,000 points, 1.2x multiplier, 5% discount
- **Gold**: 5,000 points, 1.5x multiplier, 10% discount
- **Platinum**: 15,000 points, 2.0x multiplier, 15% discount

### 3. Create Earning Rules

Navigate to Loyalty → Loyalty Rules and create rules:

- **Order Based**: Points based on order amount
- **First Visit**: Bonus for first-time guests
- **Birthday**: Birthday bonus
- **Time Based**: Happy hours, weekend bonuses
- **Item Based**: Points for specific items

Example rule conditions (JSON):
```json
{
  "min_amount": 1000,
  "day_of_week": "friday"
}
```

### 4. Create Rewards

Navigate to Loyalty → Loyalty Rewards and create rewards:

- **Discount**: Percentage or fixed amount discount
- **Free Item**: Free menu item or service
- **Upgrade**: Room or table upgrade
- **Service**: Free service (spa, breakfast, etc.)

## Usage

### Enroll a Guest

```php
use Modules\Loyalty\Application\DTOs\EnrollGuestDTO;
use Modules\Loyalty\Application\Services\LoyaltyService;

$loyaltyService = app(LoyaltyService::class);

$dto = EnrollGuestDTO::fromArray([
    'program_id' => $programId,
    'guest_id' => $guestId,
    'user_id' => $userId,
    'birthday' => '1990-01-01',
    'referred_by' => $referrerId,
]);

$profile = $loyaltyService->enrollGuest($dto);
```

### Process Order Loyalty

```php
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;

$profile = $loyaltyService->processOrderLoyalty(
    guestId: $guestId,
    programId: $programId,
    orderAmount: CurrencyAmount::fromFloat(1000.0),
    tierSlug: 'silver',
    sourceType: 'order',
    sourceId: $orderId
);
```

### Calculate Points

```php
use Modules\Loyalty\Application\DTOs\CalculatePointsDTO;

$dto = CalculatePointsDTO::fromArray([
    'program_id' => $programId,
    'guest_id' => $guestId,
    'order_amount' => 1000.0,
    'tier_slug' => 'silver',
    'is_first_visit' => false,
    'is_birthday' => false,
]);

$result = $loyaltyService->calculatePoints($dto);
// $result->totalPoints - Total points earned
// $result->description - Human-readable description
// $result->appliedRules - Rules that were applied
```

### Redeem Reward

```php
use Modules\Loyalty\Application\DTOs\RedeemRewardDTO;

$dto = RedeemRewardDTO::fromArray([
    'profile_id' => $profileId,
    'reward_id' => $rewardId,
    'points_cost' => $pointsCost,
]);

$profile = $loyaltyService->applyReward($dto);
```

### Check Tier Upgrade

```php
$newTier = $loyaltyService->checkTierUpgrade($profile);
if ($newTier !== null) {
    // Guest was upgraded to a new tier
}
```

### Get Profile Balance

```php
$points = $loyaltyService->getProfileBalance($guestId, $programId);
```

## Livewire Components

### Order Loyalty Display

Display loyalty points in order forms:

```blade
<livewire:loyalty::order-loyalty-display 
    :guest-id="$guestId"
    :program-id="$programId"
    :order-amount="$orderAmount"
    :tier-slug="$tierSlug"
/>
```

### KDS Loyalty Display

Display loyalty points in Kitchen Display System:

```blade
<livewire:loyalty::kds-loyalty-display
    :guest-id="$guestId"
    :program-id="$programId"
    :polling-interval="30"
/>
```

### Loyalty Reward Selector

Allow guests to select and redeem rewards:

```blade
<livewire:loyalty::loyalty-reward-selector
    :profile-id="$profileId"
/>
```

## Integration with Verticals

### Restaurant Integration

```php
use Modules\Loyalty\Application\Services\OrderLoyaltyIntegration;

$orderLoyalty = app(OrderLoyaltyIntegration::class);

// Auto-enroll guest
$orderLoyalty->autoEnrollGuest($guestId, $programId, $userId, $birthday);

// Process points after order completion
$orderLoyalty->processOrderCompletion(
    $guestId,
    $programId,
    $orderAmount,
    $tierSlug,
    $orderUuid,
    $orderId
);
```

### Hotel Integration

```php
use Modules\Loyalty\Application\Services\BookingLoyaltyIntegration;

$bookingLoyalty = app(BookingLoyaltyIntegration::class);

// Auto-enroll guest
$bookingLoyalty->autoEnrollGuest($guestId, $programId, $userId, $birthday);

// Process points after check-out
$bookingLoyalty->processBookingCompletion(
    $guestId,
    $programId,
    $bookingAmount,
    $tierSlug,
    $bookingUuid,
    $bookingId
);
```

## Events

The loyalty system dispatches the following events:

- `PointsEarned` - When points are earned
- `TierUpgraded` - When a guest upgrades to a new tier
- `RewardRedeemed` - When a reward is redeemed
- `GuestEnrolled` - When a guest is enrolled in a program

You can listen to these events to send notifications, update analytics, etc.:

```php
use Modules\Loyalty\Domain\Events\PointsEarned;

Event::listen(PointsEarned::class, function (PointsEarned $event) {
    // Send notification, update analytics, etc.
});
```

## Configuration

See `config/loyalty.php` for all configuration options:

- `defaults` - Default values
- `cache` - Cache settings and TTL
- `auto_enrollment` - Auto-enrollment settings
- `tiers` - Default tier configuration
- `expiration` - Point expiration settings
- `integrations` - Vertical-specific integration settings
- `notifications` - Notification settings
- `fraud_detection` - Fraud detection settings
- `queue` - Queue settings for async operations

## Testing

Run the test suite:

```bash
./vendor/bin/pest tests/Modules/Loyalty
```

## Performance Optimization

- **Caching**: All frequently accessed data is cached with configurable TTL
- **Queue**: Heavy operations can be queued for async processing
- **Database Indexes**: Proper indexes on all foreign keys and frequently queried fields
- **Eager Loading**: Relationships are eager-loaded to prevent N+1 queries

## Security

- **Fraud Detection**: Configurable limits on points earned and redemptions per day
- **Transaction Integrity**: All point operations are wrapped in database transactions
- **Audit Logging**: All transactions are logged with correlation IDs
- **PII Compliance**: Personal data is handled according to compliance requirements

## Troubleshooting

### Points not being awarded

1. Check if the loyalty program is active
2. Verify the program schedule (starts_at/ends_at)
3. Check if rules are active and match conditions
4. Verify the guest is enrolled in the program

### Tier not upgrading

1. Check if tier system is enabled in program settings
2. Verify tier requirements (min_points, min_spend, min_visits)
3. Check if tiers are sorted correctly
4. Ensure checkTierUpgrade is called after points are earned

### Cache issues

Clear loyalty cache:
```bash
php artisan cache:clear
php artisan config:clear
```

## Integration Checklist

- [x] Run migrations
- [x] Configure environment variables
- [x] Create loyalty program
- [x] Configure tiers
- [x] Create earning rules
- [x] Create rewards
- [x] Integrate with order processing
- [x] Integrate with booking processing
- [x] Add Livewire components to views
- [x] Set up event listeners for notifications
- [x] Configure fraud detection limits
- [x] Test end-to-end flow
- [x] Monitor performance with cache stats

## License

Proprietary - CatVRF
