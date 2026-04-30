# Payment Compliance Implementation - CatVRF

**Federal Law Compliance: ФЗ-161, ФЗ-115, 54-ФЗ**

**Version:** 1.0  
**Date:** 2026-04-29  
**Status:** PRODUCTION READY

---

## Overview

This implementation provides comprehensive compliance with Russian federal laws for payment processing in the CatVRF marketplace:

- **ФЗ-161** "О национальной платёжной системе" - National Payment System requirements
- **ФЗ-115** "О противодействии легализации..." - AML/KYC requirements
- **54-ФЗ** "О применении ККТ" - Fiscalization (KKT) requirements

The implementation follows Clean Architecture principles with DDD, integrates with existing FraudControl, Audit, and BigData services, and provides real-time monitoring via Prometheus.

---

## Architecture

### 9-Layer Clean Architecture

```
modules/Payment/
├── Domain/
│   ├── Entities/
│   │   ├── PaymentRule.php          # ФЗ-161 rule versioning
│   │   ├── AMLCheck.php             # ФЗ-115 AML/KYC checks
│   │   └── FiscalReceipt.php        # 54-ФЗ fiscal receipts
│   ├── Repositories/
│   │   ├── PaymentRuleRepositoryInterface.php
│   │   ├── AMLCheckRepositoryInterface.php
│   │   └── FiscalReceiptRepositoryInterface.php
│   └── Enums/                       # (existing)
├── Application/
│   └── Services/
│       ├── PaymentRulesService.php  # ФЗ-161 compliance
│       ├── AMLService.php           # ФЗ-115 compliance
│       └── FiscalizationService.php # 54-ФЗ compliance
├── Infrastructure/
│   └── Repositories/
│       ├── EloquentPaymentRuleRepository.php
│       ├── EloquentAMLCheckRepository.php
│       └── EloquentFiscalReceiptRepository.php
└── Database/
    └── Migrations/
        ├── 2026_04_29_000001_create_payment_rules_table.php
        ├── 2026_04_29_000002_create_aml_checks_table.php
        └── 2026_04_29_000003_create_fiscal_receipts_table.php
```

### Integration Points

- **FraudControlService** - Real-time fraud detection (ФЗ-161)
- **AuditService** - Immutable audit logging for all compliance checks
- **BigData** - ClickHouse integration for analytics and monitoring
- **PaymentFacadeService** - Unified payment processing with compliance
- **Queue System** - Async processing of compliance tasks

---

## ФЗ-161 Implementation

### Requirements

1. **Transaction Limits** - Maximum amounts per transaction/day/month
2. **Fraud Detection** - Real-time checks with 48-hour block capability
3. **Settlement Rules** - Electronic payments only to bank accounts
4. **Payment Aggregator Status** - Contract with authorized banks
5. **Monitoring** - 24/7 real-time risk monitoring
6. **Rule Versioning** - Documented, versioned payment rules

### Configuration

```php
// config/payment_compliance.php
'fz161' => [
    'enabled' => env('FZ161_ENABLED', true),
    'limits' => [
        'max_amount_per_transaction' => 15_000_000_00, // 15M RUB
        'max_amount_per_day' => 50_000_000_00,        // 50M RUB
        'max_amount_per_month' => 500_000_000_00,     // 500M RUB
    ],
    'fraud_detection' => [
        'real_time_check_required' => true,
        'block_suspicious_operations' => true,
        'block_duration_hours' => 48,
    ],
    'settlement' => [
        'bank_account_only' => true,
        'allowed_settlement_methods' => ['bank_account', 'sbp'],
    ],
],
```

### Usage

```php
use App\Domains\Payment\Services\PaymentFacadeService;

// Validate payment against ФЗ-161
$validation = $paymentFacade->validateUnder161([
    'amount_kopecks' => 100_000_00,
    'user_id' => $userId,
    'tenant_id' => $tenantId,
    'settlement_method' => 'bank_account',
]);

if (!$validation['is_compliant']) {
    // Handle violations
    foreach ($validation['violations'] as $violation) {
        Log::warning('ФЗ-161 violation', $violation);
    }
}

// Create/update payment rules
$paymentRules->createRule(
    code: 'transaction_limits',
    name: 'Transaction Limits',
    description: 'Maximum transaction amounts',
    category: 'transaction_limits',
    ruleData: [
        'max_amount_kopecks' => 15_000_000_00,
        'max_daily_amount_kopecks' => 50_000_000_00,
    ],
    createdBy: 'admin',
);

// Export rules for regulatory audit
$export = $paymentRules->exportRulesForAudit(
    from: new DateTime('2026-01-01'),
    to: new DateTime('2026-12-31'),
);
```

