# Inventory API Documentation

## Base URL
```
/api/v1/inventory
```

## Authentication
All endpoints require authentication via Bearer token:
```
Authorization: Bearer {token}
```

---

## Inventory Items

### List Inventory Items

**GET** `/api/v1/inventory/items`

Retrieve all inventory items for the current tenant.

**Query Parameters:**
- `sku` (string, optional): Filter by SKU
- `name` (string, optional): Filter by name
- `low_stock` (boolean, optional): Show only items below minimum threshold
- `expiring_soon` (boolean, optional): Show only items expiring soon
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 20)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "tenant_id": 1,
      "business_group_id": 1,
      "product_id": 1,
      "sku": "PROD-001",
      "name": "Antibiotic Amoxicillin 500mg",
      "current_stock": 450,
      "hold_stock": 50,
      "available_stock": 400,
      "min_stock_threshold": 100,
      "max_stock_threshold": 1000,
      "version": 5,
      "is_low_stock": false,
      "last_checked_at": "2026-04-28T10:00:00Z",
      "created_at": "2026-04-01T10:00:00Z",
      "updated_at": "2026-04-28T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

---

### Get Inventory Item

**GET** `/api/v1/inventory/items/{id}`

Retrieve a specific inventory item by ID.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "tenant_id": 1,
    "business_group_id": 1,
    "product_id": 1,
    "sku": "PROD-001",
    "name": "Antibiotic Amoxicillin 500mg",
    "current_stock": 450,
    "hold_stock": 50,
    "available_stock": 400,
    "min_stock_threshold": 100,
    "max_stock_threshold": 1000,
    "version": 5,
    "stock_movements": [],
    "batches": [],
    "created_at": "2026-04-01T10:00:00Z",
    "updated_at": "2026-04-28T10:00:00Z"
  }
}
```

---

### Update Stock

**POST** `/api/v1/inventory/items/{id}/stock`

Update stock quantity for an inventory item with optimistic locking.

**Request Body:**
```json
{
  "quantity": 500,
  "expected_version": 5,
  "reason": "Stock receipt from supplier",
  "meta": {
    "supplier_id": 1,
    "document_number": "DOC-2026-001"
  }
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "current_stock": 500,
    "hold_stock": 50,
    "version": 6,
    "updated_at": "2026-04-28T10:15:00Z"
  }
}
```

**Response (409 Conflict):**
```json
{
  "error": "OptimisticLockException",
  "message": "Resource has been modified by another user",
  "expected_version": 5,
  "actual_version": 6
}
```

---

### Reserve Stock

**POST** `/api/v1/inventory/items/{id}/reserve`

Reserve stock for an order.

**Request Body:**
```json
{
  "quantity": 50,
  "order_id": "ORD-12345",
  "order_type": "b2c",
  "reason": "Order reservation"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "item_id": 1,
    "reserved_quantity": 50,
    "hold_stock": 100,
    "available_stock": 350,
    "reservation_id": "RES-001"
  }
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "error": "InsufficientStock",
  "message": "Not enough stock available for reservation",
  "available": 350,
  "requested": 500
}
```

---

### Release Reserved Stock

**POST** `/api/v1/inventory/items/{id}/release`

Release previously reserved stock.

**Request Body:**
```json
{
  "quantity": 50,
  "reservation_id": "RES-001",
  "reason": "Order cancelled"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "item_id": 1,
    "released_quantity": 50,
    "hold_stock": 50,
    "available_stock": 400
  }
}
```

---

## Batches

### List Batches

**GET** `/api/v1/inventory/batches`

Retrieve all batches for the current tenant.

**Query Parameters:**
- `product_id` (integer, optional): Filter by product
- `warehouse_id` (integer, optional): Filter by warehouse
- `status` (string, optional): Filter by status (active, expiring_soon, expired, depleted, quarantine)
- `expiring_soon_days` (integer, optional): Show batches expiring within X days

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "product_id": 1,
      "product_sku": "PROD-001",
      "batch_number": "BATCH-2026-001",
      "lot_number": "LOT-001",
      "manufacture_date": "2025-01-15",
      "expiry_date": "2027-01-15",
      "initial_quantity": 500,
      "current_quantity": 200,
      "purchase_price": 150.00,
      "warehouse_id": 1,
      "zone_id": 1,
      "bin_id": 1,
      "supplier_id": 1,
      "status": "active",
      "days_until_expiry": 262,
      "is_expired": false,
      "remaining_percentage": 40.0,
      "created_at": "2026-01-15T10:00:00Z"
    }
  ]
}
```

