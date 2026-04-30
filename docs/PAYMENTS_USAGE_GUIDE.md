# CatVRF Payments Vertical - Usage Guide

**Version:** 1.0  
**Date:** 2026-04-29

---

## Overview

The CatVRF Payments Vertical provides a production-ready, enterprise-grade payment processing system with:

- **Smart Routing**: Automatic gateway selection based on client type, amount, fraud risk
- **Escrow**: Hold funds until conditions are met (marketplace scenarios)
- **Split Payments**: Automatic distribution to sellers with platform commission
- **Recurring Billing**: Subscription management with automatic charging
- **Payout Batches**: Mass seller payouts with batch processing
- **Resilience**: Circuit breakers, idempotency, outbox pattern for reliability
- **Compliance**: PCI DSS, 152-ФЗ ready with audit logging

---

## Quick Start

### Basic Payment Processing

```php
use App\Domains\Payment\Facades\Payment;
use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;

// Process a payment
$result = Payment::process(
    payable: $order,
    method: PaymentMethodVO::fromString('card'),
    amount: MoneyVO::fromDecimal(1000.50), // 1000.50 RUB
);

if ($result->isSuccess()) {
    // Payment successful
    $paymentUrl = $result->paymentUrl;
    $providerPaymentId = $result->providerPaymentId;
} elseif ($result->requiresUserAction()) {
    // 3DS or other action required
    $nextAction = $result->nextAction;
    $clientSecret = $result->clientSecret;
} else {
    // Payment failed
    $error = $result->errorMessage;
}
```

### Payment with Preferred Gateway

```php
$result = Payment::process(
    payable: $order,
    method: PaymentMethodVO::fromString('card'),
    amount: MoneyVO::fromDecimal(1000.50),
    preferredGateway: 'tinkoff', // Force Tinkoff
);
```

### Refund Payment

```php
$result = Payment::refund(
    paymentIntentId: $paymentIntent->uuid,
    amount: MoneyVO::fromDecimal(500.00), // Partial refund
    reason: 'Customer request',
);
```

### Cancel Payment (Before Capture)

```php
$result = Payment::cancel($paymentIntent->uuid);
```

---

## Escrow Operations

### Create Escrow Hold

```php
use App\Domains\Payment\Facades\Payment;
use App\Domains\Payment\Models\PaymentIntent;

$hold = Payment::escrowHold(
    paymentIntent: $paymentIntent,
    walletId: $sellerWallet->id,
    amount: MoneyVO::fromDecimal(1000.50),
    releaseConditions: [
        'order_delivered' => true,
        'customer_confirmed' => true,
    ],
    autoReleaseAt: now()->addDays(7), // Auto-release after 7 days
);
```

### Release Escrow Funds

```php
$hold = Payment::escrowRelease(
    hold: $hold,
    amountKopecks: 100050, // Release full amount
    targetWalletId: $sellerWallet->id,
    reason: 'Order delivered and confirmed',
);
```

### Partial Release

```php
$hold = Payment::escrowRelease(
    hold: $hold,
    amountKopecks: 50025, // Release half
    targetWalletId: $sellerWallet->id,
    reason: 'Partial fulfillment',
);
```

### Cancel Escrow Hold

```php
$hold = Payment::escrowCancel(
    hold: $hold,
    reason: 'Order canceled',
);
```

---

## Split Payments & Payouts

### Split Payment to Multiple Sellers

```php
use App\Domains\Payment\Facades\Payment;

$result = Payment::splitAndPayout($order);

// Returns:
// [
//     'platform_fee' => 5000, // Platform commission in kopecks
//     'payouts' => Collection<Payout>, // Seller payouts
// ]
```

### Create Payout Batch

```php
use App\Domains\Payment\Facades\Payment;

$payouts = [
    ['seller_id' => 1, 'amount_kopecks' => 100000, 'bank_account' => '...'],
    ['seller_id' => 2, 'amount_kopecks' => 150000, 'bank_account' => '...'],
];

$batch = Payment::createPayoutBatch(
    payouts: $payouts,
    provider: 'tochka', // Use Tochka for B2B payouts
    scheduledAt: now()->addHours(2), // Schedule for later
);
```