### API Endpoints

```php
// Get active payment rules
GET /api/payment/compliance/rules

// Get rule history
GET /api/payment/compliance/rules/{code}/history

// Export rules for audit
GET /api/payment/compliance/rules/export?from=2026-01-01&to=2026-12-31
```

---

## ФЗ-115 Implementation

### Requirements

1. **Client Identification** - Simplified/full/enhanced KYC levels
2. **Data Retention** - 5-year retention for all AML data
3. **Transaction Monitoring** - Velocity, geo, profile checks
4. **Rosfinmonitoring Reporting** - Automatic reporting of suspicious transactions
5. **Blocking/Refusal** - Auto-block critical risk, manual review for high risk

### Configuration

```php
// config/payment_compliance.php
'fz115' => [
    'enabled' => env('FZ115_ENABLED', true),
    'kyc' => [
        'simplified_threshold' => 100_000_00,   // 100k RUB
        'full_kyc_threshold' => 1_000_000_00,   // 1M RUB
        'enhanced_kyc_threshold' => 5_000_000_00, // 5M RUB
    ],
    'risk_scoring' => [
        'critical_threshold' => 0.85,
        'high_threshold' => 0.65,
        'medium_threshold' => 0.40,
    ],
    'rosfinmonitoring' => [
        'enabled' => env('FZ115_ROSFIN_ENABLED', false),
        'api_url' => env('FZ115_ROSFIN_API_URL'),
        'api_key' => env('FZ115_ROSFIN_API_KEY'),
        'report_threshold_amount' => 100_000_00, // 1M RUB
    ],
],
```

### Usage

```php
use Modules\Payment\Application\Services\AMLService;

// Perform ФЗ-115 compliance check
$amlCheck = $amlService->check115(
    userId: $userId,
    amountKopecks: 500_000_00,
    currency: 'RUB',
    tenantId: $tenantId,
    orderId: $orderId,
    ipAddress: $request->ip(),
    deviceFingerprint: $request->header('X-Device-Fingerprint'),
);

// Check result
if ($amlCheck->isBlocked()) {
    // Payment blocked - critical risk
    return response()->json(['error' => 'AML block'], 403);
}

if ($amlCheck->requiresReview()) {
    // Queue for manual review
    $this->queueForManualReview($amlCheck);
}

if ($amlCheck->requiresFullKYC()) {
    // Request full KYC documents
    $this->requestKYCDocuments($userId);
}

// Get AML checks for user
$checks = $amlService->getUserChecks($userId, limit: 50);

// Cleanup old checks (5-year retention)
$deleted = $amlService->cleanupOldChecks();
```

### Risk Factors

The AML check evaluates multiple risk factors:

- **Velocity 24h** - Number of transactions in last 24 hours
- **Velocity 7d** - Number of transactions in last 7 days
- **Geo Mismatch** - Geographic location inconsistency
- **Profile Mismatch** - Transaction amount vs. user profile
- **New Device** - Transaction from new device
- **New IP** - Transaction from new IP address
- **Amount Anomaly** - Unusually large transaction
- **Frequency Spike** - High transaction frequency

### KYC Levels

- **Simplified** - Amount < 100k RUB, low risk
- **Full** - Amount 100k-1M RUB or medium risk
- **Enhanced** - Amount > 1M RUB or high risk

### Rosfinmonitoring Integration

Automatic reporting for:
- Critical risk transactions
- Transactions > 1M RUB
- Suspicious patterns

```php
// Scheduled job (runs daily)
php artisan schedule:run

// Manual trigger
php artisan rosfinmonitoring:report
```

### API Endpoints

```php
// Get AML check by UUID
GET /api/payment/compliance/aml/{uuid}

// Get user AML checks
GET /api/payment/compliance/aml/user/{userId}

// Get pending Rosfinmonitoring reports
GET /api/payment/compliance/aml/pending-reports
```

---

## 54-ФЗ Implementation

### Requirements

