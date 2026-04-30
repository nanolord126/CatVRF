# Supermarket Buyer Flow - Setup Guide

## Overview
Complete setup guide for the Supermarket buyer flow implementation, including frontend Vue components, backend API, and database migrations.

## Prerequisites

- PHP 8.3+
- Laravel 11+
- Node.js 18+
- Vue 3
- MySQL 8.0+
- Redis (for caching and reservations)

## Installation Steps

### 1. Database Migrations

Run the migrations to create the required tables:

```bash
php artisan migrate
```

This will create:
- `cart_items` - Shopping cart with 20-minute reservations
- `tracking_sessions` - Real-time tracking sessions
- `tracking_telemetry` - Location and temperature telemetry data

### 2. API Routes

The API routes are already registered in `routes/api.php`:

```php
require __DIR__.'/api/supermarket-buyer.php';
```

This registers the following endpoints:
- `GET /api/supermarket/cart` - Get user's cart
- `POST /api/supermarket/cart/add` - Add item to cart
- `PATCH /api/supermarket/cart/{itemId}` - Update cart item
- `DELETE /api/supermarket/cart/{itemId}` - Remove item from cart
- `DELETE /api/supermarket/cart` - Clear cart
- `GET /api/supermarket/checkout/delivery-slots` - Get delivery slots
- `POST /api/supermarket/checkout` - Process checkout
- `GET /api/supermarket/tracking/{orderUuid}` - Get order tracking
- `GET /api/supermarket/recommendations/ai` - Get AI recommendations
- `GET /api/supermarket/recommendations/cross-sell` - Get cross-sell suggestions

### 3. Frontend Setup

The Vue components are located in `frontend/src/components/business/supermarket/`:

- `Cart.vue` - Shopping cart with timer
- `Checkout.vue` - One Page Checkout
- `OrderTracking.vue` - Real-time tracking
- `AIRecommendations.vue` - AI recommendations
- `SupermarketCatalog.vue` - Product catalog
- `ProductCard.vue` - Product display card
- `ProductList.vue` - Product list with filters

The API composable is in `frontend/src/composables/useSupermarketApi.ts`.

### 4. Configuration

Add the following to your `.env` file:

```env
# Supermarket Configuration
SUPERMARKET_MIN_ORDER_AMOUNT=500
SUPERMARKET_DEFAULT_SELLER_ADDRESS="Москва"
SUPERMARKET_RESERVATION_MINUTES=20
SUPERMARKET_COLD_CHAIN_FEE=5
```

## API Endpoints Reference

### Cart Endpoints

#### Get Cart
```
GET /api/supermarket/cart
Authorization: Bearer {token}
```

Response:
```json
{
  "items": [
    {
      "id": "1",
      "product": {
        "id": "123",
        "name": "Молоко 3.2%",
        "price": 89,
        "sub_vertical": "grocery_and_delivery",
        "requires_cold_chain": true,
        "image": "https://..."
      },
      "quantity": 2,
      "total": 178,
      "attributes": {}
    }
  ],
  "reservation_expires_at": "2026-04-26T14:35:00Z",
  "subtotal": 178,
  "cold_chain_required": true
}
```

#### Add to Cart
```
POST /api/supermarket/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": "123",
  "quantity": 2,
  "attributes": {}
}
```

#### Update Cart Item
```
PATCH /api/supermarket/cart/{itemId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 3
}
```

#### Remove from Cart
```
DELETE /api/supermarket/cart/{itemId}
Authorization: Bearer {token}
```

#### Clear Cart
```
DELETE /api/supermarket/cart
Authorization: Bearer {token}
```

### Checkout Endpoints

#### Get Delivery Slots
```
GET /api/supermarket/checkout/delivery-slots?address=Москва,+ул.+Пушкина+10
Authorization: Bearer {token}
```

Response:
```json
{
  "slots": [
    {
      "id": "slot_1",
      "date": "2026-04-27",
      "time_range": "10:00-12:00",
      "cost": 199
    },
    {
      "id": "slot_2",
      "date": "2026-04-27",
      "time_range": "12:00-14:00",
      "cost": 199
    }
  ]
}
```

#### Process Checkout
```
POST /api/supermarket/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "address": "Москва, ул. Пушкина 10, кв. 5",
  "slot_id": "slot_1",
  "payment_method": "card"
}
```

Response:
```json
{
  "success": true,
  "order_uuid": "123e4567-e89b-12d3-a456-426614174000",
  "total_amount": 377
}
```

### Tracking Endpoints

#### Get Order Tracking
```
GET /api/supermarket/tracking/{orderUuid}
Authorization: Bearer {token}
```

Response:
```json
{
  "status": "in_transit",
  "cold_chain_required": true,
  "eta_minutes": 15,
  "temperature": 4.5,
  "distance": 2.3,
  "courier_location": {
    "x": 20,
    "y": 30
  },
  "courier": {
    "name": "Алексей",
    "vehicle": "Peugeot Partner",
    "rating": 4.8
  },
  "temperature_history": [3.2, 3.5, 4.0, 4.2, 4.5, 4.3, 4.1, 4.5, 4.8, 4.5]
}
```

