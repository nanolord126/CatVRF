# Bonuses Vertical Integration Guide

## Overview

This guide shows how to integrate the Bonuses vertical into other CatVRF verticals using the 9-layer DDD architecture.

## Integration Pattern

### 1. Using BonusFacade (Recommended)

The simplest way to integrate is through the `Bonus` facade:

```php
use App\Domains\Bonuses\Facades\Bonus;

// Award bonus after order completion
Bonus::award(
    userId: $order->user_id,
    tenantId: $order->tenant_id,
    amount: $bonusAmount,
    type: 'loyalty',
    reason: 'order_completed',
    sourceType: 'order',
    sourceId: $order->id,
    verticalCode: 'Beauty',
);
```

### 2. Using BonusService Directly

For more complex scenarios, inject the service:

```php
use App\Domains\Bonuses\Services\BonusService;
use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\Enums\BonusType;

final class OrderService
{
    public function __construct(
        private readonly BonusService $bonusService,
    ) {}

    public function completeOrder(Order $order): void
    {
        // ... order completion logic ...

        // Calculate bonus
        $bonusAmount = $this->bonusService->calculateBonusForOrder(
            orderAmount: $order->total_amount,
            ruleType: 'loyalty',
            verticalCode: 'Beauty',
            userType: $order->user->type,
        );

        // Award bonus
        $dto = new AwardBonusDto(
            userId: $order->user_id,
            tenantId: $order->tenant_id,
            amount: $bonusAmount,
            type: BonusType::LOYALTY,
            reason: 'order_completed',
            sourceType: 'order',
            sourceId: $order->id,
            verticalCode: 'Beauty',
            correlationId: $order->correlation_id,
        );

        $this->bonusService->award($dto);
    }
}
```

## Vertical-Specific Examples

### BeautyMasters Vertical

```php
// modules/BeautyMasters/Application/Services/AppointmentService.php

use App\Domains\Bonuses\Facades\Bonus;

final class AppointmentService
{
    public function completeAppointment(Appointment $appointment): void
    {
        // ... appointment completion ...

        // Award loyalty bonus (1.2x multiplier for Beauty)
        $bonusAmount = (int) ($appointment->price * 0.015 * 1.2);

        Bonus::award(
            userId: $appointment->user_id,
            tenantId: $appointment->tenant_id,
            amount: $bonusAmount,
            type: 'loyalty',
            reason: 'appointment_completed',
            sourceType: 'appointment',
            sourceId: $appointment->id,
            verticalCode: 'Beauty',
        );
    }
}
```

### Restaurant Vertical

```php
// modules/Restaurant/Services/OrderService.php

use App\Domains\Bonuses\Facades\Bonus;

final class OrderService
{
    public function completeOrder(Order $order): void
    {
        // ... order completion ...

        // Award bonus (1.0x multiplier for Food)
        $bonusAmount = (int) ($order->total * 0.01);

        Bonus::award(
            userId: $order->user_id,
            tenantId: $order->tenant_id,
            amount: $bonusAmount,
            type: 'loyalty',
            reason: 'order_completed',
            sourceType: 'order',
            sourceId: $order->id,
            verticalCode: 'Food',
        );
    }
}
```

### Hotels Vertical

```php
// modules/Hotels/Services/BookingService.php

use App\Domains\Bonuses\Facades\Bonus;

final class BookingService
{
    public function completeBooking(Booking $booking): void
    {
        // ... booking completion ...

        // Award bonus (1.5x multiplier for Hotels)
        $bonusAmount = (int) ($booking->total_price * 0.02 * 1.5);

        Bonus::award(
            userId: $booking->user_id,
            tenantId: $booking->tenant_id,
            amount: $bonusAmount,
            type: 'loyalty',
            reason: 'booking_completed',
            sourceType: 'booking',
            sourceId: $booking->id,
            verticalCode: 'Hotels',
        );
    }
}
```

### AI Constructor Vertical