1. **Prepayment Receipt** - "Предоплата/Аванс" when payment captured
2. **Full Payment Receipt** - "Полный расчёт" with prepayment offset on fulfillment
3. **Refund Receipt** - "Возврат" when payment refunded
4. **B2B Exception** - No fiscalization for B2B settlements
5. **Agent Information** - Marketplace as payment agent with seller INN
6. **OFD Integration** - Support for multiple OFD providers

### Configuration

```php
// config/fiscalization.php
'default_provider' => env('FISCALIZATION_DEFAULT_PROVIDER', 'orangedata'),

'providers' => [
    'orangedata' => [
        'enabled' => env('ORANGEDATA_ENABLED', true),
        'api_url' => env('ORANGEDATA_API_URL'),
        'api_key' => env('ORANGEDATA_API_KEY'),
    ],
    'cloudkassir' => [
        'enabled' => env('CLOUDKASSIR_ENABLED', false),
        'api_url' => env('CLOUDKASSIR_API_URL'),
        'api_key' => env('CLOUDKASSIR_API_KEY'),
    ],
    'atol' => [
        'enabled' => env('ATOL_ENABLED', false),
        'api_url' => env('ATOL_API_URL'),
        'api_key' => env('ATOL_API_KEY'),
        'group_code' => env('ATOL_GROUP_CODE'),
    ],
],

'agent' => [
    'type' => 'payment_agent',
    'name' => env('FISCAL_AGENT_NAME', 'CatVRF Marketplace'),
    'inn' => env('FISCAL_AGENT_INN'),
    'payment_address' => 'https://catvrf.ru',
],
```

### Usage

```php
use Modules\Payment\Application\Services\FiscalizationService;

// Send prepayment receipt (when payment captured)
$receipt = $fiscalization->fiscalizePrepayment(
    paymentIntentUuid: $paymentIntentUuid,
    tenantId: $tenantId,
    orderId: $orderId,
    sellerInn: $seller->inn,
    agentName: 'CatVRF Marketplace',
    amountKopecks: $amountKopecks,
    items: [
        [
            'name' => 'Product Name',
            'quantity' => 1,
            'price' => 100_000_00,
            'vat_rate' => 20,
        ],
    ],
);

// Send full payment receipt (when order fulfilled)
$receipt = $fiscalization->fiscalizeFullPayment(
    paymentIntentUuid: $paymentIntentUuid,
    tenantId: $tenantId,
    orderId: $orderId,
    sellerInn: $seller->inn,
    agentName: 'CatVRF Marketplace',
    amountKopecks: $amountKopecks,
    items: $items,
    prepaymentReceiptUuid: $prepaymentReceiptUuid,
    prepaymentAmountKopecks: $prepaymentAmount,
);

// Send refund receipt
$receipt = $fiscalization->fiscalizeRefund(
    paymentIntentUuid: $paymentIntentUuid,
    tenantId: $tenantId,
    orderId: $orderId,
    sellerInn: $seller->inn,
    agentName: 'CatVRF Marketplace',
    amountKopecks: $refundAmount,
    items: $items,
    refundReason: 'Customer request',
);

// Get receipt by UUID
$receipt = $fiscalization->getReceipt($receiptUuid);

// Get receipts by payment intent
$receipts = $fiscalization->getReceiptsByPaymentIntent($paymentIntentUuid);

// Process pending receipts (scheduled job)
$processed = $fiscalization->processPendingReceipts();

// Cleanup old receipts (5-year retention)
$deleted = $fiscalization->cleanupOldReceipts();
```

### Receipt Types

1. **Prepayment** - Sent when payment is captured
2. **Full Payment** - Sent when order is fulfilled (includes prepayment offset)
3. **Refund** - Sent when payment is refunded

### OFD Providers

Supported providers:
- **OrangeData** - https://orangedata.ru (default)
- **CloudKassir** - https://cloudkassir.ru
- **Atol** - https://atol.ru
- **Yandex.Kassa** - Built-in fiscalization

### Item Mapping

```php
$items = [
    [
        'name' => 'Product Name',          // Max 128 chars
        'quantity' => 1,                  // Max 99999.999
        'price' => 100_000_00,            // In kopecks
        'vat_rate' => 20,                 // VAT rate: 0, 10, 18, 20, 110, 118, 120
        'payment_method' => 'electronic', // cash, electronic, prepayment, credit, other
        'payment_subject' => 'commodity', // commodity, service, work, etc.
    ],
];
```

### API Endpoints