### Recommendations Endpoints

#### Get AI Recommendations
```
GET /api/supermarket/recommendations/ai
Authorization: Bearer {token}
```

Response:
```json
{
  "products": [
    {
      "id": "456",
      "name": "Сыр Российский",
      "price": 150,
      "sub_vertical": "grocery_and_delivery",
      "requires_cold_chain": true,
      "image": "https://...",
      "match_score": 87,
      "reason": "Основано на ваших предыдущих заказах"
    }
  ],
  "taste_profile": ["Бакалея", "Мясные"]
}
```

#### Get Cross-sell Recommendations
```
GET /api/supermarket/recommendations/cross-sell
Authorization: Bearer {token}
```

Response:
```json
[
  {
    "id": "789",
    "name": "Хлеб белый",
    "price": 45,
    "sub_vertical": "grocery_and_delivery",
    "requires_cold_chain": false,
    "image": "https://..."
  }
]
```

## Frontend Usage

### Using the Cart Component

```vue
<script setup lang="ts">
import Cart from '@/components/business/supermarket/Cart.vue'
</script>

<template>
  <Cart />
</template>
```

### Using the Checkout Component

```vue
<script setup lang="ts">
import Checkout from '@/components/business/supermarket/Checkout.vue'
</script>

<template>
  <Checkout />
</template>
```

### Using the Order Tracking Component

```vue
<script setup lang="ts">
import OrderTracking from '@/components/business/supermarket/OrderTracking.vue'
</script>

<template>
  <OrderTracking order-uuid="123e4567-e89b-12d3-a456-426614174000" />
</template>
```

### Using the API Composable

```typescript
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()

// Get cart
const cart = await api.fetchCart()

// Add to cart
await api.addToCart('123', 2)

// Process checkout
const result = await api.processCheckout({
  address: 'Москва, ул. Пушкина 10',
  slot_id: 'slot_1',
  payment_method: 'card'
})
```

## Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run supermarket-specific tests
php artisan test --filter=Supermarket
```

### Manual Testing

1. **Cart Flow**
   - Add items to cart
   - Verify 20-minute reservation timer
   - Update quantities
   - Remove items
   - Check cold chain warnings

2. **Checkout Flow**
   - Proceed to checkout
   - Select delivery slot
   - Choose payment method
   - Complete order

3. **Tracking Flow**
   - View order tracking page
   - Verify temperature display
   - Check ETA updates

## Troubleshooting

### Cart Reservation Expiry

If cart items expire before checkout:
- Increase `SUPERMARKET_RESERVATION_MINUTES` in `.env`
- Check Redis is running for cache
- Verify `reservation_expires_at` is being set correctly

### Delivery Slots Not Loading

If delivery slots are not available:
- Check GeoLogisticsAdapter configuration
- Verify address format is correct
- Check cache tags are being flushed properly

### Cold Chain Temperature Not Updating

If temperature data is missing:
- Verify ColdChainAdapter is configured
- Check IoT sensors are sending data
- Verify tracking session is active

## Performance Optimization

### Caching

The implementation uses cache tags for performance:
- `supermarket:products` - Product data
- `supermarket:delivery_slots` - Delivery slots
- `supermarket:recommendations` - AI recommendations

Cache TTL:
- Products: 30 minutes
- Delivery slots: 15 minutes
- Recommendations: 15 minutes

### Redis Configuration

Ensure Redis is configured for cache:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Security Considerations

1. **Fraud Detection** - All checkout operations go through FraudControlService
2. **Audit Logging** - All cart and checkout operations are logged
3. **PII Protection** - No personal data sent to external LLMs
4. **Tenant Isolation** - All queries scoped to tenant_id
5. **Rate Limiting** - API endpoints are rate-limited

## Compliance

- **152-FZ** - Medical/food data compliance
- **152-FZ** - PII protection
- **Cold Chain** - Temperature monitoring for perishable goods
- **Audit Trail** - All operations logged

## Monitoring

### Key Metrics to Monitor

- Cart abandonment rate
- Reservation expiry rate
- Checkout completion rate
- Average time to checkout
- Cold chain temperature violations
- Delivery slot utilization

### Logging

Audit logs are stored in:
- Database audit table
- Application logs
- Correlation ID for tracing

## Future Enhancements

1. **WebSocket Integration** - Real-time updates without polling
2. **Push Notifications** - Order status updates
3. **Payment Gateway Integration** - Actual payment processing
4. **Map Provider Integration** - Real-time courier tracking
5. **ML Model** - Enhanced AI recommendations
6. **Voice Chat** - Voice communication with courier
7. **Delivery Photos** - Photo proof of delivery

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Check audit logs in database
- Verify Redis is running
- Check GeoLogisticsAdapter configuration
