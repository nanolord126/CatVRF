# B2B Certificate/Document Management System - Setup Guide

## Overview

Complete B2B certificate and document management system with mandatory supply chain tracking for the Supermarket vertical. Enforces maximum chain length from manufacturer to end buyer and prevents unauthorized reselling.

## Features

### Document Management
- Upload and manage certificates (PDF, JPEG, TIFF formats only)
- Certificate validity period tracking (FROM-TO dates)
- Automatic expiry checking and status updates
- Document templates support
- Full audit trail with DocumentHistory

### Batch Tracking
- Batch number and barcode tracking
- Batch weight management
- Delivered quantity tracking
- Remaining quantity calculation
- Automatic certificate closing when batch fully sold

### Supply Chain Enforcement
- **Maximum Chain Length:**
  - Chain 1: Manufacturer → Wholesaler → Wholesaler → B2B Buyer (4 hops max)
  - Chain 2: Manufacturer → Trading House → Wholesaler → Wholesaler → Consumer (5 hops max)
- Mandatory primary document from manufacturer for non-manufacturer tiers
- Automatic resale blocking for unauthorized intermediaries
- Chain position tracking
- Compliance verification

### Distribution
- Auto-distribution to B2B clients
- Email/push/in-app notifications
- Retry logic for failed deliveries
- Distribution tracking

## Architecture

### Clean Structure
```
modules/Supermarket/
├── Domain/
│   └── Models/
│       ├── Document.php
│       ├── DocumentTemplate.php
│       ├── DocumentHistory.php
│       ├── SupplierTier.php
│       └── SupplyChainLink.php
├── Application/
│   └── Services/
│       ├── DocumentManagementService.php
│       ├── DocumentDistributionService.php
│       └── SupplyChainVerificationService.php
└── Infrastructure/
    └── Http/
        └── Controllers/
            └── DocumentController.php
```

### Database Tables
- `supermarket_documents` - Main documents table with B2B certificate fields
- `document_templates` - Document templates
- `document_history` - Audit trail
- `supplier_tiers` - Tier classification (TIER_1 to TIER_4)
- `supply_chain_links` - Chain tracking

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create all required tables:
- `add_b2b_certificate_fields_to_documents_table`
- `create_document_templates_table`
- `create_document_history_table`
- `create_supplier_tiers_table`
- `create_supply_chain_links_table`
- `add_supply_chain_fields_to_documents_table`

### 2. Seed Supplier Tiers

```bash
php artisan db:seed --class=SupplierTierSeeder
```

This creates 4 supplier tiers:
1. **TIER_1 - Manufacturer** (Level 1)
   - Factory, farm, or product origin
   - Start of supply chain
   - Can sell to: Trading House, Wholesaler, Retailer
   - Max chain depth: 4

2. **TIER_2 - Trading House** (Level 2)
   - Trading house or distributor
   - Requires primary document from manufacturer
   - Can sell to: Wholesaler, Retailer
   - Max chain depth: 3

3. **TIER_3 - Wholesaler** (Level 3)
   - Wholesale seller
   - Requires primary document from manufacturer
   - Can sell to: Wholesaler, Retailer
   - Max chain depth: 2

4. **TIER_4 - Retailer** (Level 4)
   - Retail store or business on platform
   - End of supply chain
   - Cannot sell further
   - Max chain depth: 0

## API Endpoints

### Supplier Document Management
Base URL: `/api/supermarket/supplier/documents`

**Authentication:** Required (auth:sanctum, supplier.verified, fraud-check)

#### CRUD Operations
- `GET /` - List documents (with filters: status, type, search)
- `POST /` - Create document
- `GET /{document}` - Get document details
- `PUT /{document}` - Update document
- `DELETE /{document}` - Delete document

#### B2B Certificate Operations
- `POST /{document}/add-quantity` - Add delivered quantity
- `POST /{document}/close` - Close certificate
- `POST /{document}/reopen` - Reopen certificate (admin only)
- `POST /{document}/validate` - Validate certificate

#### History & Distribution
- `GET /{document}/history` - Get document history
- `POST /{document}/distribute` - Auto-distribute to B2B clients
- `POST /{document}/distribute/{client}` - Distribute to specific client

#### Statistics
- `GET /stats` - Get supplier statistics

## Usage Examples

### Create a Document (Manufacturer)

```php
POST /api/supermarket/supplier/documents
Content-Type: multipart/form-data

{
    "document_type": "certificate",
    "title": "Сертификат качества партии №12345",
    "file": <PDF file>,
    "document_number": "CERT-2024-12345",
    "batch_number": "BATCH-12345",
    "barcode": "4601234567890",
    "batch_weight": 1000.500,
    "valid_from": "2024-01-01",
    "valid_to": "2025-01-01",
    "supplier_tier_id": 1, // Manufacturer tier
    "auto_distribute": true
}
```

### Create a Document (Wholesaler with Primary Document)

```php
POST /api/supermarket/supplier/documents

{
    "document_type": "certificate",
    "title": "Сертификат перепродажи",
    "file": <PDF file>,
    "document_number": "CERT-2024-67890",
    "batch_number": "BATCH-12345", // Same batch as manufacturer
    "batch_weight": 500.000,
    "supplier_tier_id": 3, // Wholesaler tier
    "primary_document_id": 123, // Reference to manufacturer's document
    "requires_primary_document": true
}
```

### Add Delivered Quantity

```php
POST /api/supermarket/supplier/documents/{document}/add-quantity

{
    "quantity": 250.500
}
```

### Validate Certificate

```php
POST /api/supermarket/supplier/documents/{document}/validate
```

Response:
```json
{
    "success": true,
    "is_valid": true
}
```

## Supply Chain Verification

