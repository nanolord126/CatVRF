# Warehouse API Documentation

## Base URL
```
/api/v1/warehouses
```

## Authentication
All endpoints require authentication via Bearer token:
```
Authorization: Bearer {token}
```

---

## Warehouse Endpoints

### List Warehouses

**GET** `/api/v1/warehouses`

Retrieve all warehouses for the current tenant.

**Query Parameters:**
- `is_active` (boolean, optional): Filter by active status
- `is_verified` (boolean, optional): Filter by verification status
- `search` (string, optional): Search by name
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
      "owner_id": 1,
      "name": "Main Warehouse",
      "address": "123 Storage St",
      "branch_id": "BR001",
      "type": "central",
      "capacity": 10000,
      "current_stock": 6500,
      "is_active": true,
      "is_verified": true,
      "rating": 4.8,
      "tags": ["pharmaceutical", "cold-chain"],
      "created_at": "2026-04-28T10:00:00Z",
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

### Create Warehouse

**POST** `/api/v1/warehouses`

Create a new warehouse.

**Request Body:**
```json
{
  "name": "Distribution Center",
  "address": "456 Logistics Ave",
  "branch_id": "BR002",
  "type": "regional",
  "capacity": 5000,
  "is_active": true
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "tenant_id": 1,
    "owner_id": 1,
    "name": "Distribution Center",
    "address": "456 Logistics Ave",
    "branch_id": "BR002",
    "type": "regional",
    "capacity": 5000,
    "current_stock": 0,
    "is_active": true,
    "is_verified": false,
    "rating": null,
    "tags": [],
    "created_at": "2026-04-28T10:05:00Z",
    "updated_at": "2026-04-28T10:05:00Z"
  }
}
```

---

### Get Warehouse

**GET** `/api/v1/warehouses/{id}`

Retrieve a specific warehouse by ID.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "tenant_id": 1,
    "owner_id": 1,
    "name": "Main Warehouse",
    "address": "123 Storage St",
    "branch_id": "BR001",
    "type": "central",
    "capacity": 10000,
    "current_stock": 6500,
    "is_active": true,
    "is_verified": true,
    "rating": 4.8,
    "utilization_percentage": 65.0,
    "zones": [],
    "licenses": [],
    "created_at": "2026-04-28T10:00:00Z",
    "updated_at": "2026-04-28T10:00:00Z"
  }
}
```

**Response (404 Not Found):**
```json
{
  "error": "Warehouse not found"
}
```

---

### Update Warehouse

**PUT** `/api/v1/warehouses/{id}`

Update warehouse details.

**Request Body:**
```json
{
  "name": "Main Warehouse - Updated",
  "address": "123 Storage St, Suite 100",
  "capacity": 12000
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "tenant_id": 1,
    "owner_id": 1,
    "name": "Main Warehouse - Updated",
    "address": "123 Storage St, Suite 100",
    "branch_id": "BR001",
    "type": "central",
    "capacity": 12000,
    "current_stock": 6500,
    "is_active": true,
    "is_verified": true,
    "rating": 4.8,
    "created_at": "2026-04-28T10:00:00Z",
    "updated_at": "2026-04-28T10:10:00Z"
  }
}
```

---

### Delete Warehouse

**DELETE** `/api/v1/warehouses/{id}`

Soft delete a warehouse.

**Response (204 No Content)**

---

## Warehouse Zones

### List Zones

**GET** `/api/v1/warehouses/{id}/zones`

Retrieve all zones for a warehouse.

**Query Parameters:**
- `type` (string, optional): Filter by zone type (receiving, storage, picking, packing, shipping, quarantine, returns)
- `is_active` (boolean, optional): Filter by active status

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "name": "Receiving Zone A",
      "type": "receiving",
      "capacity": 1000,
      "current_stock": 450,
      "branch_id": "BR001",
      "is_active": true,
      "utilization_percentage": 45.0,
      "bins_count": 10
    }
  ]
}
```

---

### Create Zone

**POST** `/api/v1/warehouses/{id}/zones`

Create a new zone in a warehouse.

**Request Body:**
```json
{
  "name": "Storage Zone B",
  "type": "storage",
  "capacity": 2000,
  "branch_id": "BR001"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "warehouse_id": 1,
    "name": "Storage Zone B",
    "type": "storage",
    "capacity": 2000,
    "current_stock": 0,
    "branch_id": "BR001",
    "is_active": true,
    "created_at": "2026-04-28T10:15:00Z"
  }
}
```

---

## Warehouse Bins

### List Bins

**GET** `/api/v1/warehouses/{id}/bins`

Retrieve all bins for a warehouse.