### Process Payout Batch

```php
$batch = Payment::processPayoutBatch($batch);

echo "Processed: {$batch->processed_count}/{$batch->total_count}";
echo "Failed: {$batch->failed_count}";
```

---

## Recurring Subscriptions

### Create Subscription

```php
use App\Domains\Payment\Facades\Payment;

$subscription = Payment::createSubscription(
    userId: $user->id,
    productId: $product->id,
    amount: MoneyVO::fromDecimal(299.00), // Monthly fee
    interval: 'month',
    intervalCount: 1,
    trialEnd: now()->addDays(14), // 14-day trial
);
```

### Charge Subscription

```php
$result = Payment::chargeSubscription($subscription);

if ($result->isSuccess()) {
    $subscription->update([
        'last_payment_at' => now(),
        'next_payment_at' => now()->addMonth(),
    ]);
}
```

### Process Due Subscriptions (Scheduled Job)

```php
// Add to app/Console/Kernel.php
$schedule->call(function () {
    $processed = Payment::processDueSubscriptions();
    Log::info("Processed {$processed} subscriptions");
})->daily();
```

---

## Smart Routing

### Get Best Gateway for Payment

```php
use App\Domains\Payment\Facades\Payment;

$routing = Payment::routeToBestGateway(
    amount: MoneyVO::fromDecimal(5000.00),
    method: PaymentMethodVO::fromString('card'),
    clientType: 'b2c',
    fraudScore: 0.2, // Low risk
    region: 'moscow',
);

// Returns:
// [
//     'provider' => 'tinkoff',
//     'confidence' => 0.95,
//     'reason' => 'smart_selection',
// ]
```

### Manual Gateway Selection

```php
$result = Payment::process(
    payable: $order,
    method: PaymentMethodVO::fromString('card'),
    amount: MoneyVO::fromDecimal(1000.50),
    preferredGateway: 'tochka', // Override smart routing
);
```

---

## Order Service Integration

### Complete Order Payment Flow

```php
use App\Domains\Payment\Facades\Payment;
use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;

class OrderService
{
    public function processOrderPayment(Order $order, string $paymentMethod): PaymentResultDTO
    {
        // 1. Process payment
        $result = Payment::process(
            payable: $order,
            method: PaymentMethodVO::fromString($paymentMethod),
            amount: MoneyVO::fromDecimal($order->total_amount),
        );

        if (! $result->isSuccess()) {
            throw new PaymentException($result->errorMessage);
        }

        // 2. Create escrow hold for marketplace
        if ($order->isMarketplace()) {
            $paymentIntent = Payment::getIntent($result->paymentIntentId);
            Payment::escrowHold(
                paymentIntent: $paymentIntent,
                walletId: $order->seller->wallet_id,
                amount: MoneyVO::fromDecimal($order->total_amount),
                releaseConditions: ['order_id' => $order->id],
                autoReleaseAt: now()->addDays(7),
            );
        }

        // 3. Update order status
        $order->update([
            'status' => 'paid',
            'payment_intent_id' => $result->paymentIntentId,
        ]);

        return $result;
    }

    public function releaseSellerPayment(Order $order): void
    {
        $paymentIntent = Payment::getIntent($order->payment_intent_id);
        $hold = $paymentIntent->escrowHolds->first();

        if ($hold && $hold->hasRemainingFunds()) {
            Payment::escrowRelease(
                hold: $hold,
                amountKopecks: $hold->remaining_amount_kopecks,
                targetWalletId: $order->seller->wallet_id,
                reason: 'Order fulfilled',
            );
        }
    }

    public function refundOrder(Order $order, string $reason): void
    {
        $result = Payment::refund(
            paymentIntentId: $order->payment_intent_id,
            amount: MoneyVO::fromDecimal($order->total_amount),
            reason: $reason,
        );

        if ($result->isSuccess()) {
            $order->update(['status' => 'refunded']);
        }
    }
}
```

---

## Scheduled Jobs