The system automatically validates supply chains on document creation:

### Validation Rules

1. **Primary Document Requirement**
   - All non-manufacturer tiers must reference primary document from manufacturer
   - Primary document must not be blocked

2. **Chain Depth Validation**
   - Chain depth calculated from manufacturer
   - Cannot exceed tier-specific max depth
   - Manufacturer: max 4 hops
   - Trading House: max 3 hops
   - Wholesaler: max 2 hops
   - Retailer: max 0 hops (end of chain)

3. **Tier Transition Validation**
   - Only allowed transitions between tiers
   - Configured in SupplierTier.allowed_transitions

### Example Valid Chains

✅ **Chain 1 (4 hops):**
```
Manufacturer (TIER_1) 
  → Wholesaler (TIER_3) 
  → Wholesaler (TIER_3) 
  → B2B Buyer (TIER_4)
```

✅ **Chain 2 (5 hops):**
```
Manufacturer (TIER_1)
  → Trading House (TIER_2)
  → Wholesaler (TIER_3)
  → Wholesaler (TIER_3)
  → Consumer/Store (TIER_4)
```

❌ **Invalid Chain (too long):**
```
Manufacturer (TIER_1)
  → Wholesaler (TIER_3)
  → Wholesaler (TIER_3)
  → Wholesaler (TIER_3)  // Exceeds max depth
  → B2B Buyer (TIER_4)
```

## Admin Interface

### Filament Resource

Access via Filament admin panel under **Supermarket** group:

- **DocumentResource** - Full CRUD for documents
  - Filters by type, status, tier, validity, resale block
  - Actions: validate, view history, delete
  - Bulk actions: validate all, delete all
  - View document history with full audit trail

### Supply Chain Monitoring

The system provides supply chain statistics:
- Total chain links
- Blocked resale links
- Non-compliant links
- Average chain depth
- Distribution by tier

## Vue Component

### DocumentManagement.vue

Location: `frontend/src/components/business/supermarket/DocumentManagement.vue`

Features:
- Statistics dashboard (total, active, expired, closed)
- Document list with filters
- Batch tracking display
- Quantity management
- Certificate status indicators
- History view modal
- Auto-distribution controls

## File Upload Validation

### Supported Formats
- PDF (application/pdf)
- JPEG (image/jpeg, image/jpg)
- TIFF (image/tiff, image/tif)

### Validation Rules
- Max file size: 10MB
- Format validation on upload
- Automatic format detection
- Hash calculation for integrity

## Certificate Expiry

### Automatic Checks
- Validity period checked on validation
- Expired certificates marked automatically
- Status updated to 'expired'
- History logged

### Manual Validation
```php
$document->validateCertificate($userId);
```

## Batch Quantity Management

### Rules
- Cannot add quantity exceeding batch_weight
- Certificate closes automatically when fully sold
- Remaining quantity calculated: batch_weight - delivered_quantity
- Transaction tracking in supply_chain_links

### Example
```php
// Batch weight: 1000 kg
// Delivered: 750 kg
// Remaining: 250 kg

$document->addDeliveredQuantity(250); // Closes certificate
$document->addDeliveredQuantity(300); // Throws exception
```

## Resale Blocking

### Automatic Blocking
Documents are automatically blocked when:
- Chain depth exceeds maximum
- Invalid tier transition detected
- Missing primary document for non-manufacturer
- Compliance violations detected

### Manual Blocking
```php
$verificationService->blockUnauthorizedResale(
    $documentId,
    'Exceeds maximum chain depth',
    $userId
);
```

### Unblocking
```php
$verificationService->unblockDocument($documentId, $userId);
```

## Testing

### Run Tests

```bash
php artisan test --filter=Document
```

### Test Coverage
- Document CRUD operations
- Batch quantity validation
- Certificate validation
- Supply chain verification
- Distribution logic
- Resale blocking

## Monitoring & Logs

### Audit Logging
All document operations are logged via DocumentHistory:
- Created
- Updated
- Deleted
- Distributed
- Validated
- Expired
- Closed

### System Logs
Check Laravel logs for:
- File upload errors
- Distribution failures
- Chain validation violations
- Resale blocking events

## Troubleshooting

### Common Issues

**1. Migration fails with table not found**
- Ensure supermarket_documents table exists
- Check if previous migrations ran successfully

**2. Supplier tier validation fails**
- Run seeder: `php artisan db:seed --class=SupplierTierSeeder`
- Check supplier_tiers table has 4 records

**3. File upload rejected**
- Check file format (PDF/JPEG/TIFF only)
- Verify file size < 10MB
- Check storage permissions

**4. Chain validation fails**
- Verify primary_document_id references valid document
- Check supplier_tier_id is set correctly
- Ensure chain depth within limits

## Security

### Compliance
- PII anonymization before external AI (not implemented here)
- Audit logging for all operations
- Role-based access control
- Fraud check middleware on API endpoints

### Authentication
- Sanctum authentication required
- Supplier verification required
- Fraud check middleware
- Rate limiting (throttle:30,1 for create)

## Performance

### Optimization
- Database indexes on frequently queried fields
- Caching recommended for document lists
- Queue distribution notifications
- Batch operations for bulk actions

### Scalability
- Designed for 5k-50k+ RPS
- Async notification processing
- Circuit breaker for external services
- Retry logic with exponential backoff

## Next Steps

1. Configure email/push notification templates
2. Set up queue workers for distribution
3. Configure storage for document files
4. Set up monitoring for chain violations
5. Create admin dashboard for supply chain analytics
6. Implement automated expiry checks (scheduler)
7. Add integration with external certificate verification services

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review documentation in `docs/supermarket/`
- Check Filament admin panel for visual debugging
