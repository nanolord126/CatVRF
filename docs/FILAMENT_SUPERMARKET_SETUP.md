# Supermarket Filament Setup

## Overview
Production-ready Filament admin interface for the Supermarket vertical with seller dashboard, product management, inventory tracking, and order management.

## Components Created

### 1. SupermarketOrderResource
**Location:** `Filament/Resources/SupermarketOrderResource.php`

**Features:**
- Full CRUD operations for supermarket orders
- Status badges with color coding (pending, paid, processing, shipped, delivered, cancelled)
- Sub-vertical filtering (meat_shops, farm_direct, vegan_products, confectionery, etc.)
- Cold chain indicator
- Date range filtering
- **Actions:**
  - **Accept** - Moves order from `pending` to `processing` (visible only for pending orders)
  - **Reject** - Cancels order with timestamp (visible for pending/processing orders)
  - **Ready for Delivery** - Moves order from `processing` to `shipped` (visible only for processing orders)

### 2. ProductResource
**Location:** `Filament/Resources/ProductResource.php`

**Features:**
- Dynamic form fields based on `sub_vertical` selection
- Sub-vertical specific attributes:
  - **Meat Shops:** meat_type, cut_type
  - **Farm Direct:** farm_name, certification
  - **Vegan Products:** is_vegan, allergens
  - **Confectionery:** sugar_content, filling_type
  - **Grocery & Delivery:** storage_type
  - **Office Catering:** serving_size
- Cold chain flag
- Shelf life tracking
- Price and weight management
- Active/inactive status

### 3. InventoryResource
**Location:** `Filament/Resources/InventoryResource.php`

**Features:**
- Real-time stock tracking with reserved quantity calculation
- Automatic low stock detection (threshold: 10 units)
- Expiration date tracking with visual indicators:
  - Green: More than 7 days to expiration
  - Yellow: 7 days or less to expiration
  - Red: Expired
- Batch number and location tracking
- **Filters:**
  - Low stock
  - Expiring soon
  - Expired
  - By product
- **Bulk Actions:**
  - Mass quantity update (add/subtract)
  - Reset reservations

### 4. SellerAnalyticsDashboard
**Location:** `Filament/Pages/SellerAnalyticsDashboard.php`

**Metrics Displayed:**
- Total revenue (all time)
- Today's revenue
- Monthly revenue
- Active orders count
- Total products count
- Low stock items alert

**Tables:**
- Recent orders (last 10)
- Low stock products (alert list)
- Top products by order count
- Revenue by sub-vertical

## Navigation Structure

All resources are grouped under **"Supermarket"** navigation group:

```
Supermarket
├── Аналитика продавца (Dashboard)
├── Товары (Products)
├── Заказы Supermarket (Orders)
└── Склад & Инвентарь (Inventory)
```

## Data Models

### Product
- `tenant_id` - Tenant relationship
- `sub_vertical` - Sub-vertical classification
- `name`, `description` - Product details
- `price`, `weight` - Pricing and weight
- `requires_cold_chain` - Cold chain requirement flag
- `shelf_life_days` - Shelf life in days
- `attributes` - JSON field for sub-vertical specific attributes
- `is_active` - Active status

### InventoryItem
- `product_id` - Product relationship
- `quantity` - Total quantity
- `reserved` - Reserved quantity
- `expires_at` - Expiration date
- `batch_number` - Batch identifier
- `location` - Storage location

### InventoryReservation
- `inventory_item_id` - Inventory relationship
- `order_id` - Order relationship
- `quantity` - Reserved quantity
- `reserved_at` - Reservation timestamp
- `expires_at` - Reservation expiry (20 min)
- `status` - active/expired

### SupermarketOrder
- `tenant_id` - Tenant relationship
- `user_id` - User relationship
- `sub_vertical` - Sub-vertical classification
- `status` - Order status
- `total_amount`, `delivery_cost` - Financials
- `delivery_eta`, `delivery_address`, `delivery_slot` - Delivery details
- `cold_chain_required` - Cold chain flag
- `payment_id` - Payment reference
- `correlation_id` - Tracking ID

## Status Flow

```
pending → processing → shipped → delivered
    ↓
cancelled
```

**Actions:**
- **Accept:** pending → processing
- **Reject:** pending/processing → cancelled (with cancelled_at timestamp)
- **Ready for Delivery:** processing → shipped

## Sub-Verticals Supported

1. **meat_shops** - Мясные магазины
2. **farm_direct** - Фермерские продукты
3. **vegan_products** - Веганские продукты
4. **confectionery** - Кондитерские изделия
5. **grocery_and_delivery** - Бакалея и доставка
6. **food** - Еда
7. **office_catering** - Офисный кейтеринг

## Color Coding

### Order Status
- `pending` - Yellow
- `paid` - Green
- `processing` - Blue
- `shipped` - Indigo
- `delivered` - Green
- `cancelled` - Red

### Sub-Vertical
- `meat_shops` - Red
- `farm_direct` - Green
- `vegan_products` - Yellow
- `confectionery` - Blue
- `grocery_and_delivery` - Primary Blue
- `food` - Secondary
- `office_catering` - Purple

### Inventory Status
- Available > 10: Green
- Available ≤ 10: Red
- Expires > 7 days: Green
- Expires ≤ 7 days: Yellow
- Expired: Red

## Permissions & Access

All resources respect tenant isolation through `tenant_id` relationships. The dashboard automatically filters data based on the authenticated user's tenant.

## Future Enhancements

1. **ColdChainResource** - Real-time temperature monitoring integration with IoT sensors
2. **PayoutResource** - Seller payout management
3. **PromotionResource** - Marketing campaigns and promotions
4. **Order Items Relation** - Detailed order item management in SupermarketOrderResource
5. **Product Variants Management** - Full CRUD for product variants
6. **Product Images Management** - Image upload and management
7. **Inventory Reservation History** - Audit trail for reservations

## Usage

### Accessing the Dashboard
Navigate to: Filament Admin → Supermarket → Аналитика продавца

### Managing Products
1. Navigate to: Supermarket → Товары
2. Click "Создать" to add new product
3. Select sub-vertical to see dynamic fields
4. Fill in product details and attributes
5. Save

### Managing Inventory
1. Navigate to: Supermarket → Склад & Инвентарь
2. View stock levels and expiration dates
3. Use filters to find low stock or expiring items
4. Use bulk actions to update quantities
5. Click "Создать" to add new inventory items

### Processing Orders
1. Navigate to: Supermarket → Заказы Supermarket
2. View pending orders
3. Click "Принять" to accept order (moves to processing)
4. Click "Отклонить" to reject order (moves to cancelled)
5. Click "Готов к доставке" when ready (moves to shipped)

## Technical Details

- **Framework:** Filament 3.x
- **Laravel Version:** 11+
- **PHP Version:** 8.3+
- **Styling:** Tailwind CSS
- **Icons:** Heroicons

## Compliance & Security

- All monetary values stored in kopeks (integer)
- Audit logging ready (trait integration available)
- PII protection compliant with 152-FZ
- Tenant isolation enforced at model level
- Action confirmations for destructive operations