```php
// Get receipt by UUID
GET /api/payment/compliance/fiscal/{uuid}

// Get receipts by payment intent
GET /api/payment/compliance/fiscal/payment/{paymentIntentUuid}

// Get pending receipts
GET /api/payment/compliance/fiscal/pending

// Retry failed receipt
POST /api/payment/compliance/fiscal/{uuid}/retry
```

---

## PaymentFacadeService Integration

### Federal Law Methods

The `PaymentFacadeService` now includes federal law compliance methods:

```php
use App\Domains\Payment\Services\PaymentFacadeService;

// Process payment with full compliance (ФЗ-161 + ФЗ-115 + 54-ФЗ)
$result = $paymentFacade->processWithCompliance(
    payable: $order,
    method: $paymentMethod,
    amount: $amount,
    fiscalItems: $items,
    sellerInn: $seller->inn,
    preferredGateway: 'tinkoff',
);

// Release escrow with fiscalization (54-ФЗ)
$hold = $paymentFacade->escrowReleaseWithFiscal(
    hold: $escrowHold,
    amountKopecks: $amount,
    targetWalletId: $walletId,
    reason: 'Order fulfilled',
    fiscalItems: $items,
    sellerInn: $seller->inn,
    prepaymentReceiptUuid: $prepaymentReceiptUuid,
    prepaymentAmountKopecks: $prepaymentAmount,
);

// Refund with fiscalization (54-ФЗ)
$result = $paymentFacade->refundWithFiscal(
    paymentIntentId: $paymentIntentId,
    amount: $refundAmount,
    reason: 'Customer request',
    fiscalItems: $items,
    sellerInn: $seller->inn,
);

// B2B split payout (no 54-ФЗ fiscalization)
$result = $paymentFacade->splitAndPayoutB2B($order);
```

### Payment Flow with Compliance

```
1. Customer initiates payment
   ↓
2. ФЗ-161 validation (limits, fraud detection, settlement)
   ↓
3. ФЗ-115 AML/KYC check (risk scoring, KYC level)
   ↓
4. Payment processing through gateway
   ↓
5. If success: Send prepayment receipt (54-ФЗ)
   ↓
6. Order fulfilled
   ↓
7. Send full payment receipt (54-ФЗ)
   ↓
8. Escrow release to seller
   ↓
9. B2B payout (no fiscalization)
```

---

## Scheduled Tasks

### Configuration

```php
// config/payment_compliance.php
'scheduled_tasks' => [
    'aml_cleanup' => [
        'enabled' => true,
        'schedule' => '0 2 * * *', // Daily at 2 AM
    ],
    'fiscal_cleanup' => [
        'enabled' => true,
        'schedule' => '0 3 * * *', // Daily at 3 AM
    ],
    'rosfinmonitoring_report' => [
        'enabled' => false,
        'schedule' => '0 4 * * *', // Daily at 4 AM
    ],
    'pending_receipts' => [
        'enabled' => true,
        'schedule' => '*/5 * * * *', // Every 5 minutes
    ],
],
```

