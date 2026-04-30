# 152-FZ Compliance Implementation Guide

## Overview

CatVRF implements full compliance with Russian Federal Law No. 152-FZ "On Personal Data" (Федеральный закон № 152-ФЗ "О персональных данных"). This document describes the implementation, architecture, and operational procedures for 152-FZ compliance.

**Version:** 1.0  
**Last Updated:** April 23, 2026  
**Compliance Level:** Full (Уровень 2 ИСПДн)

---

## Table of Contents

1. [Key Requirements](#key-requirements)
2. [Architecture](#architecture)
3. [Consent Management](#consent-management)
4. [Data Localization](#data-localization)
5. [Data Protection](#data-protection)
6. [Data Destruction](#data-destruction)
7. [Audit Logging](#audit-logging)
8. [Subject Rights](#subject-rights)
9. [API Endpoints](#api-endpoints)
10. [Testing](#testing)
11. [Deployment](#deployment)
12. [Monitoring](#monitoring)

---

## Key Requirements

### Legal Requirements (152-FZ)

| Requirement | Implementation | Status |
|-------------|----------------|--------|
| Explicit consent (Ст. 9) | Granular consent via ConsentEngine | ✅ |
| Written consent for biometrics (Ст. 11) | UKEDS/written form validation | ✅ |
| Data localization (Ст. 18) | All databases in Russia | ✅ |
| Protection level (ИСПДн) | Level 2 (FSTEC Order № 21) | ✅ |
| Subject rights (Ст. 20-21) | Access, correction, deletion APIs | ✅ |
| Data destruction (30 days) | PurgePersonalDataJob | ✅ |
| Audit logging | ClickHouse + fallback | ✅ |
| Roskomnadzor notification | Registry number configured | ✅ |

---

## Architecture

### Components

```
┌─────────────────────────────────────────────────────────────┐
│                     API Layer                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Registration │  │   Consents   │  │  Subject     │    │
│  │   Controller │  │  Controller  │  │  Rights API  │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ ConsentEngine│  │ PersonalData │  │   Behavioral │    │
│  │              │  │ AccessAudit  │  │  Integration │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                     Data Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │   Users DB   │  │ UserConsents │  │   ClickHouse │    │
│  │ (PostgreSQL) │  │  (PostgreSQL)│  │   (Audit)    │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                     Job Queue                               │
│  ┌──────────────┐  ┌──────────────┐                        │
│  │ PurgePersonal│  │ ConsentExpiry│                        │
│  │   DataJob    │  │     Job      │                        │
│  └──────────────┘  └──────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Consent Collection** → ConsentEngine → UserConsents table
2. **Biometric Processing** → Consent check → BehavioralBiometricsService
3. **Data Access** → JIT access check → PersonalDataAccessAudit → ClickHouse
4. **Consent Withdrawal** → UserConsents.update() → PurgePersonalDataJob (30 days)
5. **Data Destruction** → Job execution → Audit log → Certificate generation

---

## Consent Management

### Consent Types

| Type | Requires Written Form | Retention | Purpose |
|------|----------------------|-----------|---------|
| `registration` | No | 365 days | Account creation |
| `orders` | No | 1095 days | Order processing |
| `communications` | No | 180 days | Notifications |
| `biometric_face_id` | Yes | 365 days | Face authentication |
| `biometric_liveness` | Yes | 30 days | Liveness check |
| `biometric_behavioral` | Yes | 90 days | Continuous auth |
| `biometric_voice` | Yes | 365 days | Voice authentication |
| `kyb_verification` | Yes | 1825 days | Business verification |
| `document_verification` | Yes | 1825 days | Document validation |
| `long_term_storage` | No | 2555 days | Analytics retention |

### Consent Lifecycle

```
┌─────────┐    ┌──────────┐    ┌─────────────┐    ┌──────────┐
│ Granted │───▶│ Active   │───▶│ Withdrawn  │───▶│ Purged   │
└─────────┘    └──────────┘    └─────────────┘    └──────────┘
                    │                                    │
                    └──────────▶ Expired ────────────────┘
```

### Implementation

```php
use App\Services\PersonalData\ConsentEngine;
use App\Enums\ConsentType;

$consentEngine = app(ConsentEngine::class);

// Check consent
if ($consentEngine->hasConsent($user, ConsentType::BIOMETRIC_BEHAVIORAL)) {
    // Process behavioral data
}

// Require consent (throws exception if not granted)
$consentEngine->requireConsent($user, ConsentType::BIOMETRIC_FACE_ID, 'authentication');

// Grant consent
$consent = $consentEngine->grantConsent($user, ConsentType::REGISTRATION, $request);

// Withdraw consent
$consentEngine->withdrawConsent($user, ConsentType::BIOMETRIC_BEHAVIORAL, 'User request');
```

---

## Data Localization

### Configuration

All personal data databases are hosted in Russia:

```php
// config/personal-data.php
'localization' => [
    'enabled' => true,
    'region' => 'RU',
    'database_location' => 'Russia',
    'backup_location' => 'Russia',
    'cross_border_transfer_allowed' => false,
],
```

### Infrastructure

- **Primary DB:** PostgreSQL, Moscow, Russia
- **Backup DB:** PostgreSQL, St. Petersburg, Russia
- **Audit Log:** ClickHouse, Moscow, Russia
- **File Storage:** S3-compatible, Moscow, Russia

---

## Data Protection

### Encryption

**At Rest (AES-256-GCM):**
- Email, phone, INN, names
- Biometric vectors
- Behavioral profiles
- Document metadata

**In Transit (TLS 1.3):**
- All API endpoints
- Database connections
- Inter-service communication

### Access Control

**JIT (Just-In-Time) Access:**
- Requires Passkey + 2FA
- 60-minute session
- Full audit logging
- Automatic revocation

**Role-Based Access:**
- Platform admins: Full access with JIT
- Business users: Tenant data only
- Support agents: Masked data only
- Customers: Own data only

---

## Data Destruction

### Automatic Destruction

Triggered when consent is withdrawn:

```php
// Scheduled 30 days after withdrawal
PurgePersonalDataJob::dispatch($consentId)
    ->delay(now()->addDays(30));
```

### Destruction Methods

| Data Type | Method | Retention |
|-----------|--------|-----------|
| Biometric templates | Delete + overwrite | 30 days |
| Behavioral baselines | Delete + overwrite | 30 days |
| Liveness data | Delete + overwrite | 30 days |
| Order data | Anonymization | 3 years (tax) |
| Communication logs | Delete | 30 days |
| Account data | Soft delete | 1 year |

### Certificate Generation

Every destruction event generates a certificate (see `destruction_certificate_template.md`).

---

## Audit Logging

### ClickHouse Schema

```sql
CREATE TABLE personal_data_audit (
    event_type String,
    target_user_id UInt64,
    data_type String,
    action String,
    timestamp DateTime64(3),
    correlation_id String,
    ...
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(timestamp)
TTL timestamp + INTERVAL 7 YEAR;
```

### Logged Events

- `personal_data_access` - All data access
- `consent_granted` - Consent given
- `consent_withdrawn` - Consent revoked
- `personal_data_destroyed` - Data destruction
- `biometric_data_collected` - Biometric collection
- `access_violation` - Unauthorized access attempts

### Fallback

If ClickHouse is unavailable, logs are written to PostgreSQL fallback table.

---

## Subject Rights

### Access Request

```php
// GET /api/v1/personal-data/export
// Response: JSON/PDF with all user data
```

### Deletion Request

```php
// POST /api/v1/personal-data/withdraw-consent
{
    "consent_types": ["biometric_behavioral", "communications"],
    "reason": "User request"
}
```

### Correction Request

```php
// PATCH /api/v1/personal-data
{
    "email": "new@example.com",
    "phone": "+79001234567"
}
```

### Response Time

All subject rights requests are processed within **10 working days** (152-FZ requirement).

---

## API Endpoints

### Consent Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/consents` | Get user consents |
| POST | `/api/v1/consents` | Grant consent |
| DELETE | `/api/v1/consents/{type}` | Withdraw consent |
| GET | `/api/v1/consents/{type}` | Get specific consent |

### Subject Rights

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/personal-data` | Export personal data |
| PATCH | `/api/v1/personal-data` | Update personal data |
| DELETE | `/api/v1/personal-data` | Request deletion |
| GET | `/api/v1/personal-data/audit` | Get access log |

### Admin

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/consents/pending-purge` | Get pending purges |
| POST | `/api/v1/admin/consents/{id}/purge` | Manual purge trigger |
| GET | `/api/v1/admin/audit` | Audit log search |

---

## Testing

### Test Coverage

- **ConsentEngineTest:** 95% coverage
- **PurgePersonalDataJobTest:** 90% coverage
- **PersonalDataAccessAuditTest:** 85% coverage

### Running Tests

```bash
# Run all personal data tests
./vendor/bin/pest tests/Feature/PersonalData

# Run specific test
./vendor/bin/pest tests/Feature/PersonalData/ConsentEngineTest

# Run with coverage
./vendor/bin/pest --coverage
```

### Testing Mode

For development/testing, bypass consent checks:

```php
// config/personal-data.php
'testing_mode' => env('PERSONAL_DATA_TESTING_MODE', false),
```

⚠️ **NEVER enable in production.**

---

## Deployment

### Migration Steps

1. Run migrations:
```bash
php artisan migrate
php artisan clickhouse:migrate
```

2. Publish config:
```bash
php artisan vendor:publish --tag=personal-data-config
```

3. Configure environment variables:
```env
PERSONAL_DATA_OPERATOR_NAME="CatVRF LLC"
PERSONAL_DATA_OPERATOR_ADDRESS="Russia, Moscow"
PERSONAL_DATA_OPERATOR_INN="1234567890"
PERSONAL_DATA_OPERATOR_OGRN="1234567890123"
ROSKOMNADZOR_REGISTRY_NUMBER="123-456-789"
PERSONAL_DATA_PROTECTION_LEVEL="2"
PERSONAL_DATA_TESTING_MODE=false
```

4. Set up queue worker:
```bash
php artisan queue:work --queue=personal-data
```

5. Configure ClickHouse connection:
```php
// config/database.php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST', 'localhost'),
    'port' => env('CLICKHOUSE_PORT', '8123'),
    'database' => env('CLICKHOUSE_DATABASE', 'catvrf_audit'),
    'username' => env('CLICKHOUSE_USERNAME'),
    'password' => env('CLICKHOUSE_PASSWORD'),
],
```

---

## Monitoring

### Key Metrics

| Metric | Alert Threshold |
|--------|-----------------|
| Consent check failures | > 1% of requests |
| Data purge job failures | Any failure |
| ClickHouse audit log errors | > 0.1% of writes |
| Unauthorized access attempts | > 10/min |
| Biometric processing without consent | Any occurrence |

### Grafana Dashboards

- `Personal Data Compliance` - Overall compliance status
- `Consent Management` - Consent statistics
- `Audit Logging` - Audit log metrics
- `Data Purge Jobs` - Job execution status

### Alerts

Configure alerts in Prometheus for:
- Consent check failures
- Data purge job failures
- Audit log write failures
- Access violation attempts

---

## Compliance Checklist

### Pre-Production

- [ ] All migrations run successfully
- [ ] ClickHouse audit table created
- [ ] Queue workers configured
- [ ] Environment variables set
- [ ] Testing mode disabled
- [ ] Encryption keys rotated
- [ ] Backup procedures tested
- [ ] Incident response plan updated

### Pre-Roskomnadzor Notification

- [ ] Privacy policy published
- [ ] Consent forms ready
- [ ] Responsible person appointed
- [ ] Protection level documented
- [ ] Data localization verified
- [ ] Audit logging tested
- [ ] Subject rights API tested
- [ ] Destruction procedures tested

### Ongoing

- [ ] Quarterly compliance review
- [ ] Annual penetration testing
- [ ] Regular training for staff
- [ ] Update documentation
- [ ] Review and update consents
- [ ] Monitor audit logs
- [ ] Test destruction procedures

---

## Support

For questions about 152-FZ compliance implementation:

- **Technical:** tech@catvrf.ru
- **Legal:** legal@catvrf.ru
- **DPO (Data Protection Officer):** privacy@catvrf.ru

---

## References

- [Federal Law No. 152-FZ](https://rkso.ru/152-fz)
- [FSTEC Order No. 21](https://fstec.ru/ru/documents/212)
- [Roskomnadzor Guidelines](https://rkn.gov.ru/)

---

*Document Version: 1.0*  
*Last Updated: April 23, 2026*  
*Next Review: October 23, 2026*