---

### Get Batch

**GET** `/api/v1/inventory/batches/{id}`

Retrieve a specific batch by ID.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "product_id": 1,
    "product_sku": "PROD-001",
    "batch_number": "BATCH-2026-001",
    "lot_number": "LOT-001",
    "manufacture_date": "2025-01-15",
    "expiry_date": "2027-01-15",
    "initial_quantity": 500,
    "current_quantity": 200,
    "purchase_price": 150.00,
    "warehouse_id": 1,
    "zone_id": 1,
    "bin_id": 1,
    "supplier_id": 1,
    "status": "active",
    "product": {
      "id": 1,
      "sku": "PROD-001",
      "name": "Antibiotic Amoxicillin 500mg"
    },
    "warehouse": {
      "id": 1,
      "name": "Main Warehouse"
    },
    "zone": {
      "id": 1,
      "name": "Storage Zone A"
    },
    "bin": {
      "id": 1,
      "code": "BIN-A001"
    }
  }
}
```

---

## Stock Transfers

### List Transfers

**GET** `/api/v1/inventory/transfers`

Retrieve all stock transfers.

**Query Parameters:**
- `from_warehouse_id` (integer, optional): Filter by source warehouse
- `to_warehouse_id` (integer, optional): Filter by destination warehouse
- `status` (string, optional): Filter by status (pending, in_transit, completed, cancelled)
- `start_date` (date, optional): Filter by date range start
- `end_date` (date, optional): Filter by date range end

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440002",
      "from_warehouse_id": 1,
      "to_warehouse_id": 2,
      "items": [
        {
          "item_id": 1,
          "sku": "PROD-001",
          "quantity": 100
        }
      ],
      "total_quantity": 100,
      "status": "in_transit",
      "reason": "Stock rebalancing",
      "created_by": 1,
      "created_at": "2026-04-28T09:00:00Z",
      "expected_delivery_date": "2026-04-29"
    }
  ]
}
```

---

### Create Transfer

**POST** `/api/v1/inventory/transfers`

Create a new stock transfer order.

**Request Body:**
```json
{
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "items": [
    {
      "item_id": 1,
      "quantity": 100
    },
    {
      "item_id": 2,
      "quantity": 50
    }
  ],
  "reason": "Stock rebalancing",
  "expected_delivery_date": "2026-04-29"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440002",
    "from_warehouse_id": 1,
    "to_warehouse_id": 2,
    "total_quantity": 150,
    "status": "pending",
    "reason": "Stock rebalancing",
    "created_at": "2026-04-28T10:30:00Z"
  }
}
```

---

### Execute Transfer

**POST** `/api/v1/inventory/transfers/{id}/execute`