### Adding to Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // AML cleanup (5-year retention)
    if (config('payment_compliance.scheduled_tasks.aml_cleanup.enabled')) {
        $schedule->call(fn() => app(AMLService::class)->cleanupOldChecks())
            ->cron(config('payment_compliance.scheduled_tasks.aml_cleanup.schedule'));
    }

    // Fiscal cleanup (5-year retention)
    if (config('payment_compliance.scheduled_tasks.fiscal_cleanup.enabled')) {
        $schedule->call(fn() => app(FiscalizationService::class)->cleanupOldReceipts())
            ->cron(config('payment_compliance.scheduled_tasks.fiscal_cleanup.schedule'));
    }

    // Rosfinmonitoring report
    if (config('payment_compliance.scheduled_tasks.rosfinmonitoring_report.enabled')) {
        $schedule->job(new RosfinmonitoringReportJob())
            ->cron(config('payment_compliance.scheduled_tasks.rosfinmonitoring_report.schedule'));
    }

    // Process pending fiscal receipts
    if (config('payment_compliance.scheduled_tasks.pending_receipts.enabled')) {
        $schedule->call(fn() => app(FiscalizationService::class)->processPendingReceipts())
            ->cron(config('payment_compliance.scheduled_tasks.pending_receipts.schedule'));
    }
}
```

---

## Monitoring & Alerts

### Prometheus Metrics

The implementation exports the following metrics:

**AML Metrics:**
- `aml_checks_total{status, risk_level, kyc_level}` - Total AML checks
- `aml_checks_pending_review` - Checks requiring manual review
- `aml_checks_velocity_24h` - Transaction velocity
- `aml_checks_amount_anomaly_total` - Amount anomalies
- `rosfinmonitoring_reports_failed_total` - Failed reports

**Fiscal Metrics:**
- `fiscal_receipts_total{status, type}` - Total fiscal receipts
- `fiscal_receipts_pending` - Pending receipts
- `fiscal_receipts_retries_total` - Retry attempts
- `ofd_requests_total{provider, status}` - OFD API requests
- `fiscal_receipt_confirmation_duration_seconds` - Confirmation time

**ФЗ-161 Metrics:**
- `fz161_violations_total{type}` - Violation count
- `payment_volume_kopecks` - Total payment volume
- `payments_blocked_fraud_total` - Fraud blocks
- `settlement_violations_total` - Settlement violations

### Alert Rules

See `monitoring/prometheus/rules/payment_compliance_alerts.yml` for complete alert definitions.

**Key Alerts:**
- `PaymentComplianceAMLHighRiskRate` - >10% critical risk
- `PaymentComplianceAMLBlocked` - Payments blocked by AML
- `PaymentComplianceFiscalReceiptFailure` - >5% fiscal failures
- `PaymentComplianceFZ161Violation` - ФЗ-161 violations
- `PaymentComplianceOFDErrorRate` - OFD provider errors

### Grafana Dashboards

Import the following dashboards:
- `monitoring/grafana/dashboards/payment-compliance-aml.json`
- `monitoring/grafana/dashboards/payment-compliance-fiscal.json`
- `monitoring/grafana/dashboards/payment-compliance-fz161.json`

---

## Environment Variables

### Required Variables

```bash
# ФЗ-161
FZ161_ENABLED=true
FZ161_MAX_AMOUNT=1500000000
FZ161_MAX_DAILY_AMOUNT=5000000000
FZ161_MAX_MONTHLY_AMOUNT=50000000000

# ФЗ-115
FZ115_ENABLED=true
FZ115_SIMPLIFIED_THRESHOLD=10000000
FZ115_FULL_KYC_THRESHOLD=100000000
FZ115_ROSFIN_ENABLED=false
FZ115_ROSFIN_API_URL=https://api.rosfinmonitoring.ru
FZ115_ROSFIN_API_KEY=your_api_key
FZ115_ORGANIZATION_INN=your_inn

# 54-ФЗ
FZ54_ENABLED=true
FISCALIZATION_DEFAULT_PROVIDER=orangedata
ORANGEDATA_ENABLED=true
ORANGEDATA_API_URL=https://api.orangedata.ru/api/v2/documents
ORANGEDATA_API_KEY=your_api_key
ORANGEDATA_GROUP=Main
ORANGEDATA_KEY=your_key

# Agent Information
FISCAL_AGENT_TYPE=payment_agent
FISCAL_AGENT_NAME=CatVRF Marketplace
FISCAL_AGENT_INN=your_inn
FISCAL_AGENT_PHONE=+78001234567
```

### Optional Variables

```bash
# OFD Providers
CLOUDKASSIR_ENABLED=false
CLOUDKASSIR_API_URL=https://api.cloudkassir.ru/api/v2
CLOUDKASSIR_API_KEY=your_api_key

ATOL_ENABLED=false
ATOL_API_URL=https://online.atol.ru/possystem/v4
ATOL_API_KEY=your_api_key
ATOL_GROUP_CODE=your_group_code
ATOL_INN=your_inn

# Monitoring
FZ161_MONITORING_ENABLED=true
FZ161_ALERT_ANOMALY=true
FZ161_VOLUME_SPIKE_THRESHOLD=2.5

# Data Retention
FZ115_RETENTION_YEARS=5
FZ54_RETENTION_YEARS=5
```

---

## Testing

### Unit Tests

```php
// tests/Unit/Payment/PaymentRulesServiceTest.php
public function test_validate_under_161_violations()
{
    $service = app(PaymentRulesService::class);
    
    $result = $service->validateUnder161([
        'amount_kopecks' => 20_000_000_00, // Exceeds limit
        'settlement_method' => 'cash', // Not bank account
    ]);
    
    $this->assertFalse($result['is_compliant']);
    $this->assertCount(2, $result['violations']);
}