**Query Parameters:**
- `zone_id` (integer, optional): Filter by zone
- `is_active` (boolean, optional): Filter by active status
- `with_capacity` (boolean, optional): Show only bins with available capacity

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "zone_id": 1,
      "code": "BIN-A001",
      "name": "Bin A-001",
      "coordinates": "A-1-1",
      "capacity": 100,
      "current_stock": 65,
      "is_active": true,
      "available_capacity": 35,
      "utilization_percentage": 65.0
    }
  ]
}
```

---

## Warehouse Licenses

### List Licenses

**GET** `/api/v1/warehouses/{id}/licenses`

Retrieve all licenses for a warehouse.

**Query Parameters:**
- `license_type` (string, optional): Filter by type (pharmaceutical, narcotic, psychotropic, controlled)
- `status` (string, optional): Filter by status (active, suspended, revoked, expired)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "license_type": "pharmaceutical",
      "license_number": "ЛЦ-12345678",
      "issue_date": "2024-01-01",
      "expiry_date": "2027-01-01",
      "status": "active",
      "issuing_authority": "Ministry of Health",
      "license_scope": "Pharmaceutical storage",
      "restrictions": null,
      "has_temporary_restrictions": false,
      "is_valid": true,
      "days_until_expiry": 248
    }
  ]
}
```

---

### Add License

**POST** `/api/v1/warehouses/{id}/licenses`

Add a new license to a warehouse.

**Request Body:**
```json
{
  "license_type": "pharmaceutical",
  "license_number": "ЛЦ-87654321",
  "issue_date": "2024-02-01",
  "expiry_date": "2027-02-01",
  "issuing_authority": "Ministry of Health",
  "license_scope": "Pharmaceutical storage and distribution"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "warehouse_id": 1,
    "license_type": "pharmaceutical",
    "license_number": "ЛЦ-87654321",
    "issue_date": "2024-02-01",
    "expiry_date": "2027-02-01",
    "status": "active",
    "issuing_authority": "Ministry of Health",
    "license_scope": "Pharmaceutical storage and distribution",
    "created_at": "2026-04-28T10:20:00Z"
  }
}
```

---

## Warehouse Documents

### List Documents

**GET** `/api/v1/warehouses/{id}/documents`

Retrieve all documents for a warehouse.

**Query Parameters:**
- `document_type` (string, optional): Filter by document type
- `status` (string, optional): Filter by status (draft, pending_approval, approved, rejected, archived)
- `start_date` (date, optional): Filter by date range start
- `end_date` (date, optional): Filter by date range end

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "document_number": "DOC-2026-001",
      "document_type": "receipt_act",
      "status": "approved",
      "document_date": "2026-04-28",
      "items": [
        {
          "product_sku": "PROD-001",
          "quantity": 100,
          "unit_price": 150.00
        }
      ],
      "total_amount": 15000.00,
      "currency": "RUB",
      "created_by": 1,
      "approved_by": 2,
      "approved_at": "2026-04-28T10:30:00Z",
      "created_at": "2026-04-28T10:00:00Z"
    }
  ]
}
```

---

## Cold Chain Monitoring

### Get Temperature Readings

**GET** `/api/v1/warehouses/{id}/cold-chain/readings`

Retrieve temperature readings for a warehouse.

**Query Parameters:**
- `zone_id` (integer, optional): Filter by zone
- `sensor_id` (string, optional): Filter by sensor
- `start_date` (datetime, optional): Filter by date range start
- `end_date` (datetime, optional): Filter by date range end

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "warehouse_id": 1,
      "zone_id": 1,
      "temperature": 4.5,
      "humidity": 65.0,
      "sensor_id": "SENSOR-001",
      "recorded_at": "2026-04-28T10:00:00Z",
      "is_within_range": true,
      "min_allowed": 2.0,
      "max_allowed": 8.0
    }
  ]
}
```

---

### Get Temperature Alerts

**GET** `/api/v1/warehouses/{id}/cold-chain/alerts`

Retrieve temperature alerts for a warehouse.

**Query Parameters:**
- `severity` (string, optional): Filter by severity (low, medium, high, critical)
- `resolved` (boolean, optional): Show resolved alerts

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "zone_id": 1,
      "temperature": 9.5,
      "min_allowed": 2.0,
      "max_allowed": 8.0,
      "severity": "medium",
      "resolved_at": null,
      "resolved_by": null,
      "created_at": "2026-04-28T09:45:00Z",
      "duration_minutes": 15
    }
  ]
}
```

---

### Resolve Alert

**POST** `/api/v1/cold-chain/alerts/{id}/resolve`

Resolve a temperature alert.

**Request Body:**
```json
{
  "resolution_notes": "Temperature adjusted, system back to normal"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "resolved_at": "2026-04-28T10:00:00Z",
    "resolved_by": 1,
    "status": "resolved"
  }
}
```

---

## Statistics

### Get Warehouse Statistics

**GET** `/api/v1/warehouses/{id}/statistics`

Retrieve comprehensive statistics for a warehouse.

**Response (200 OK):**
```json
{
  "data": {
    "warehouse_id": 1,
    "utilization_percentage": 65.0,
    "total_zones": 5,
    "total_bins": 50,
    "total_products": 500,
    "total_batches": 1250,
    "active_licenses": 2,
    "expiring_licenses": 0,
    "temperature_violations_24h": 0,
    "stock_movements_24h": 150,
    "inbound_24h": 80,
    "outbound_24h": 70
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
    "name": ["The name field is required."]
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

**422 Unprocessable Entity:**
```json
{
  "error": "Unprocessable Entity",
  "message": "The given data was invalid."
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
