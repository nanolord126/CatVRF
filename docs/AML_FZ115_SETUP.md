# AML Service - ФЗ-115 Compliance Setup Guide

**Version:** 1.0  
**Date:** 2026-04-29  
**Project:** CatVRF - AI-powered Healthcare Marketplace  
**Compliance:** Russian Federal Law No. 115-FZ (ФЗ-115)

## Overview

The AML (Anti-Money Laundering) Service provides comprehensive ФЗ-115 compliance for CatVRF, including:
- Real-time risk scoring for all financial operations
- KYC (Know Your Customer) verification with 3 levels
- Suspicious operation detection and reporting to Rosfinmonitoring
- Integration with FraudControlService, BigData, and PaymentFacade
- Automatic dossier management with 5-year data retention
- Velocity controls and transaction monitoring

## Architecture

### Clean Architecture Structure

```
app/Domains/Payments/AML/
├── DTO/
│   ├── AMLCheckResult.php          # (205 lines) Check result with risk levels
│   ├── AMLRiskScore.php            # (237 lines) Risk score with factors
│   └── KYCLevel.php                # (207 lines) KYC levels and thresholds
├── Models/
│   ├── AmlCheck.php                # (259 lines) AML check records
│   ├── AmlDossier.php              # (320 lines) KYC dossier management
│   └── SuspiciousOperation.php     # (319 lines) Suspicious operations
├── Events/
│   ├── AmlCheckCompleted.php       # Check completion event
│   └── SuspiciousActivityDetected.php # Suspicious activity event
├── Jobs/
│   └── ReportToRosfinmonitoringJob.php # Rosfinmonitoring reporting
├── AMLService.php                 # (365 lines) Main AML service
├── AMLRiskCalculator.php          # Risk calculation logic
└── AMLDossierService.php          # KYC dossier management
```

### Database Schema

#### aml_checks
- Stores all AML checks with risk scores and factors
- Links to orders, payment transactions, users, tenants
- Indexed for fast queries on risk_score, passed status, and dates

#### aml_dossiers
- KYC dossiers with encrypted document storage
- 5-year retention per ФЗ-115
- Tracks verification status and completion percentage

#### suspicious_operations
- High-risk operations flagged for Rosfinmonitoring reporting
- XML/JSON payload generation
- Reporting status and reference tracking

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create:
- `aml_checks` table
- `aml_dossiers` table
- `suspicious_operations` table

### 2. Configure Environment Variables

Add to your `.env` file:

```bash
# AML Configuration
AML_ENABLED=true

# Rosfinmonitoring Integration (ФЗ-115)
ROSFINMONITORING_ENABLED=false
ROSFINMONITORING_API_KEY=your_api_key_here
ROSFINMONITORING_API_URL=https://api.rosfinmonitoring.ru
ROSFINMONITORING_TIMEOUT=30
AML_AUTO_REPORT=false
ROSFINMONITORING_TEST_MODE=true

# Sanctions Screening
SANCTIONS_SCREENING_ENABLED=true
OFAC_ENABLED=false
EU_SANCTIONS_ENABLED=false

# Notifications
AML_TELEGRAM_ENABLED=false
AML_SLACK_ENABLED=false
AML_EMAIL_ENABLED=true

# Four-Eyes Approval
AML_FOUR_EYES_ENABLED=true

# BigData Integration
AML_BIGDATA_ENABLED=true

# Fraud Control Integration
AML_FRAUD_CONTROL_ENABLED=true
```

### 3. Update Service Provider

Add to your `AppServiceProvider` or create a dedicated `AMLServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Payments\AML\AMLService;
use App\Domains\Payments\AML\AMLRiskCalculator;
use App\Domains\Payments\AML\AMLDossierService;

class AMLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AMLService::class, function ($app) {
            return new AMLService(
                new AMLRiskCalculator(),
                new AMLDossierService(),
                $app->make(\App\Services\Fraud\FraudControlService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
```

## Usage

### Basic AML Check

```php
use App\Domains\Payments\AML\AMLService;
use App\Models\Order;
use App\Models\User;

$amlService = app(AMLService::class);

// Check a payment
$order = Order::find($orderId);
$buyer = User::find($buyerId);

$result = $amlService->checkPayment($order, $buyer);

if (!$result->passed) {
    // Handle blocked operation
    throw new \Exception($result->reason);
}

// Operation passed, continue with payment processing
```

### Check Different Operation Types

```php
// Payment check
$result = $amlService->checkPayment($order, $buyer);

// Payout check
$result = $amlService->checkPayout($transaction, $seller);

// Transfer check
$result = $amlService->checkTransfer($transaction, $sender);

// Generic check
$result = $amlService->check($operation, $user, 'payment');
```

### KYC Management