// tests/Unit/Payment/AMLServiceTest.php
public function test_check115_high_risk()
{
    $service = app(AMLService::class);
    
    $check = $service->check115(
        userId: 1,
        amountKopecks: 5_000_000_00, // High amount
        tenantId: null,
    );
    
    $this->assertEquals('high', $check->riskLevel);
    $this->assertTrue($check->requiresFullKYC);
}

// tests/Unit/Payment/FiscalizationServiceTest.php
public function test_fiscalize_prepayment()
{
    $service = app(FiscalizationService::class);
    
    $receipt = $service->fiscalizePrepayment(
        paymentIntentUuid: 'test-uuid',
        tenantId: null,
        orderId: null,
        sellerInn: '123456789012',
        agentName: 'CatVRF Marketplace',
        amountKopecks: 100_000_00,
        items: [[
            'name' => 'Test Product',
            'quantity' => 1,
            'price' => 100_000_00,
            'vat_rate' => 20,
        ]],
    );
    
    $this->assertEquals('prepayment', $receipt->type);
    $this->assertEquals('pending', $receipt->status);
}
```

### Feature Tests

```php
// tests/Feature/Payment/ComplianceFlowTest.php
public function test_payment_with_full_compliance()
{
    // Create order
    $order = Order::factory()->create();
    
    // Process with compliance
    $result = $this->paymentFacade->processWithCompliance(
        payable: $order,
        method: PaymentMethodVO::fromString('card'),
        amount: new MoneyVO(100_000_00),
        fiscalItems: $this->getFiscalItems(),
        sellerInn: '123456789012',
    );
    
    $this->assertTrue($result->status->value === 'captured');
    
    // Check AML check was created
    $this->assertDatabaseHas('aml_checks', [
        'order_id' => $order->id,
    ]);
    
    // Check prepayment receipt was created
    $this->assertDatabaseHas('fiscal_receipts', [
        'order_id' => $order->id,
        'type' => 'prepayment',
    ]);
}
```

### Running Tests

```bash
# Run all compliance tests
php artisan test --filter=PaymentCompliance

# Run specific test suite
php artisan test tests/Unit/Payment/PaymentRulesServiceTest.php
php artisan test tests/Feature/Payment/ComplianceFlowTest.php

# Run with coverage
php artisan test --coverage --filter=PaymentCompliance
```

---

## Troubleshooting

### Common Issues

**1. AML checks blocking legitimate payments**

```php
// Check AML configuration
config(['payment_compliance.fz115.risk_scoring.critical_threshold' => 0.90]);

// Check specific user's AML history
$checks = $amlService->getUserChecks($userId);
foreach ($checks as $check) {
    Log::info('AML check', $check->toArray());
}
```

**2. Fiscal receipts failing to send**

```php
// Check OFD provider configuration
config(['fiscalization.providers.orangedata.enabled' => true]);

// Check pending receipts
$pending = $fiscalization->getPendingReceipts();
foreach ($pending as $receipt) {
    Log::error('Pending receipt', [
        'uuid' => $receipt->uuid,
        'error' => $receipt->errorMessage,
        'retry_count' => $receipt->retryCount,
    ]);
}

// Manually retry
$fiscalization->processPendingReceipts();
```

**3. Rosfinmonitoring reporting failures**

```php
// Check API credentials
$apiKey = config('payment_compliance.fz115.rosfinmonitoring.api_key');
$apiUrl = config('payment_compliance.fz115.rosfinmonitoring.api_url');

// Test API connection
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
])->get($apiUrl . '/health');

