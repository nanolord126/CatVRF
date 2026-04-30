# UserProfile Domain

## Overview
The UserProfile domain handles user profile management including addresses, activity tracking, and wishlist functionality.

## Architecture Layers

### Layer 1: Models
- **UserAddress** - User address model with tenant scoping
- **UserActivity** - User activity tracking model
- **Wishlist** - User wishlist items model

### Layer 2: DTOs
- **CreateAddressDto** - Data transfer object for creating addresses
- **UpdateAddressDto** - Data transfer object for updating addresses
- **AddToWishlistDto** - Data transfer object for wishlist operations

### Layer 3: Services
- **UserAddressService** - Address CRUD operations
- **UserActivityService** - Activity tracking and analytics
- **WishlistService** - Wishlist management

### Layer 4: Requests
- **AddressRequest** - Form request for address operations
- **WishlistRequest** - Form request for wishlist operations

### Layer 5: Resources
- **UserAddressResource** - API resource for addresses
- **UserActivityResource** - API resource for activities
- **WishlistResource** - API resource for wishlist

### Layer 6: Events
- **AddressCreatedEvent** - Dispatched when address is created
- **AddressUpdatedEvent** - Dispatched when address is updated
- **WishlistItemAddedEvent** - Dispatched when item added to wishlist

### Layer 7: Listeners
- **AddressCreatedListener** - Handles address creation
- **WishlistItemAddedListener** - Handles wishlist additions

### Layer 8: Jobs
- **ActivityCleanupJob** - Scheduled job for cleaning old activities
- **WishlistSyncJob** - Queued job for wishlist synchronization

### Layer 9: Filament Resources
- **UserAddressResource** - Admin UI for address management
- **UserActivityResource** - Admin UI for activity monitoring
- **WishlistResource** - Admin UI for wishlist management

## Database Schema

### user_addresses Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `full_name` - Recipient full name
- `phone` - Phone number
- `country` - Country
- `city` - City
- `address_line_1` - Address line 1
- `address_line_2` - Address line 2
- `postal_code` - Postal code
- `is_default` - Default address flag
- `created_at`, `updated_at` - Timestamps

### user_activities Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `action_type` - Type of activity
- `resource_type` - Resource type
- `resource_id` - Resource ID
- `metadata` - JSON metadata
- `ip_address` - IP address
- `created_at`, `updated_at` - Timestamps

### wishlists Table
- `id` - Primary key
- `uuid` - Unique identifier
- `tenant_id` - Tenant (foreign key)
- `user_id` - User (foreign key)
- `product_id` - Product (foreign key)
- `price_at_add` - Price when added
- `created_at`, `updated_at` - Timestamps

## Dependencies
- `App\Services\FraudControlService` - Fraud detection
- `App\Services\AuditService` - Audit logging
- `Illuminate\Database\DatabaseManager` - Database operations
- `Psr\Log\LoggerInterface` - Logging
- `Illuminate\Http\Request` - HTTP request

## Usage Examples

### Create Address
```php
$dto = new CreateAddressDto(
    userId: 5,
    fullName: 'John Doe',
    phone: '+79001234567',
    country: 'Russia',
    city: 'Moscow',
    addressLine1: 'Main Street 123',
    postalCode: '123456',
    isDefault: true,
);
$address = $userAddressService->create($dto, $correlationId);
```

### Log Activity
```php
$userActivityService->log(
    userId: 5,
    actionType: 'product_view',
    resourceType: 'Product',
    resourceId: 123,
    metadata: ['duration' => 30],
    correlationId: $correlationId,
);
```

### Add to Wishlist
```php
$dto = new AddToWishlistDto(
    userId: 5,
    productId: 123,
    priceAtAdd: 99900,
);
$wishlistService->add($dto, $correlationId);
```

## Testing
Run domain tests with:
```bash
php artisan test --filter UserProfileDomain
```

## Queue Configuration
All jobs use the `user_profile` queue as defined in `config/domain_queues.php`.
