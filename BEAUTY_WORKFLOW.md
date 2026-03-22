<!-- Beauty вертикаль - полный жизненный цикл запуска -->

## Архитектура Beauty вертикали

```
┌─────────────────────────────────────────────────────────────────┐
│                    BEAUTY LIFECYCLE                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. SALON REGISTRATION (Multi-tenant)                            │
│     └─ BeautySalon created in tenant schema                      │
│        └─ Auto create wallet account via Observer                │
│                                                                   │
│  2. SERVICE CREATION                                             │
│     └─ Salon creates Service (price, duration)                   │
│     └─ Service stored in beauty_services table                   │
│                                                                   │
│  3. BOOKING FLOW                                                 │
│     ┌─ Customer views available Services                         │
│     ├─ Creates Booking (service, date/time, notes)               │
│     ├─ Status: PENDING → requires payment                        │
│     └─ Booking stored with correlation_id for audit trail       │
│                                                                   │
│  4. PAYMENT FLOW (Tinkoff Sandbox)                               │
│     ┌─ PaymentService.initiatePayment(booking)                   │
│     ├─ Creates Payment record (status: PENDING)                  │
│     ├─ Calls TinkoffGateway.createPayment()                      │
│     ├─ Returns payment URL for customer redirect                 │
│     └─ Customer completes payment at Tinkoff                     │
│                                                                   │
│  5. WEBHOOK CALLBACK                                             │
│     ┌─ Tinkoff sends callback to /beauty/payment/callback        │
│     ├─ Verify signature with TinkoffGateway.verifyCallback()     │
│     ├─ Update Payment status: CONFIRMED                          │
│     ├─ Calculate split:                                          │
│     │   └─ Salon payout: 80% → wallet.deposit()                  │
│     │   └─ Platform: 20% → platform wallet                       │
│     └─ Update Booking status: CONFIRMED                          │
│                                                                   │
│  6. SALON DASHBOARD                                              │
│     ┌─ Salon sees new Booking in Filament Resource               │
│     ├─ Complete status: COMPLETED (service provided)             │
│     ├─ Or Cancel status: CANCELLED (refund triggered)            │
│     └─ Wallet shows available payout balance                     │
│                                                                   │
│  7. PAYOUT                                                       │
│     └─ Batch payout to salon bank account (scheduled job)        │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Database Schema

### beauty_salons

```sql
id, tenant_id, user_id, name, phone, address, city, rating, status, created_at
```

### beauty_services

```sql
id, tenant_id, salon_id, name, description, price, duration_minutes, is_active, category
```

### beauty_bookings

```sql
id, tenant_id, salon_id, service_id, customer_id, scheduled_at, status, notes, correlation_id
```

### beauty_payments

```sql
id, tenant_id, salon_id, booking_id, amount, status, payment_method, tinkoff_payment_id, 
salon_payout_amount, platform_commission_amount, commission_percent, completed_at, correlation_id
```

## Key Enums

### BookingStatus

- `pending` - ожидание оплаты
- `confirmed` - оплачено, ожидание услуги
- `completed` - услуга оказана
- `cancelled` - отменено (возврат)
- `no_show` - не явилась

### PaymentStatus

- `pending` - ожидание платежа
- `confirmed` - оплачено
- `failed` - ошибка платежа
- `refunded` - возвращено
- `cancelled` - отменено

## Services

### BookingService

```php
createBooking(Service $service, int $customerId, string $scheduledAt, ?string $notes)
confirmBooking(Booking $booking)
completeBooking(Booking $booking)
cancelBooking(Booking $booking, ?string $reason)
```

### PaymentService

```php
initiatePayment(Booking $booking): array // returns [payment_id, payment_url, correlation_id]
confirmPayment(Payment $payment, string $tinkoffPaymentId)
failPayment(Payment $payment, string $reason)
refundPayment(Payment $payment, string $reason)
```

### TinkoffGateway

```php
createPayment(int $paymentId, int $amount, string $orderId, ...): string
getPaymentStatus(string $paymentId): array
refund(string $paymentId): bool
verifyCallback(array $data): bool
```

## Configuration

### .env

```env
# Tinkoff Sandbox (for development/testing)
TINKOFF_API_KEY=1716383938760904
TINKOFF_API_SECRET=j1Jk3mK9qL2p8nQ4

# Wallet (bavix/laravel-wallet)
WALLET_ENABLED=true
```

### config/payments.php

```php
'tinkoff' => [
    'api_key' => env('TINKOFF_API_KEY'),
    'api_secret' => env('TINKOFF_API_SECRET'),
],
```

## API Endpoints

### Public Beauty API (future - for mobile app)

```
GET  /api/beauty/services              # List all services
GET  /api/beauty/services/{id}         # Service details
POST /api/beauty/bookings              # Create booking
GET  /api/beauty/bookings/{id}         # Booking details

POST /beauty/payment/initiate           # Initiate payment
GET  /beauty/payment/success            # Success redirect
GET  /beauty/payment/failed             # Failed redirect
POST /beauty/payment/callback           # Tinkoff webhook
```

## Filament Tenant Panel Resources

### SalonResource (Admin view)

- ListPage: all salons, tenant filter
- CreatePage: register new salon
- EditPage: edit salon details, view balance

### ServiceResource

- ListPage: services by salon
- CreatePage: add service
- EditPage: edit service

### BookingResource

- ListPage: bookings by status, date range
- ViewPage: booking details, payment status
- Actions: complete, cancel, mark no-show

### PaymentResource

- ListPage: payments with status, date range
- ViewPage: payment details, salon payout amount

## Testing

### BookingTest

- create_booking
- booking_status_transitions
- cancel_booking
- upcoming_scope

### PaymentTest

- payment_initiated
- payment_confirmed_with_wallet_credit
- payment_failed
- payment_refunded

## Production Checklist

- [ ] Octane running with Swoole
- [ ] Horizon queue processing enabled
- [ ] Rate limiting configured (50 req/min on payment webhook)
- [ ] Logging configured with correlation_id tracking
- [ ] Audit logs on all Beauty mutations
- [ ] Database indexes on tenant_id, status, created_at
- [ ] Wallet accounts created for all salons
- [ ] Tinkoff API credentials in vault/secrets
- [ ] HTTPS only, secure cookies
- [ ] Config caching: `php artisan config:cache`
- [ ] Route caching: `php artisan route:cache`