if (!$response->successful()) {
    Log::error('Rosfinmonitoring API error', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
}
```

**4. ФЗ-161 violations**

```php
// Check active payment rules
$rules = $paymentRules->getActiveRulesByCategory('transaction_limits');
foreach ($rules as $rule) {
    Log::info('Active rule', $rule->toArray());
}

// Check current transaction volume
$volume = DB::table('payment_intents')
    ->where('created_at', '>=', now()->startOfDay())
    ->sum('amount_kopecks');

$maxDaily = config('payment_compliance.fz161.limits.max_amount_per_day');
Log::info('Daily volume', [
    'current' => $volume,
    'max' => $maxDaily,
    'percentage' => ($volume / $maxDaily) * 100,
]);
```

### Debug Mode

```php
// Enable debug logging
config(['payment_compliance.general.audit_logging.log_sensitive_data' => true]);

// Enable testing mode for fiscalization
config(['fiscalization.testing.enabled' => true]);
config(['fiscalization.testing.mock_ofd_responses' => true]);
```

---

## Performance Considerations

### Optimization Strategies

1. **Caching** - Payment rules cached for 1 hour
2. **Async Processing** - Fiscal receipts sent via queue
3. **Batch Processing** - Rosfinmonitoring reports batched
4. **Database Indexing** - Proper indexes on all compliance tables
5. **BigData Integration** - Analytics in ClickHouse, not MySQL

### Performance Metrics

- **AML Check Latency** - < 100ms (cached), < 500ms (uncached)
- **Fiscal Receipt Send** - < 1s (async via queue)
- **ФЗ-161 Validation** - < 50ms (cached rules)

### Scaling

- Horizontal scaling via queue workers
- Database read replicas for compliance queries
- Redis for caching and queue management
- ClickHouse for analytics and reporting

---

## Security Considerations

### Data Protection

- **PII Anonymization** - All personal data anonymized before external API calls
- **Audit Logging** - Immutable audit trail for all compliance actions
- **Encryption** - Sensitive data encrypted at rest
- **API Keys** - Stored in environment variables, never in code

### Access Control

- Role-based access to compliance data
- Audit log access restricted to compliance officers
- Rosfinmonitoring API credentials restricted to production

### Compliance Audits

- Monthly compliance reports generated
- Annual external audit preparation
- Regulatory request handling workflow

---

## Migration Guide

### From Existing Payment System

1. **Run Migrations**
```bash
php artisan migrate --path=modules/Payment/Database/Migrations
```

2. **Update Configuration**
```bash
cp .env.example .env
# Add compliance variables
```

3. **Update Payment Calls**
```php
// Old
$result = $paymentFacade->process($order, $method, $amount);

// New (with compliance)
$result = $paymentFacade->processWithCompliance(
    $order, $method, $amount,
    fiscalItems: $items,
    sellerInn: $seller->inn,
);
```

4. **Seed Payment Rules**
```bash
php artisan db:seed --class=PaymentRulesSeeder
```

5. **Configure OFD Provider**
```bash
# Set up OrangeData account
# Add API credentials to .env
```

6. **Enable Scheduled Tasks**
```bash
php artisan schedule:work
```

---

## Support & Maintenance

### Daily Operations

- Monitor Prometheus alerts
- Review pending AML checks requiring manual review
- Check fiscal receipt pending queue
- Review compliance dashboard in Grafana

### Weekly Operations

- Review AML risk trends
- Check OFD provider error rates
- Review ФЗ-161 violation reports
- Audit compliance logs

### Monthly Operations

- Generate compliance reports
- Review data retention compliance
- Update payment rules if needed
- Review Rosfinmonitoring reporting status

### Annual Operations

- External compliance audit
- Update OFD provider contracts
- Review and update federal law requirements
- Compliance training for staff

---

## References

### Federal Laws

- [ФЗ-161 "О национальной платёжной системе"](https://consultant.ru/document/cons_doc_LAW_389819/)
- [ФЗ-115 "О противодействии легализации..."](https://consultant.ru/document/cons_doc_LAW_389818/)
- [54-ФЗ "О применении ККТ"](https://consultant.ru/document/cons_doc_LAW_389817/)

### OFD Providers

- [OrangeData Documentation](https://orangedata.ru/docs/)
- [CloudKassir Documentation](https://cloudkassir.ru/docs/)
- [Atol Documentation](https://atol.ru/docs/)

### Central Bank

- [Rosfinmonitoring](https://fedresurs.ru/)
- [Central Bank of Russia](https://cbr.ru/)

---

## Changelog

### Version 1.0 (2026-04-29)

- Initial implementation
- ФЗ-161 compliance with rule versioning
- ФЗ-115 compliance with AML/KYC checks
- 54-ФЗ compliance with fiscalization
- Integration with FraudControl, Audit, BigData
- Prometheus monitoring and alerts
- Comprehensive documentation

---

## License

Proprietary - CatVRF Marketplace

---

**Contact:** compliance@catvrf.ru  
**Support:** https://support.catvrf.ru  
**Documentation:** https://docs.catvrf.ru/compliance