Execute a pending transfer order.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "status": "completed",
    "executed_at": "2026-04-28T10:45:00Z",
    "stock_movements": [
      {
        "id": 1,
        "from_warehouse_id": 1,
        "to_warehouse_id": 2,
        "item_id": 1,
        "quantity": 100
      }
    ]
  }
}
```

---

## FIFO Allocation

### Allocate from FIFO

**POST** `/api/v1/inventory/fifo/allocate`

Allocate stock using FIFO logic based on expiry dates.

**Request Body:**
```json
{
  "item_id": 1,
  "quantity": 50,
  "context": "sale",
  "order_id": "ORD-12345",
  "order_type": "b2c"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "item_id": 1,
    "quantity_allocated": 50,
    "batches": [
      {
        "batch_id": 1,
        "batch_number": "BATCH-2026-001",
        "quantity": 30,
        "expiry_date": "2027-01-15",
        "days_until_expiry": 262
      },
      {
        "batch_id": 2,
        "batch_number": "BATCH-2026-002",
        "quantity": 20,
        "expiry_date": "2027-02-20",
        "days_until_expiry": 298
      }
    ],
    "context": "sale"
  }
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "error": "InsufficientStockWithExpiry",
  "message": "Insufficient stock with valid expiry dates",
  "required": 50,
  "available": 30
}
```

---

## Stock Movements

### List Movements

**GET** `/api/v1/inventory/movements`

Retrieve stock movements.

**Query Parameters:**
- `warehouse_id` (integer, optional): Filter by warehouse
- `item_id` (integer, optional): Filter by inventory item
- `movement_type` (string, optional): Filter by type (receipt, transfer, picking, packing, shipment, return, adjustment)
- `start_date` (date, optional): Filter by date range start
- `end_date` (date, optional): Filter by date range end

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "from_zone_id": 1,
      "to_zone_id": 2,
      "inventory_item_id": 1,
      "product_sku": "PROD-001",
      "quantity": 100,
      "movement_type": "transfer",
      "order_id": "TRF-001",
      "reason": "Zone transfer",
      "created_at": "2026-04-28T10:00:00Z"
    }
  ]
}
```

---

## Reports

### Low Stock Report

**GET** `/api/v1/inventory/reports/low-stock`

Generate low stock report.

**Query Parameters:**
- `threshold` (integer, optional): Minimum stock threshold (default: 10)

**Response (200 OK):**
```json
{
  "data": {
    "generated_at": "2026-04-28T11:00:00Z",
    "total_items": 5,
    "items": [
      {
        "item_id": 1,
        "sku": "PROD-001",
        "name": "Antibiotic Amoxicillin 500mg",
        "current_stock": 50,
        "min_threshold": 100,
        "shortage": 50,
        "urgency": "high"
      }
    ]
  }
}
```

---

### Expiry Report

**GET** `/api/v1/inventory/reports/expiry`

Generate expiry report for batches.

**Query Parameters:**
- `days` (integer, optional): Days ahead to check (default: 30)

**Response (200 OK):**
```json
{
  "data": {
    "generated_at": "2026-04-28T11:00:00Z",
    "days_ahead": 30,
    "total_batches": 3,
    "batches": [
      {
        "batch_id": 1,
        "batch_number": "BATCH-2026-001",
        "product_sku": "PROD-001",
        "expiry_date": "2026-05-15",
        "days_until_expiry": 17,
        "quantity": 200,
        "urgency": "medium"
      }
    ]
  }
}
```

---

## Error Responses

All endpoints may return error responses:

**400 Bad Request:**
```json
{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "quantity": ["The quantity must be greater than 0."]
  }
}
```

**401 Unauthorized:**
```json
{
  "error": "Unauthorized",
  "message": "Authentication required"
}
```

**403 Forbidden:**
```json
{
  "error": "Forbidden",
  "message": "You do not have permission to perform this action"
}
```

**404 Not Found:**
```json
{
  "error": "Not Found",
  "message": "Resource not found"
}
```

**409 Conflict:**
```json
{
  "error": "OptimisticLockException",
  "message": "Resource has been modified by another user",
  "expected_version": 5,
  "actual_version": 6
}
```

**422 Unprocessable Entity:**
```json
{
  "error": "InsufficientStock",
  "message": "Not enough stock available",
  "available": 100,
  "requested": 150
}
```

**500 Internal Server Error:**
```json
{
  "error": "Internal Server Error",
  "message": "An unexpected error occurred"
}
```

---

**Version**: 2026.04.28  
**Author**: CatVRF Team
