# Production Guide: 152-FZ Compliance for CatVRF

## Purpose

This guide provides step-by-step instructions for deploying and maintaining 152-FZ compliance in production for CatVRF medical marketplace.

**Target Audience:** DevOps Engineers, Security Team, Compliance Officers  
**Version:** 1.0  
**Last Updated:** April 23, 2026

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Roskomnadzor Notification](#roskomnadzor-notification)
3. [Infrastructure Setup](#infrastructure-setup)
4. [Configuration](#configuration)
5. [Deployment](#deployment)
6. [Post-Deployment Verification](#post-deployment-verification)
7. [Ongoing Operations](#ongoing-operations)
8. [Incident Response](#incident-response)
9. [Quarterly Review](#quarterly-review)

---

## Pre-Deployment Checklist

### Legal Documentation

- [ ] **Privacy Policy** published at `https://catvrf.ru/privacy`
- [ ] **Consent Forms** available in web and mobile apps
- [ ] **Order for Responsible Person** signed and filed
- [ ] **Protection Level Document** (Уровень защищённости ИСПДн) prepared
- [ ] **Data Processing Agreement** with third parties signed
- [ ] **Employee NDA** with data protection clauses signed

### Technical Requirements

- [ ] All databases hosted in Russia (verified)
- [ ] Encryption at rest enabled (AES-256-GCM)
- [ ] TLS 1.3 for all endpoints
- [ ] ClickHouse audit logging configured
- [ ] Queue workers for purge jobs configured
- [ ] Backup procedures tested
- [ ] Disaster recovery plan documented

### Testing

- [ ] All Pest tests passing (>95% coverage)
- [ ] Integration tests for consent flows passing
- [ ] Load testing for audit logging (10k RPS)
- [ ] Failover testing for ClickHouse fallback
- [ ] Penetration testing completed
- [ ] Security review completed

---

## Roskomnadzor Notification

### Step 1: Register in System

1. Go to [Roskomnadzor Portal](https://rsoc.rkn.gov.ru/)
2. Register operator account
3. Obtain digital signature (UKEDS) for signing

### Step 2: Prepare Notification Data

Required information:

```yaml
operator:
  name: "CatVRF LLC"
  inn: "1234567890"
  ogrn: "1234567890123"
  address: "Russia, Moscow, [full address]"
  contact: "privacy@catvrf.ru"
  
data_categories:
  - "Общие персональные данные"
  - "Биометрические персональные данные"
  - "Специальные категории персональных данных"
  
purposes:
  - "Предоставление медицинских услуг"
  - "Аутентификация и верификация"
  - "Защита от мошенничества"
  
subjects:
  citizens: "RF"
  volume: ">100,000"
  
protection_level: "Уровень 2"
data_localization: "RF"
cross_border: "Нет"
```

### Step 3: Submit Notification

1. Log in to Roskomnadzor portal
2. Fill out notification form
3. Upload required documents:
   - Privacy Policy
   - Order for Responsible Person
   - Protection Level Document
4. Sign with UKEDS
5. Submit

### Step 4: Receive Registry Number

After submission, you'll receive:
- Registry number (e.g., `123-456-789`)
- Confirmation email
- Entry in public registry

**Save this number in `.env`:**
```env
ROSKOMNADZOR_REGISTRY_NUMBER="123-456-789"
ROSKOMNADZOR_NOTIFICATION_SUBMITTED=true
ROSKOMNADZOR_NOTIFICATION_DATE="2026-04-23"
```

### Step 5: Update Notification

Notify Roskomnadzor within 30 days of:
- Changes in data categories
- Changes in processing purposes
- Changes in protection level
- Change of responsible person
- Data breaches

---

## Infrastructure Setup

### Database Configuration

**PostgreSQL (Primary - Moscow):**
```yaml
region: Moscow
version: 15
encryption: AES-256-GCM
backup: Daily, retained 30 days
replication: Synchronous to St. Petersburg
```

**ClickHouse (Audit - Moscow):**
```yaml
region: Moscow
version: 23
retention: 7 years
partitioning: Monthly
compression: ZSTD
```

**Redis (Session Cache - Moscow):**
```yaml
region: Moscow
version: 7
encryption: TLS
persistence: AOF
```

### Storage Configuration

**S3-Compatible Storage (Moscow):**
```yaml
provider: "Selectel" or "Yandex Object Storage"
region: ru-msk
encryption: SSE-S3
versioning: Enabled
lifecycle: Delete after 7 years
```

### Network Configuration

```
Internet
    │
    ▼
┌─────────────────┐
│  Load Balancer  │ (TLS 1.3, HSTS)
└─────────────────┘
    │
    ▼
┌─────────────────┐
│   WAF / DDoS    │ (Selectel Shield)
└─────────────────┘
    │
    ▼
┌─────────────────────────────────────┐
│         Application Servers         │
│  ┌──────────┐  ┌──────────┐        │
│  │  Node 1  │  │  Node 2  │  ...   │
│  └──────────┘  └──────────┘        │
└─────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────┐
│         Database Layer              │
│  ┌──────────┐  ┌──────────┐        │
│  │PostgreSQL│  │ClickHouse│        │
│  │ (Primary)│  │  (Audit) │        │
│  └──────────┘  └──────────┘        │
└─────────────────────────────────────┘
```

---

## Configuration

### Environment Variables

```env
# Operator Information
PERSONAL_DATA_OPERATOR_NAME="CatVRF LLC"
PERSONAL_DATA_OPERATOR_ADDRESS="Russia, Moscow, 123456, ул. Примерная, д. 1"
PERSONAL_DATA_OPERATOR_CONTACT="privacy@catvrf.ru"
PERSONAL_DATA_OPERATOR_INN="1234567890"
PERSONAL_DATA_OPERATOR_OGRN="1234567890123"

# Roskomnadzor
ROSKOMNADZOR_REGISTRY_NUMBER="123-456-789"
ROSKOMNADZOR_NOTIFICATION_SUBMITTED=true
ROSKOMNADZOR_NOTIFICATION_DATE="2026-04-23"
ROSKOMNADZOR_BREACH_NOTIFICATION_REQUIRED=true
ROSKOMNADZOR_BREACH_NOTIFICATION_HOURS=72

# Localization
PERSONAL_DATA_LOCALIZATION_ENABLED=true
PERSONAL_DATA_REGION="RU"
PERSONAL_DATA_DB_LOCATION="Russia"
PERSONAL_DATA_BACKUP_LOCATION="Russia"
PERSONAL_DATA_CROSS_BORDER_ALLOWED=false

# Protection Level
PERSONAL_DATA_PROTECTION_LEVEL="2"

# Encryption
ENCRYPTION_AT_REST_ENABLED=true
ENCRYPTION_ALGORITHM="AES-256-GCM"
KEY_ROTATION_DAYS=90
TLS_VERSION="1.3"
HSTS_ENABLED=true
HSTS_MAX_AGE=31536000

# Consent
CONSENT_VERSION="1.0"
REQUIRE_EXPLICIT_CONSENT=true
BIOMETRIC_REQUIRES_WRITTEN=true
ALLOW_PARTIAL_CONSENT=true
PERSONAL_DATA_TESTING_MODE=false  # CRITICAL: MUST BE FALSE IN PROD

# Access Control
JIT_ACCESS_ENABLED=true
JIT_REQUIRES_PASSKEY=true
JIT_REQUIRES_2FA=true
JIT_ACCESS_DURATION_MINUTES=60
AUDIT_ALL_ACCESS=true

# Audit
PERSONAL_DATA_AUDIT_ENABLED=true
AUDIT_STORAGE="clickhouse"
AUDIT_RETENTION_DAYS=2555

# Destruction
DESTRUCTION_DAYS_AFTER_WITHDRAWAL=30
DESTRUCTION_METHOD="overwrite"
GENERATE_DESTRUCTION_CERTIFICATE=true
NOTIFY_USER_ON_DESTRUCTION=true

# Responsible Person
RESPONSIBLE_PERSON_NAME="Иванов Иван Иванович"
RESPONSIBLE_PERSON_POSITION="Chief Information Security Officer"
RESPONSIBLE_PERSON_EMAIL="ciso@catvrf.ru"
RESPONSIBLE_PERSON_PHONE="+79001234567"
```

### ClickHouse Configuration

```php
// config/database.php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST', 'clickhouse.internal'),
    'port' => env('CLICKHOUSE_PORT', '8123'),
    'database' => env('CLICKHOUSE_DATABASE', 'catvrf_audit'),
    'username' => env('CLICKHOUSE_USERNAME', 'catvrf_audit'),
    'password' => env('CLICKHOUSE_PASSWORD'),
    'options' => [
        'https' => true,
        'connect_timeout' => 10,
        'read_timeout' => 30,
        'write_timeout' => 30,
    ],
],
```

---

## Deployment

### 1. Database Migration

```bash
# Run PostgreSQL migrations
php artisan migrate --force

# Run ClickHouse migrations
php artisan clickhouse:migrate --force
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=personal-data-config
```

### 3. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 4. Start Queue Workers

```bash
# Using Supervisor
php artisan queue:work --queue=personal-data --sleep=3 --tries=3 --timeout=3600

# Or using systemd
systemctl start catvrf-queue-personal-data
```

### 5. Verify Configuration

```bash
# Check configuration
php artisan config:show personal-data

# Check ClickHouse connection
php artisan clickhouse:test

# Run health check
php artisan health:check
```

### 6. Smoke Tests

```bash
# Test consent API
curl -X POST https://api.catvrf.ru/api/v1/consents \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"consent_type":"registration"}'

# Test audit logging
curl -X GET https://api.catvrf.ru/api/v1/personal-data \
  -H "Authorization: Bearer $TOKEN"

# Test JIT access
curl -X POST https://api.catvrf.ru/api/v1/admin/jit-access \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

---

## Post-Deployment Verification

### Checklist

- [ ] All migrations successful
- [ ] ClickHouse audit table receiving data
- [ ] Consent API responding correctly
- [ ] Queue workers processing jobs
- [ ] Encryption verified (check DB for encrypted values)
- [ ] TLS 1.3 enabled on all endpoints
- [ ] HSTS headers present
- [ ] Audit logs visible in Grafana
- [ ] Alert rules configured
- [ ] Backup jobs running
- [ ] Documentation updated

### Verification Commands

```bash
# Check consent records
php artisan tinker
>>> App\Models\UserConsent::count()

# Check audit logs
php artisan tinker
>>> DB::connection('clickhouse')->table('personal_data_audit')->count()

# Check queue workers
php artisan queue:status

# Check encryption
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->email; // Should be encrypted
```

---

## Ongoing Operations

### Daily Tasks

- [ ] Review audit logs for anomalies
- [ ] Check queue worker status
- [ ] Monitor alert dashboards
- [ ] Verify backup completion

### Weekly Tasks

- [ ] Review consent statistics
- [ ] Check for pending purges
- [ ] Review access logs
- [ ] Update threat intelligence

### Monthly Tasks

- [ ] Review and rotate encryption keys
- [ ] Test disaster recovery
- [ ] Review and update documentation
- [ ] Conduct security awareness training

### Quarterly Tasks

- [ ] Full compliance review (see Quarterly Review section)
- [ ] Penetration testing
- [ ] Third-party audit (if required)
- [ ] Update Roskomnadzor notification if needed

---

## Incident Response

### Data Breach Response

**Timeline (152-FZ requirement: 72 hours):**

| Time | Action |
|------|--------|
| 0-1h | Detect and contain breach |
| 1-4h | Assess impact and scope |
| 4-24h | Notify internal stakeholders |
| 24-48h | Prepare notification for Roskomnadzor |
| 48-72h | Submit notification to Roskomnadzor |
| 72h+ | Notify affected individuals |

### Notification Template for Roskomnadzor

```yaml
breach:
  date: "2026-04-23"
  detected_at: "2026-04-23T10:00:00Z"
  contained_at: "2026-04-23T12:00:00Z"
  
affected:
  users: 150
  data_categories:
    - "ФИО"
    - "Email"
    - "Телефон"
  
impact:
  risk_level: "Средний"
  potential_harm: "Фишинг, спам"
  
mitigation:
  actions_taken:
    - "Заблокирован доступ"
    - "Уведомлены пострадавшие"
    - "Усилен мониторинг"
  
notification:
  roskomnadzor_submitted: true
  submitted_at: "2026-04-26T10:00:00Z"
  registry_number: "123-456-789"
```

### Communication Channels

- **Internal:** Slack #security-incidents
- **Legal:** legal@catvrf.ru
- **Roskomnadzor:** rsoc@rkn.gov.ru
- **Affected Users:** Email + SMS

---

## Quarterly Review

### Review Checklist

#### 1. Legal Compliance

- [ ] Privacy policy reviewed and updated
- [ ] Consent forms reviewed and updated
- [ ] Responsible person still in role
- [ ] Roskomnadzor notification still accurate
- [ ] No new legal requirements

#### 2. Technical Compliance

- [ ] All databases still in Russia
- [ ] Encryption still enabled
- [ ] TLS version still current
- [ ] Audit logging functioning
- [ ] Backup procedures tested
- [ ] No security vulnerabilities

#### 3. Operational Metrics

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Consent rate | >90% | ___% | ___ |
| Data purge success rate | 100% | ___% | ___ |
| Audit log success rate | >99.9% | ___% | ___ |
| Unauthorized access attempts | <10/day | ___ | ___ |
| Data breach incidents | 0 | ___ | ___ |

#### 4. Documentation

- [ ] Architecture diagrams updated
- [ ] SOPs reviewed
- [ ] Incident response plan updated
- [ ] Training materials updated

#### 5. Third-Party Review

- [ ] Review third-party data processors
- [ ] Verify DPAs are current
- [ ] Review sub-processor changes

### Review Report Template

```markdown
# Quarterly 152-FZ Compliance Review
## Q2 2026 (April - June 2026)

**Review Date:** June 30, 2026  
**Reviewer:** [Name]  
**Next Review:** September 30, 2026

## Executive Summary
[Summary of compliance status]

## Legal Compliance
[Details]

## Technical Compliance
[Details]

## Operational Metrics
[Table with metrics]

## Findings and Recommendations
[List of findings and actions]

## Action Items
[Owner and due dates]

## Sign-off
[Approvals]
```

---

## Emergency Contacts

| Role | Name | Email | Phone |
|------|------|-------|-------|
| CISO | [Name] | ciso@catvrf.ru | +79001234567 |
| DPO | [Name] | privacy@catvrf.ru | +79001234568 |
| Legal Counsel | [Name] | legal@catvrf.ru | +79001234569 |
| DevOps Lead | [Name] | devops@catvrf.ru | +79001234570 |
| On-Call Engineer | [Name] | oncall@catvrf.ru | +79001234571 |

---

## Appendix

### Useful Commands

```bash
# Check consent statistics
php artisan tinker
>>> App\Models\UserConsent::groupBy('consent_type')->selectRaw('consent_type, count(*) as count')->get()

# Check pending purges
php artisan tinker
>>> App\Models\UserConsent::where('status', 'withdrawn')->whereNull('data_purged_at')->where('data_purge_scheduled_at', '<', now())->get()

# Manual purge trigger
php artisan tinker
>>> App\Jobs\PersonalData\PurgePersonalDataJob::dispatch($consentId)

# Check audit log volume
php artisan tinker
>>> DB::connection('clickhouse')->table('personal_data_audit')->where('timestamp', '>=', now()->subDay())->count()
```

### Resources

- [152-FZ Full Text](https://rkso.ru/152-fz)
- [FSTEC Guidelines](https://fstec.ru/ru/documents)
- [Roskomnadzor Portal](https://rsoc.rkn.gov.ru/)
- [CatVRF Internal Wiki](https://wiki.catvrf.ru/compliance)

---

*Document Version: 1.0*  
*Last Updated: April 23, 2026*  
*Next Review: July 23, 2026*
