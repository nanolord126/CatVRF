# Barcode Scanner API Documentation

**Version:** 1.0  
**Date:** 2026-04-28  
**Module:** WMS (Warehouse Management System)

## Overview

The Barcode Scanner API provides endpoints for barcode scanning operations integrated with stock adjustments in the CatVRF WMS system. It supports real-time inventory tracking through WebSocket broadcasts and integrates with the existing stock movement infrastructure.

## Base URL

```
/api/wms/barcode
```

## Authentication

All endpoints require authentication via Sanctum tokens. Include the `Authorization` header:

```
Authorization: Bearer {token}
```

## WebSocket Channel

**Channel:** `scanner.{tenantId}.{warehouseId}`

**Authentication:** Users must belong to the tenant and have warehouse access or be admins.

**Event:** `barcode.scan.updated`

### WebSocket Event Payload

```json
{
  "event": "barcode_scan_updated",
  "tenant_id": 1,
  "warehouse_id": 1,
  "barcode": "1234567890123",
  "item_id": 123,
  "item_name": "Product Name",
  "sku": "SKU-001",
  "previous_stock": 100,
  "new_stock": 95,
  "adjustment_type": "out",
  "quantity": 5,
  "movement_id": 456,
  "user_id": 1,
  "user_name": "John Doe",
  "correlation_id": "uuid-here",
  "timestamp": "2026-04-28T12:00:00Z"
}
```

## Endpoints

### 1. Lookup Barcode

**Endpoint:** `GET /api/wms/barcode/lookup/{barcode}`

**Description:** Lookup an inventory item by barcode.

**Query Parameters:**
- `warehouse_id` (optional, integer): Warehouse ID. Defaults to user's warehouse or 1.

**Response (200 OK):**

```json
{
  "found": true,
  "barcode": "1234567890123",
  "item_id": 123,
  "sku": "SKU-001",
  "name": "Product Name",
  "current_stock": 100,
  "unit_cost": 10.50
}
```

**Response (404 Not Found):**

```json
{
  "found": false,
  "barcode": "1234567890123",
  "message": "Item not found"
}
```

### 2. Validate Barcode

**Endpoint:** `POST /api/wms/barcode/validate`

**Description:** Validate barcode format and structure.

**Request Body:**

```json
{
  "barcode": "1234567890123"
}
```

**Response (200 OK):**

```json
{
  "original": "1234567890123",
  "normalized": "1234567890123",
  "valid": true,
  "type": "EAN13"
}
```

### 3. Validate ChestnyZnak Code

**Endpoint:** `POST /api/wms/barcode/chestny-znak/validate`

**Description:** Validate ChestnyZnak (Честный ЗНАК) marking code for Russian market compliance (ФЗ-61).

**Request Body:**

```json
{
  "marking_code": "010467001234567821ABCDE12345678"
}
```

**Response (200 OK):**

```json
{
  "valid": true,
  "marking_code": "010467001234567821ABCDE12345678",
  "parsed": {
    "gtin": "0104670012345678",
    "serial": "21ABCDE12345678",
    "verification_code": "1234"
  }
}
```

### 4. Lookup Batch

**Endpoint:** `POST /api/wms/barcode/batch/lookup`

**Description:** Lookup inventory batch by batch number or serial number.

**Request Body:**

```json
{
  "code": "BATCH-001",
  "warehouse_id": 1
}
```

**Response (200 OK):**

```json
{
  "found": true,
  "code": "BATCH-001",
  "batch_id": "uuid-here",
  "batch_number": "BATCH-001",
  "expiry_date": "2026-12-31",
  "current_quantity": 50,
  "status": "available"
}
```

### 5. Bulk Lookup

**Endpoint:** `POST /api/wms/barcode/bulk-lookup`

**Description:** Lookup multiple barcodes in a single request.

**Request Body:**

```json
{
  "barcodes": ["1234567890123", "9876543210987"],
  "warehouse_id": 1
}
```

**Response (200 OK):**

```json
{
  "1234567890123": {
    "found": true,
    "item_id": 123,
    "sku": "SKU-001",
    "name": "Product Name",
    "current_stock": 100
  },
  "9876543210987": {
    "found": false,
    "message": "Item not found"
  }
}
```

### 6. Scan and Adjust Stock

**Endpoint:** `POST /api/wms/barcode/scan/adjust`

**Description:** Process barcode scan and automatically adjust stock levels. Requires `create stock_movement` permission.

**Request Body:**

```json
{
  "barcode": "1234567890123",
  "warehouse_id": 1,
  "quantity": 5,
  "adjustment_type": "out",
  "reason": "Stock take adjustment"
}
```

**Parameters:**
- `barcode` (required, string): Scanned barcode
- `warehouse_id` (required, integer): Warehouse ID
- `quantity` (required, integer, min: 1): Quantity to adjust
- `adjustment_type` (required, enum: `in`, `out`, `adjust`): Type of adjustment
  - `in`: Add stock to inventory
  - `out`: Remove stock from inventory
  - `adjust`: Set stock to exact quantity
- `reason` (required, string, max: 255): Reason for adjustment

**Response (201 Created):**

```json
{
  "success": true,
  "movement_id": 456,
  "item_id": 123,
  "barcode": "1234567890123",
  "adjustment_type": "out",
  "quantity": 5,
  "previous_stock": 100,
  "new_stock": 95
}
```

**Response (404 Not Found):**

```json
{
  "success": false,
  "barcode": "1234567890123",
  "message": "Item not found for barcode"
}
```

**Response (400 Bad Request):**

```json
{
  "success": false,
  "barcode": "1234567890123",
  "message": "Insufficient stock",
  "available": 3,
  "requested": 5
}
```