```php
use App\Domains\Payments\AML\AMLDossierService;

$dossierService = app(AMLDossierService::class);

// Get current KYC level
$currentLevel = $dossierService->getCurrentKYCLevel($user);

// Request Enhanced KYC
$dossier = $dossierService->requestFullKYC($user);

// Upload documents
$dossierService->uploadDocument($user, 'passport', '/path/to/passport.pdf');
$dossierService->uploadDocument($user, 'inn', '/path/to/inn.pdf');

// Verify dossier
$dossierService->verifyDossier($dossierId, true, 'All documents verified');

// Get missing documents
$missing = $dossierService->getMissingDocuments($dossier);
```

### Get Statistics

```php
// User AML stats
$stats = $amlService->getUserAMLStats($user);
// Returns: total_checks, passed, failed, avg_risk_score, current_kyc_level, etc.

// Tenant AML stats
$stats = $amlService->getTenantAMLStats($tenantId);
// Returns: total_checks, passed, failed, high_risk_count, etc.

// Dossier statistics
$dossierStats = $dossierService->getDossierStatistics($tenantId);
// Returns: total, pending, verified, rejected, by kyc_level
```

## Risk Scoring

### Risk Factors

The AMLRiskCalculator evaluates multiple factors:

| Factor | Weight | Description |
|--------|--------|-------------|
| amount_over_100k | 40 | Amount exceeds 100,000 RUB |
| velocity_24h | 35 | More than 5 operations in 24 hours |
| geo_mismatch | 25 | User country ≠ seller country |
| clv_low | 20 | CLV score < 30 |
| new_account | 20 | Account created < 7 days ago |
| high_risk_category | 30 | Crypto, gambling, cash_out, p2p_transfer |
| unusual_time | 15 | Operations between 2 AM - 5 AM |
| device_fingerprint | 20 | Device fingerprint change |

### Risk Levels

- **Minimal** (0-19): No restrictions
- **Low** (20-39): Simplified KYC sufficient
- **Medium** (40-69): Standard KYC required
- **High** (70-84): Enhanced KYC required
- **Critical** (85-100): Automatic block

### KYC Levels

| Level | Threshold | Required Documents |
|-------|-----------|-------------------|
| Simplified | < 15,000 RUB | Phone, Email |
| Standard | 15,000 - 99,999 RUB | Passport, SNILS, Phone, Email |
| Enhanced | ≥ 100,000 RUB | Passport, INN, SNILS, Source of Funds, Selfie |

## ФЗ-115 Compliance

### Mandatory Reporting Threshold

Operations ≥ 100,000 RUB must be reported to Rosfinmonitoring within 3 business days.

### Data Retention

- All KYC data stored for 5 years per ФЗ-115
- Automatic cleanup of expired dossiers
- Encrypted document storage

### Suspicious Operation Detection

Operations flagged as suspicious:
- Risk score ≥ 70
- High-risk categories (crypto, gambling, etc.)
- Velocity limit exceeded
- Geographic anomalies

### Reporting to Rosfinmonitoring

The `ReportToRosfinmonitoringJob` automatically:
- Generates XML payload per ФЗ-115 format
- Sends via official API or through payment partner (Tinkoff/Tochka)
- Stores reference number and reporting status
- Retries on failure (3 attempts with exponential backoff)

## Integration with PaymentFacade

Add AML check to your payment processing flow:

```php
use App\Domains\Payments\AML\AMLService;

class PaymentFacade
{
    public function process(Order $order, User $buyer): PaymentResult
    {
        $amlService = app(AMLService::class);
        
        // AML check
        $amlResult = $amlService->checkPayment($order, $buyer);
        
        if (!$amlResult->passed) {
            return PaymentResult::blocked($amlResult->reason);
        }
        
        // Continue with payment processing
        // ...
    }
}
```

## Events

### AmlCheckCompleted

Dispatched when an AML check passes:

```php
Event::listen(AmlCheckCompleted::class, function ($event) {
    Log::info('AML check passed', [
        'check_id' => $event->amlCheck->id,
        'risk_score' => $event->amlCheck->risk_score,
    ]);
});
```

### SuspiciousActivityDetected

Dispatched when suspicious activity is detected:

```php
Event::listen(SuspiciousActivityDetected::class, function ($event) {
    // Send alert to compliance team
    Notification::route('mail', 'compliance@catvrf.ru')
        ->notify(new SuspiciousActivityAlert($event->amlCheck));
});
```

## Monitoring

### BigData Integration

All AML checks are automatically tracked in BigData:

```php
BigData::track('aml.check', [
    'tenant_id' => $tenant->id,
    'user_id' => $user->id,
    'risk_score' => $riskScore,
    'kyc_level' => $kycLevel,
    'passed' => $passed,
    'operation_type' => $operationType,
    'amount' => $amount,
    'risk_factors' => $riskFactors,
]);
```

### Metrics to Monitor

- Total AML checks per day
- Pass/fail ratio
- Average risk score
- KYC upgrade requests
- Suspicious operations count
- Rosfinmonitoring reporting success rate
- Velocity limit violations

### Grafana Dashboards