### Configure in `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Process outbox messages every 5 minutes
    $schedule->call(function () {
        Payment::processOutbox();
    }->everyFiveMinutes();

    // Process expired escrow holds hourly
    $schedule->call(function () {
        Payment::processExpiredEscrow();
    })->hourly();

    // Process due subscriptions daily at 02:00
    $schedule->call(function () {
        Payment::processDueSubscriptions();
    })->dailyAt('02:00');

    // Process payout batches every 15 minutes
    $schedule->call(function () {
        $batches = PayoutBatch::readyToProcess()->get();
        foreach ($batches as $batch) {
            Payment::processPayoutBatch($batch);
        }
    })->everyFifteenMinutes();
}
```

---

## Configuration

### Gateway Configuration (`config/payment.php`)

```php
return [
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'tinkoff'),
    'webhook_url' => env('PAYMENT_WEBHOOK_URL'),
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    
    'gateways' => [
        'tinkoff' => [
            'terminal_key' => env('TINKOFF_TERMINAL_KEY'),
            'secret_key' => env('TINKOFF_SECRET_KEY'),
            'api_url' => 'https://securepay.tinkoff.ru/v2/',
        ],
        'tochka' => [
            'client_id' => env('TOCHKA_CLIENT_ID'),
            'client_secret' => env('TOCHKA_CLIENT_SECRET'),
            'api_url' => 'https://enter.tochka.com/api/',
        ],
        'sber' => [
            'terminal_key' => env('SBER_TERMINAL_KEY'),
            'secret_key' => env('SBER_SECRET_KEY'),
            'api_url' => 'https://securepayments.sberbank.ru/',
        ],
    ],

    'smart_routing' => [
        'enabled' => env('PAYMENT_SMART_ROUTING_ENABLED', true),
        'circuit_breaker_threshold' => 5,
        'circuit_breaker_timeout' => 60,
    ],

    'escrow' => [
        'auto_release_enabled' => true,
        'default_hold_days' => 7,
    ],

    'recurring' => [
        'max_retry_attempts' => 3,
        'retry_delay_hours' => 24,
    ],

    'outbox' => [
        'max_retry_attempts' => 5,
        'cleanup_days' => 30,
    ],
];
```

### Environment Variables (`.env`)

```env
# Payment Gateways
TINKOFF_TERMINAL_KEY=your_terminal_key
TINKOFF_SECRET_KEY=your_secret_key
TOCHKA_CLIENT_ID=your_client_id
TOCHKA_CLIENT_SECRET=your_client_secret
SBER_TERMINAL_KEY=your_terminal_key
SBER_SECRET_KEY=your_secret_key

# Payment Configuration
PAYMENT_DEFAULT_GATEWAY=tinkoff
PAYMENT_WEBHOOK_URL=https://your-domain.com/api/payment/webhook
PAYMENT_WEBHOOK_SECRET=your_webhook_secret
PAYMENT_SMART_ROUTING_ENABLED=true

# Redis (for circuit breaker, smart routing metrics)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## API Endpoints

### Process Payment
```
POST /api/v1/payments/process
{
    "payable_type": "App\\Models\\Order",
    "payable_id": 123,
    "payment_method": "card",
    "amount": 1000.50,
    "preferred_gateway": "tinkoff"
}
```

### Get Payment Status
```
GET /api/v1/payments/{payment_intent_id}/status
```

### Refund Payment
```
POST /api/v1/payments/{payment_intent_id}/refund
{
    "amount": 500.00,
    "reason": "Customer request"
}
```

### Cancel Payment
```
POST /api/v1/payments/{payment_intent_id}/cancel
```

### Get Escrow Holds
```
GET /api/v1/payments/escrow/holds?wallet_id={wallet_id}
```

### Release Escrow
```
POST /api/v1/payments/escrow/{hold_id}/release
{
    "amount_kopecks": 100050,
    "target_wallet_id": 456,
    "reason": "Order fulfilled"
}
```

---

## Error Handling

### PaymentException

```php
use App\Domains\Payment\Exceptions\PaymentException;

try {
    $result = Payment::process($order, $method, $amount);
} catch (PaymentException $e) {
    // Handle payment-specific errors
    Log::error('Payment failed', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);
    
    return response()->json([
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
    ], 422);
}
```

### Fraud Block

```php
if ($result->isError() && $result->errorCode === 'FRAUD_DETECTED') {
    // Notify user about fraud block
    // Log for review
    // Optionally escalate to manual review
}
```

