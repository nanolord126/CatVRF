# Inventory Reporting API Documentation

## Overview
This document describes the API endpoints for inventory reporting services in the CatVRF system.

## Base URL
```
/api/v1/inventory/reports
```

## Authentication
All endpoints require authentication via Bearer token:
```
Authorization: Bearer {token}
```

## Endpoints

### 1. Inventory Turnover Report

#### Get Warehouse Turnover Report
```
GET /api/v1/inventory/reports/turnover/warehouse/{warehouseId}
```

**Parameters:**
- `warehouseId` (path, required): Warehouse ID
- `days` (query, optional): Period in days (default: 30)

**Response:**
```json
{
  "warehouse_id": 1,
  "period_days": 30,
  "items": [
    {
      "inventory_item_id": 123,
      "product_name": "Antibiotic X",
      "sku": "ABC-001",
      "turnover_rate": 4.5,
      "days_of_inventory": 81,
      "average_daily_usage": 12.5,
      "total_cost": 15000.00
    }
  ],
  "summary": {
    "total_items": 456,
    "average_turnover_rate": 3.8,
    "total_value": 2450000.00
  }
}
```

#### Export Turnover Report to CSV
```
GET /api/v1/inventory/reports/turnover/warehouse/{warehouseId}/export
```

**Response:** CSV file

---

### 2. ABC-XYZ Analysis

#### Perform ABC-XYZ Analysis
```
GET /api/v1/inventory/reports/abc-xyz/warehouse/{warehouseId}
```

**Parameters:**
- `warehouseId` (path, required): Warehouse ID

**Response:**
```json
{
  "warehouse_id": 1,
  "abc_classification": {
    "A": 15,
    "B": 35,
    "C": 50
  },
  "xyz_classification": {
    "X": 40,
    "Y": 35,
    "Z": 25
  },
  "combined_classification": {
    "AX": 10,
    "AY": 5,
    "AZ": 2,
    "BX": 20,
    "BY": 10,
    "BZ": 5,
    "CX": 15,
    "CY": 5,
    "CZ": 3
  },
  "strategy_recommendations": [
    {
      "class": "AX",
      "strategy": "High priority monitoring",
      "description": "High value, stable demand - maintain optimal stock levels"
    }
  ]
}
```

#### Export ABC-XYZ Analysis to CSV
```
GET /api/v1/inventory/reports/abc-xyz/warehouse/{warehouseId}/export
```

**Response:** CSV file

---

### 3. Expiration Report

#### Generate Expiration Report
```
GET /api/v1/inventory/reports/expiration/warehouse/{warehouseId}
```

**Parameters:**
- `warehouseId` (path, required): Warehouse ID
- `days` (query, optional): Period in days (default: 90)

**Response:**
```json
{
  "warehouse_id": 1,
  "period_days": 90,
  "expired_batches": [
    {
      "batch_id": "uuid-1",
      "batch_number": "BATCH-001",
      "product_name": "Antibiotic X",
      "expiry_date": "2026-03-15",
      "quantity": 50,
      "value": 5000.00
    }
  ],
  "expiring_soon": [
    {
      "batch_id": "uuid-2",
      "batch_number": "BATCH-002",
      "product_name": "Insulin",
      "expiry_date": "2026-05-20",
      "days_until_expiry": 22,
      "quantity": 100,
      "value": 10000.00
    }
  ],
  "write_off_recommendations": [
    {
      "batch_id": "uuid-1",
      "reason": "Expired",
      "action": "Destroy",
      "estimated_value": 5000.00
    }
  ],
  "shelf_life_analysis": {
    "average_shelf_life_days": 180,
    "shortest_shelf_life_days": 30,
    "longest_shelf_life_days": 365
  }
}
```

---

### 4. Audit Trail Report

#### Generate Item Audit Trail
```
GET /api/v1/inventory/reports/audit/item/{itemId}
```

**Parameters:**
- `itemId` (path, required): Inventory item ID
- `days` (query, optional): Period in days (default: 90)

**Response:**
```json
{
  "inventory_item_id": 123,
  "item_name": "Antibiotic X",
  "item_sku": "ABC-001",
  "period_days": 90,
  "total_events": 156,
  "stock_movements_count": 120,
  "audit_logs_count": 36,
  "timeline": [
    {
      "timestamp": "2026-04-28T10:30:00Z",
      "type": "stock_movement",
      "movement_type": "in",
      "quantity": 100,
      "reason": "Purchase",
      "user_id": 456
    }
  ]
}
```

---

### 5. Compliance Report

#### Generate Compliance Report
```
POST /api/v1/inventory/reports/compliance
```

**Request Body:**
```json
{
  "tenant_id": 1,
  "warehouse_id": 1,
  "report_period": "2026-04"
}
```

**Response:**
```json
{
  "tenant_id": 1,
  "warehouse_id": 1,
  "report_period": "2026-04",
  "generated_at": "2026-04-28T10:00:00Z",
  "fz152_compliance": {
    "law": "152-ФЗ",
    "description": "Federal Law on Personal Data",
    "is_compliant": true,
    "compliance_percentage": 100,
    "issues": []
  },
  "fz323_compliance": {
    "law": "ФЗ-323",
    "description": "Federal Law on Healthcare",
    "is_compliant": true,
    "compliance_percentage": 95,
    "issues": []
  },
  "fz61_compliance": {
    "law": "ФЗ-61",
    "description": "Federal Law on Medicines Circulation",
    "is_compliant": false,
    "compliance_percentage": 85,
    "issues": [
      {
        "severity": "critical",
        "description": "Expired batches not recalled",
        "count": 2,
        "recommendation": "Immediately recall expired batches"
      }
    ]
  },
  "audit_trail_integrity": {
    "category": "Audit Trail Integrity",
    "is_compliant": true,
    "compliance_percentage": 100,
    "issues": []
  }
}
```

---

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
  "error": "Bad Request",
  "message": "Invalid parameter value",
  "code": "INVALID_PARAMETER"
}
```

### 401 Unauthorized
```json
{
  "error": "Unauthorized",
  "message": "Authentication required",
  "code": "UNAUTHORIZED"
}
```

### 403 Forbidden
```json
{
  "error": "Forbidden",
  "message": "Insufficient permissions",
  "code": "FORBIDDEN"
}
```

### 404 Not Found
```json
{
  "error": "Not Found",
  "message": "Resource not found",
  "code": "NOT_FOUND"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal Server Error",
  "message": "An unexpected error occurred",
  "code": "INTERNAL_ERROR"
}
```

---

## Rate Limiting
- Standard rate limit: 100 requests per minute per user
- Export endpoints: 10 requests per minute per user

---

## Versioning
API version: v1

---

## Changelog

### v1.0.0 (2026-04-28)
- Initial release
- Added turnover report endpoints
- Added ABC-XYZ analysis endpoints
- Added expiration report endpoints
- Added audit trail report endpoints
- Added compliance report endpoints