Create dashboard panels for:
- AML checks over time (passed/failed)
- Risk score distribution
- KYC level distribution
- Suspicious operations trend
- Reporting latency

## Testing

Run the integration test suite:

```bash
php artisan test tests/Feature/AML/AMLIntegrationTest.php
```

Test coverage includes:
- Low-risk payment passes with simplified KYC
- High amount requires Enhanced KYC
- Critical risk blocks operation
- Velocity limit blocks rapid operations
- Geo mismatch increases risk
- Risk score calculation
- KYC level determination
- Dossier creation and verification
- Cache serving
- Event dispatching
- Rosfinmonitoring job dispatching
- User stats
- Dossier completion tracking
- Suspicious operation creation
- Risk score DTO categories
- KYC level validation
- AML check scopes

## Configuration Options

### Risk Thresholds (config/aml.php)

```php
'risk_thresholds' => [
    'block' => 85,                    // Critical risk - automatic block
    'enhanced_kyc' => 70,            // High risk - requires Enhanced KYC
    'standard_kyc' => 40,            // Medium risk - requires Standard KYC
    'low_risk' => 20,                // Low risk threshold
],
```

### Amount Thresholds

```php
'amount_thresholds' => [
    'simplified' => 15000,           // 15,000 RUB - simplified KYC
    'standard' => 60000,             // 60,000 RUB - standard KYC
    'enhanced' => 100000,            // 100,000 RUB - enhanced KYC
    'reporting' => 100000,           // 100,000 RUB - Rosfinmonitoring reporting
],
```

### Velocity Controls

```php
'velocity' => [
    'enabled' => true,
    'window_hours' => 24,
    'max_operations' => 5,
    'block_on_exceed' => true,
],
```

## Troubleshooting

### AML Check Fails Unexpectedly

1. Check risk factors in the result:
```php
$result = $amlService->checkPayment($order, $user);
dd($result->riskFactors);
```

2. Verify user's CLV score and account age
3. Check transaction amount against thresholds
4. Review velocity limits

### KYC Upgrade Not Triggered

1. Verify config thresholds are correct
2. Check if auto_upgrade is enabled
3. Review dossier service logs

### Rosfinmonitoring Reporting Fails

1. Verify API credentials in `.env`
2. Check if test mode is enabled
3. Review job logs: `php artisan queue:work --queue=high`
4. Verify network connectivity to Rosfinmonitoring API

### Cache Issues

```bash
# Clear AML cache
php artisan cache:forget 'aml:*'

# Or clear all cache
php artisan cache:clear
```

## Security Considerations

### PII Protection

- All KYC documents encrypted before storage
- Passport numbers masked in summaries
- INN masked in summaries
- Audit logs exclude sensitive data

### Data Retention

- Automatic cleanup after 5 years
- Manual cleanup available via service
- GDPR-compliant deletion

### Access Control

- All AML operations tenant-isolated
- Admin-only access to sensitive data
- Audit trail for all modifications

## Performance

### Caching

- AML check results cached for 15 minutes
- Cache key based on user, operation type, and amount
- Automatic cache invalidation on user changes

### Database Optimization

- Indexed queries on tenant_id, user_id, risk_score, passed
- Efficient pagination for admin panels
- Batch operations for cleanup

### Queue Configuration

```bash
# Run AML reporting queue
php artisan queue:work --queue=high --tries=3 --timeout=120
```

## Maintenance

### Cleanup Expired Dossiers

Schedule in `app/Console/Kernel.php`:

```php
$schedule->call(function () {
    $dossierService = app(AMLDossierService::class);
    $count = $dossierService->cleanupExpiredDossiers();
    Log::info("Cleaned up {$count} expired AML dossiers");
})->daily();
```

### Review Suspicious Operations

Run weekly:

```bash
php artisan aml:review-suspicious
```

## Compliance Checklist

- [x] Risk scoring algorithm implemented
- [x] KYC levels with document requirements
- [x] Velocity controls
- [x] Suspicious operation detection
- [x] Rosfinmonitoring XML generation
- [x] 5-year data retention
- [x] Encrypted document storage
- [x] Audit logging
- [x] BigData tracking
- [x] Fraud control integration
- [x] Event-driven notifications
- [x] Comprehensive testing
- [x] Configuration management

## Support

For issues or questions:
- Review logs: `storage/logs/laravel.log`
- Check AML-specific logs in `storage/logs/aml.log`
- Contact compliance team for ФЗ-115 questions

## References

- ФЗ-115 (Federal Law No. 115-FZ): https://www.consultant.ru/document/cons_doc_LAW_28295/
- Rosfinmonitoring: https://fedresurs.ru/
- Central Bank of Russia AML Guidelines: https://cbr.ru/

## Changelog

### Version 1.0 (2026-04-29)
- Initial production-ready implementation
- Full ФЗ-115 compliance
- Integration with CatVRF architecture
- Comprehensive testing suite
- Complete documentation
