# Supermarket Vue Components - Setup Guide

## Overview
Production-ready Vue 3 components for the Supermarket vertical with TypeScript support, following the existing project patterns.

## Components Created

### 1. Product Components

#### ProductCard.vue
**Location:** `frontend/src/components/business/supermarket/ProductCard.vue`

**Features:**
- Product image with aspect ratio
- Dynamic badges (cold chain, sub-vertical, active status)
- Price display with currency formatting
- Weight and shelf life indicators
- Sub-vertical specific attributes display
- View details and edit actions

**Props:**
```typescript
interface Product {
  id: string
  name: string
  description?: string
  price: number
  weight?: number
  requires_cold_chain: boolean
  shelf_life_days?: number
  sub_vertical: string
  attributes?: Record<string, any>
  is_active: boolean
  image?: string
}
```

**Events:**
- `viewDetails` - Emitted when user clicks "Подробнее"
- `editProduct` - Emitted when user clicks "Редактировать"

#### ProductList.vue
**Location:** `frontend/src/components/business/supermarket/ProductList.vue`

**Features:**
- Search by name and description
- Filter by sub-vertical, status, cold chain requirement
- Pagination support
- Empty state with reset filters button
- Grid layout responsive design

**Filters:**
- Search query
- Sub-vertical dropdown
- Status (active/inactive)
- Cold chain (requires/doesn't require)

#### ProductForm.vue
**Location:** `frontend/src/components/business/supermarket/ProductForm.vue`

**Features:**
- Dynamic form based on sub-vertical selection
- Sub-vertical specific fields:
  - **Meat Shops:** meat_type, cut_type
  - **Farm Direct:** farm_name, certification
  - **Vegan Products:** is_vegan checkbox, allergens
  - **Confectionery:** sugar_content, filling_type
  - **Grocery & Delivery:** storage_type
  - **Office Catering:** serving_size
- Validation and required fields
- Loading state during submission

### 2. Order Components

#### OrderCard.vue
**Location:** `frontend/src/components/business/supermarket/OrderCard.vue`

**Features:**
- Order UUID and status badges
- Total amount and delivery cost display
- Sub-vertical badge with color coding
- Cold chain indicator with snowflake icon
- Delivery information (address, slot, ETA)
- Conditional action buttons based on status:
  - **Accept** - Visible for pending orders
  - **Reject** - Visible for pending/processing orders
  - **Ready for Delivery** - Visible for processing orders

**Status Flow:**
```
pending → processing → shipped → delivered
    ↓
cancelled
```

#### OrderList.vue
**Location:** `frontend/src/components/business/supermarket/OrderList.vue`

**Features:**
- Search by order UUID
- Filter by status, sub-vertical, cold chain
- Stats cards (total, pending, processing, shipped)
- Pagination support
- Empty state with reset filters button
- Responsive grid layout

### 3. Inventory Components

#### InventoryCard.vue
**Location:** `frontend/src/components/business/supermarket/InventoryCard.vue`

**Features:**
- Product name and SKU display
- Available quantity with color coding (red if ≤10)
- Visual quantity bars (total vs reserved)
- Status badges:
  - Low stock alert (≤10 available)
  - Expiring soon (≤7 days)
  - Expired
- Details: batch number, location, expiration date
- Edit and adjust quantity actions

#### InventoryList.vue
**Location:** `frontend/src/components/business/supermarket/InventoryList.vue`

**Features:**
- Search by product name or SKU
- Filter by status (low stock, expiring soon, expired)
- Filter by location and batch number
- Stats cards (total, low stock, expiring soon, expired)
- Bulk selection with checkboxes
- Bulk actions:
  - Update quantity
  - Reset reservations
- Pagination support
- Empty state with reset filters button

### 4. Analytics Dashboard

#### SellerAnalyticsDashboard.vue
**Location:** `frontend/src/components/business/supermarket/SellerAnalyticsDashboard.vue`

**Features:**
- Time range selector (today, week, month, quarter, year)
- Key metrics cards:
  - Total revenue with trend
  - Today's revenue with trend
  - Monthly revenue with trend
  - Active orders count
  - Total products count
  - Low stock items alert
- Revenue chart (bar chart by day)
- Revenue by sub-vertical (progress bars)
- Recent orders table
- Low stock alert list
- Top products ranking
- Expiring soon items list

**Props:**
```typescript
interface Metrics {
  total_revenue: number
  today_revenue: number
  month_revenue: number
  active_orders: number
  total_products: number
  low_stock_items: number
  revenue_trend: number
  today_trend: number
  month_trend: number
}
```

### 5. API Composable

#### useSupermarketApi.ts
**Location:** `frontend/src/composables/useSupermarketApi.ts`

**Features:**
- Centralized API calls for all Supermarket operations
- Loading and error state management
- TypeScript interfaces for all data structures
- Reactive state for products, orders, inventory, metrics

**Methods:**

**Products:**
- `fetchProducts(params)` - List products with filters
- `fetchProduct(id)` - Get single product
- `createProduct(data)` - Create new product
- `updateProduct(id, data)` - Update product
- `deleteProduct(id)` - Delete product

**Orders:**
- `fetchOrders(params)` - List orders with filters
- `fetchOrder(id)` - Get single order
- `acceptOrder(id)` - Accept order (pending → processing)
- `rejectOrder(id)` - Reject order (→ cancelled)
- `readyForDelivery(id)` - Mark as ready (processing → shipped)

**Inventory:**
- `fetchInventory(params)` - List inventory items
- `updateInventoryQuantity(id, change)` - Update single item quantity
- `bulkUpdateInventoryQuantity(ids, change)` - Bulk quantity update
- `resetInventoryReservations(ids)` - Reset reservations to 0

**Analytics:**
- `fetchMetrics(timeRange)` - Get key metrics
- `fetchRecentOrders(limit)` - Get recent orders
- `fetchLowStockProducts(limit)` - Get low stock alerts
- `fetchTopProducts(limit)` - Get top products by orders
- `fetchRevenueBySubVertical()` - Get revenue breakdown

## Usage Examples

### Using Product Components

```vue
<script setup lang="ts">
import ProductList from '@/components/business/supermarket/ProductList.vue'
import ProductForm from '@/components/business/supermarket/ProductForm.vue'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()
const showForm = ref(false)
const selectedProduct = ref(null)

onMounted(() => {
  api.fetchProducts()
})

const handleViewDetails = (product) => {
  selectedProduct.value = product
}

const handleEditProduct = (product) => {
  selectedProduct.value = product
  showForm.value = true
}

const handleSubmit = async (form) => {
  if (selectedProduct.value) {
    await api.updateProduct(selectedProduct.value.id, form)
  } else {
    await api.createProduct(form)
  }
  showForm.value = false
}
</script>

<template>
  <div>
    <ProductList 
      :products="api.products"
      @view-details="handleViewDetails"
      @edit-product="handleEditProduct"
      @create-product="showForm = true"
    />
    <ProductForm 
      v-if="showForm"
      :product="selectedProduct"
      :is-edit="!!selectedProduct"
      @submit="handleSubmit"
      @cancel="showForm = false"
    />
  </div>
</template>
```

### Using Order Components

```vue
<script setup lang="ts">
import OrderList from '@/components/business/supermarket/OrderList.vue'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()

onMounted(() => {
  api.fetchOrders()
})

const handleAcceptOrder = async (order) => {
  await api.acceptOrder(order.id)
}

const handleRejectOrder = async (order) => {
  await api.rejectOrder(order.id)
}

const handleReadyForDelivery = async (order) => {
  await api.readyForDelivery(order.id)
}
</script>

<template>
  <OrderList 
    :orders="api.orders"
    @accept-order="handleAcceptOrder"
    @reject-order="handleRejectOrder"
    @ready-for-delivery="handleReadyForDelivery"
  />
</template>
```

### Using Analytics Dashboard

```vue
<script setup lang="ts">
import SellerAnalyticsDashboard from '@/components/business/supermarket/SellerAnalyticsDashboard.vue'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()

const loadDashboardData = async () => {
  await Promise.all([
    api.fetchMetrics(),
    api.fetchRecentOrders(),
    api.fetchLowStockProducts(),
    api.fetchTopProducts(),
    api.fetchRevenueBySubVertical()
  ])
}

onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <SellerAnalyticsDashboard
    :metrics="api.metrics"
    :recent-orders="api.recentOrders"
    :low-stock-products="api.lowStockProducts"
    :top-products="api.topProducts"
    :revenue-by-sub-vertical="api.revenueBySubVertical"
  />
</template>
```

## Styling

All components use:
- Tailwind CSS for styling
- Lucide Vue Next for icons
- Responsive design with mobile-first approach
- Color-coded badges and indicators
- Hover states and transitions
- Loading states and error handling

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
- `grocery_and_delivery` - Indigo
- `food` - Purple
- `office_catering` - Pink

### Inventory Status
- Available > 10: Green
- Available ≤ 10: Red
- Expires > 7 days: Green
- Expires ≤ 7 days: Yellow
- Expired: Red

## API Endpoints

The composable expects these API endpoints:

```
GET    /api/supermarket/products
GET    /api/supermarket/products/:id
POST   /api/supermarket/products
PUT    /api/supermarket/products/:id
DELETE /api/supermarket/products/:id

GET    /api/supermarket/orders
GET    /api/supermarket/orders/:id
POST   /api/supermarket/orders/:id/accept
POST   /api/supermarket/orders/:id/reject
POST   /api/supermarket/orders/:id/ready-for-delivery

GET    /api/supermarket/inventory
PATCH  /api/supermarket/inventory/:id/quantity
POST   /api/supermarket/inventory/bulk-update-quantity
POST   /api/supermarket/inventory/reset-reservations

GET    /api/supermarket/analytics/metrics
GET    /api/supermarket/analytics/recent-orders
GET    /api/supermarket/analytics/low-stock
GET    /api/supermarket/analytics/top-products
GET    /api/supermarket/analytics/revenue-by-sub-vertical
```

## TypeScript Support

All components include full TypeScript interfaces for:
- Props
- Emits
- Data structures
- API responses

## Dependencies

Required packages:
```json
{
  "vue": "^3.4.0",
  "lucide-vue-next": "^0.300.0",
  "typescript": "^5.0.0"
}
```

## Future Enhancements

1. **Product Variants** - Add variant management UI
2. **Product Images** - Add image upload and gallery
3. **Order Details Modal** - Detailed order view with items
4. **Inventory History** - Audit trail for inventory changes
5. **Real-time Updates** - WebSocket integration for live order status
6. **Export Functionality** - Export analytics data to CSV/Excel
7. **Advanced Filtering** - Date range, custom filters
8. **Print Labels** - Generate shipping/inventory labels