**WebSocket Event:** This endpoint broadcasts a `barcode.scan.updated` event to the `scanner.{tenantId}.{warehouseId}` channel.

### 7. Bulk Scan and Adjust

**Endpoint:** `POST /api/wms/barcode/scan/bulk-adjust`

**Description:** Process multiple barcode scans and adjust stock levels in bulk. Requires `create stock_movement` permission.

**Request Body:**

```json
{
  "scans": [
    {
      "barcode": "1234567890123",
      "warehouse_id": 1,
      "quantity": 5,
      "adjustment_type": "out",
      "reason": "Stock take"
    },
    {
      "barcode": "9876543210987",
      "warehouse_id": 1,
      "quantity": 10,
      "adjustment_type": "in",
      "reason": "Receipt"
    }
  ]
}
```

**Response (201 Created):**

```json
{
  "results": [
    {
      "success": true,
      "movement_id": 456,
      "item_id": 123,
      "barcode": "1234567890123",
      "new_stock": 95
    },
    {
      "success": true,
      "movement_id": 457,
      "item_id": 124,
      "barcode": "9876543210987",
      "new_stock": 110
    }
  ],
  "total": 2,
  "successful": 2,
  "failed": 0
}
```

### 8. Get Scanner History

**Endpoint:** `GET /api/wms/barcode/scan/history`

**Description:** Retrieve history of barcode scan adjustments.

**Query Parameters:**
- `warehouse_id` (optional, integer): Filter by warehouse
- `from_date` (optional, date): Filter by start date
- `to_date` (optional, date): Filter by end date
- `per_page` (optional, integer): Items per page (default: 50)

**Response (200 OK):**

```json
{
  "data": [
    {
      "id": 456,
      "inventory_item_id": 123,
      "type": "out",
      "quantity": -5,
      "reason": "Stock take adjustment",
      "source_type": "barcode_scanner",
      "created_at": "2026-04-28T12:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 50,
  "total": 100
}
```

### 9. Pre-Flight Scan Validation

**Endpoint:** `POST /api/wms/barcode/scan/preflight`

**Description:** Validate barcode and check if scan operation can proceed before executing the adjustment.

**Request Body:**

```json
{
  "barcode": "1234567890123",
  "warehouse_id": 1,
  "operation": "out",
  "quantity": 5
}
```

**Parameters:**
- `barcode` (required, string): Barcode to validate
- `warehouse_id` (required, integer): Warehouse ID
- `operation` (required, enum: `in`, `out`, `adjust`): Operation type
- `quantity` (required, integer, min: 1): Quantity to check

**Response (200 OK):**

```json
{
  "valid": true,
  "item": {
    "found": true,
    "item_id": 123,
    "sku": "SKU-001",
    "name": "Product Name",
    "current_stock": 100
  },
  "operation": "out",
  "quantity": 5,
  "can_proceed": true
}
```

**Response (400 Bad Request):**

```json
{
  "valid": false,
  "message": "Insufficient stock",
  "available": 3,
  "requested": 5
}
```

## Error Responses

All endpoints may return standard error responses:

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated"
}
```

**403 Forbidden:**
```json
{
  "message": "This action is unauthorized"
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "barcode": ["The barcode field is required"],
    "quantity": ["The quantity must be at least 1"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "message": "Internal server error"
}
```

## Audit Logging

All stock adjustment operations are logged via the audit system:
- Action: `barcode_scan_adjustment` or `bulk_barcode_scan_adjustment`
- Entity Type: `StockMovement`
- Context includes: barcode, adjustment type, quantity, stock changes
- User ID and Tenant ID are automatically captured

## Rate Limiting

All endpoints are protected by the `wms` throttle middleware. Default rate limits are configured in the application.

## Security Considerations

1. **Tenant Isolation:** All operations are scoped to the user's tenant
2. **Warehouse Access:** Users must have warehouse access or admin privileges
3. **Permission Gates:** Stock adjustment endpoints require `create stock_movement` permission
4. **Transaction Safety:** All adjustments are wrapped in database transactions
5. **Audit Trail:** Complete audit logging for compliance (152-ФЗ, ФЗ-323)

## Supported Barcode Types

- **EAN-13:** 13-digit European Article Number
- **EAN-8:** 8-digit European Article Number
- **UPC-A:** 12-digit Universal Product Code
- **Code-128:** Alphanumeric barcode format
- **ChestnyZnak:** Russian marking codes (ФЗ-61 compliance)

## Integration Examples

### JavaScript/TypeScript Example

```typescript
// Scan and adjust stock
const scanAndAdjust = async (barcode: string, quantity: number) => {
  const response = await fetch('/api/wms/barcode/scan/adjust', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      barcode,
      warehouse_id: 1,
      quantity,
      adjustment_type: 'out',
      reason: 'Stock take'
    })
  });
  
  return response.json();
};

// Listen for real-time updates
const scannerChannel = Echo.private(`scanner.${tenantId}.${warehouseId}`);
scannerChannel.listen('barcode.scan.updated', (event) => {
  console.log('Stock updated:', event);
  updateUI(event);
});
```

### cURL Example

```bash
# Scan and adjust
curl -X POST http://localhost:8000/api/wms/barcode/scan/adjust \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "barcode": "1234567890123",
    "warehouse_id": 1,
    "quantity": 5,
    "adjustment_type": "out",
    "reason": "Stock take"
  }'
```

## Testing

Use the provided test endpoints to verify integration:

1. **Pre-flight validation** before scanning
2. **Lookup** to verify barcode exists
3. **Scan and adjust** with small quantities first
4. **Check history** to verify movements were created
5. **Monitor WebSocket** for real-time updates

## Support

For issues or questions regarding the Barcode Scanner API, contact the WMS development team or refer to the main CatVRF documentation.
