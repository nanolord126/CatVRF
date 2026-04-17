# Cart Domain

## Overview
The Cart domain handles shopping cart management following the 20-cart limit rule with 20-minute reservation periods and price protection (user never pays less than when added).

## Architecture Layers

### Layer 1: Models
- **Cart** - Shopping cart model with tenant scoping
- **CartItem** - Cart item model with price tracking

### Layer 2: DTOs
- **AddItemDto** - Data transfer object for adding items

### Layer 3: Services
- **CartService** - Cart operations including add, remove, refresh prices, and inventory integration

### Layer 4: Requests
- **AddItemRequest** - Form request for adding items

### Layer 5: Resources
- **CartResource** - API resource for carts
- **CartItemResource** - API resource for cart items

### Layer 6: Events
- **CartItemAddedEvent** - Dispatched when item is added
- **CartItemRemovedEvent** - Dispatched when item is removed
- **CartClearedEvent** - Dispatched when cart is cleared

### Layer 7: Listeners
- **CartItemAddedListener** - Handles item addition with inventory reservation
- **CartItemRemovedListener** - Handles item removal with inventory release

### Layer 8: Jobs
- **CartCleanupJob** - Scheduled job for cleaning expired carts (every minute)
- **PriceRefreshJob** - Scheduled job for refreshing cart prices

### Layer 9: Filament Resources
- **CartResource** - Admin UI for cart management
- **CartItemResource** - Admin UI for cart item management

## Database Schema

### carts Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `seller_id` - Seller (foreign key)
- `status` - Status (active, ordered, cleared)
- `reserved_until` - Reservation end timestamp
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

### cart_items Table
- `id` - Primary key
- `uuid` - Unique identifier
- `cart_id` - Cart (foreign key)
- `product_id` - Product (foreign key)
- `quantity` - Quantity
- `price_at_add` - Price when added (kopecks)
- `current_price` - Current price (kopecks)
- `correlation_id` - Correlation ID
- `created_at`, `updated_at` - Timestamps

## Business Rules

### 1 Seller = 1 Cart
Each cart contains items from a single seller only.

### Maximum 20 Carts
Users can have maximum 20 active carts simultaneously.

### 20-Minute Reservation
Cart items are reserved for 20 minutes after being added.

### Price Protection
- If price increases → user pays new (higher) price
- If price decreases → user pays old (lower) price
- User never pays less than what they added to cart

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request
- `Illuminate\Contracts\Auth\Guard` - Authentication
- `Carbon\CarbonInterface` - Date/time operations

## Usage Examples

### Add Item to Cart
```php
$dto = new AddItemDto(
    userId: 5,
    sellerId: 10,
    productId: 123,
    quantity: 2,
    currentPrice: 99900, // 999 rubles
    correlationId: $correlationId,
);
$item = $cartService->addItem($dto, $correlationId);
```

### Refresh Cart Prices
```php
$newPrices = [
    123 => 109900, // new price for product 123
    124 => 89900,  // new price for product 124
];
$cartService->refreshPrices($cartId, $newPrices, $correlationId);
```

### Remove Item from Cart
```php
$cartService->removeItem($cartId, $productId, $correlationId);
```

### Clear Cart
```php
$cartService->clear($cartId, 'ordered', $correlationId);
```

### Get User Carts
```php
$carts = $cartService->getUserCarts($userId);
```

### Get Cart Total
```php
$total = $cartService->getTotal($cartId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter CartDomain
```

## Queue Configuration
All jobs use the `cart` queue as defined in `config/domain_queues.php`.