```php
// modules/AIConstructor/Services/ConstructorService.php

use App\Domains\Bonuses\Facades\Bonus;

final class ConstructorService
{
    public function completeConstruction(ConstructorSession $session): void
    {
        // ... construction completion ...

        // Award bonus for AI constructor usage (50 bonus per use)
        Bonus::award(
            userId: $session->user_id,
            tenantId: $session->tenant_id,
            amount: 50,
            type: 'ai_constructor',
            reason: 'ai_constructor_completed',
            sourceType: 'constructor_session',
            sourceId: $session->id,
            verticalCode: $session->vertical_code,
        );
    }
}
```

## Spending Bonuses

### Checkout Integration

```php
// modules/Payment/Services/PaymentService.php

use App\Domains\Bonuses\Facades\Bonus;

final class PaymentService
{
    public function processPayment(PaymentRequest $request): Payment
    {
        $order = $request->order;

        // Check if user wants to use bonuses
        if ($request->use_bonuses && $request->bonus_amount > 0) {
            $availableBalance = Bonus::getAvailableBalance((string) $order->user_id);

            if ($availableBalance < $request->bonus_amount) {
                throw new InsufficientBonusBalanceException();
            }

            // Spend bonuses
            Bonus::spend(
                userId: $order->user_id,
                tenantId: $order->tenant_id,
                amount: $request->bonus_amount,
                reason: 'checkout',
                sourceType: 'order',
                sourceId: $order->id,
            );

            // Reduce order total
            $order->total_amount -= $request->bonus_amount;
        }

        // ... process payment ...
    }
}
```

## B2B Withdrawal Integration

```php
// modules/B2B/Services/PartnerService.php

use App\Domains\Bonuses\Facades\Bonus;

final class PartnerService
{
    public function requestWithdrawal(
        Partner $partner,
        int $amount,
        BankDetails $bankDetails,
    ): void {
        Bonus::withdraw(
            userId: $partner->user_id,
            tenantId: $partner->tenant_id,
            amount: $amount,
            withdrawalMethod: 'bank_transfer',
            bankAccountNumber: $bankDetails->account_number,
            bankName: $bankDetails->bank_name,
            bic: $bankDetails->bic,
            inn: $bankDetails->inn,
        );
    }
}
```

## Scheduled Tasks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Unlock expired holds daily at 00:00
    $schedule->call(fn () => Bonus::unlockExpiredHolds())
        ->daily()
        ->at('00:00')
        ->description('Unlock expired bonus holds');

    // Expire old bonuses daily at 01:00
    $schedule->call(fn () => Bonus::expireOldBonuses())
        ->daily()
        ->at('01:00')
        ->description('Expire old bonuses');
}
```

## Configuration

Configure vertical multipliers in `config/bonuses.php`:

```php
'vertical_multipliers' => [
    'beauty' => 1.2,
    'fashion' => 1.1,
    'food' => 1.0,
    'hotels' => 1.5,
    // Add your vertical here
    'your_vertical' => 1.3,
],
```

## Testing

```php
use App\Domains\Bonuses\Facades\Bonus;

test('vertical awards bonus correctly', function () {
    $bonus = Bonus::award(
        userId: 1,
        tenantId: 1,
        amount: 1000,
        type: 'loyalty',
        verticalCode: 'Beauty',
    );

    expect($bonus->amount)->toBe(1200); // 1000 * 1.2 multiplier
});
```

## Best Practices

1. **Always use correlation IDs** for tracing
2. **Use vertical multipliers** configured in `config/bonuses.php`
3. **Award bonuses asynchronously** via Jobs for high-traffic verticals
4. **Handle fraud checks** - BonusService includes automatic fraud detection
5. **Audit logging** - All bonus operations are automatically logged
6. **Event-driven** - Use listeners for side effects (notifications, BigData, etc.)

## Troubleshooting

### Bonus not awarded
- Check fraud detection logs
- Verify user eligibility
- Ensure bonus rules are enabled in config

### Balance not updating
- Check transaction status (pending vs credited)
- Verify hold period has expired
- Recalculate balance via admin endpoint

### Withdrawal failed
- Verify user is B2B
- Check minimum withdrawal amount (100 cents)
- Verify bank details are valid