### Gateway Failure

```php
if ($result->isError()) {
    // Smart routing will automatically try fallback gateway
    // Log the failure for monitoring
    Log::warning('Payment gateway failed', [
        'error' => $result->errorMessage,
        'correlation_id' => $result->correlationId,
    ]);
}
```

---

## Testing

### Unit Test Example

```php
use App\Domains\Payment\ValueObjects\MoneyVO;
use Tests\TestCase;

class MoneyVOTest extends TestCase
{
    public function test_money_addition()
    {
        $money1 = new MoneyVO(10000); // 100.00 RUB
        $money2 = new MoneyVO(5000);  // 50.00 RUB
        
        $sum = $money1->add($money2);
        
        $this->assertEquals(15000, $sum->toKopecks());
        $this->assertEquals(150.00, $sum->toDecimal());
    }

    public function test_money_comparison()
    {
        $money1 = new MoneyVO(10000);
        $money2 = new MoneyVO(5000);
        
        $this->assertTrue($money1->greaterThan($money2));
        $this->assertTrue($money2->lessThan($money1));
    }
}
```

### Feature Test Example

```php
use App\Domains\Payment\Facades\Payment;
use App\Models\Order;
use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    public function test_payment_processing_flow()
    {
        $order = Order::factory()->create();
        
        $result = Payment::process(
            payable: $order,
            method: PaymentMethodVO::fromString('card'),
            amount: MoneyVO::fromDecimal(1000.50),
        );
        
        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->paymentIntentId);
        
        $intent = Payment::getIntent($result->paymentIntentId);
        $this->assertEquals('succeeded', $intent->status);
    }
}
```

---

## Monitoring & Debugging

### Check Payment Status

```php
$intent = Payment::getIntent($uuid);
$status = Payment::getStatus($uuid);

echo "Status: {$status->label()}";
echo "Is Successful: {$status->isSuccessful()}";
echo "Can Refund: {$status->canRefund()}";
```

### View Escrow Holds

```php
$holds = $escrowService->getActiveHolds($walletId);

foreach ($holds as $hold) {
    echo "Hold ID: {$hold->id}";
    echo "Amount: {$hold->amount_kopecks} kopecks";
    echo "Remaining: {$hold->remaining_amount_kopecks} kopecks";
    echo "Status: {$hold->status}";
}
```

### Check Circuit Breaker State

```php
$redis = Redis::connection();
$state = $redis->get('payment:circuit:tinkoff:state');

if ($state === 'open') {
    Log::warning('Tinkoff circuit breaker is open');
}
```

---

## Best Practices

1. **Always use correlation IDs** for tracking payment flows
2. **Handle idempotency** by providing unique idempotency keys
3. **Check payment status** before attempting operations
4. **Use escrow for marketplace** scenarios to protect buyers
5. **Monitor circuit breakers** and gateway health
6. **Implement proper error handling** with retries
7. **Log all payment operations** for audit trails
8. **Test with sandbox environments** before production
9. **Use smart routing** for optimal gateway selection
10. **Keep webhook secrets secure** and verify callbacks

---

## Support & Troubleshooting

### Common Issues

**Payment fails with "Circuit breaker open"**
- Gateway is temporarily unavailable
- Smart routing will try fallback gateway
- Check gateway status and monitor logs

**Escrow hold not releasing**
- Check release conditions are met
- Verify target wallet exists
- Check for sufficient funds
- Review audit logs for errors

**Recurring subscription not charging**
- Check payment method is valid
- Verify subscription is active
- Review failed payment count
- Check fraud score

**Outbox messages not delivering**
- Check webhook URL is accessible
- Verify webhook secret matches
- Review retry attempts
- Check for network issues

### Getting Help

- Check logs: `storage/logs/payment.log`
- Review monitoring dashboards
- Consult runbooks in `/docs`
- Contact on-call team for critical issues

---

## Changelog

### Version 1.0 (2026-04-29)
- Initial production release
- Smart routing with circuit breakers
- Escrow hold/release operations
- Split payments and payouts
- Recurring subscription billing
- Outbox pattern for reliable delivery
- Full PCI DSS and 152-ФЗ compliance
