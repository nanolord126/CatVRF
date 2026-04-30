# Database Security Fortress 2026

**CatVRF Production-Ready Multi-Layer Database Security System**

## Overview

This document describes the comprehensive database security fortress implemented for CatVRF, designed to protect against SQL injection, data exfiltration, cross-tenant leakage, and insider threats. The system follows production-grade security standards comparable to Ozon/Alibaba 2026.

## Architecture

### 1. Multi-Tenancy Isolation

**Implementation:** `stancl/tenancy` with database-per-tenant isolation

- **Central Database:** Stores tenants, users, unique_contacts (hashed)
- **Tenant Databases:** Separate database per tenant with encrypted credentials
- **Global Scopes:** Automatic tenant_id filtering on all models
- **Row-Level Security:** PostgreSQL RLS policies (optional for high-security tenants)

**Key Components:**
- `App\Models\Scopes\TenantScope` - Global tenant isolation
- `App\Http\Middleware\FilamentTenantScope` - Admin panel tenant enforcement
- `config/tenancy.php` - Tenancy configuration with security settings

### 2. SQL Injection Prevention

**Protection Layers:**

1. **Eloquent/Query Builder:** Prepared statements by default
2. **Query Monitoring:** `DatabaseProtectionService::monitorQuery()`
3. **Pattern Detection:** Regex-based suspicious query detection
4. **Raw Query Blocking:** All raw queries require explicit approval

**Blocked Patterns:**
- `OR 1=1` injections
- `UNION ALL SELECT` attacks
- `DROP/DELETE TABLE` attempts
- SQL comments (`--`, `/* */`)
- Chained commands (`; DROP`)

### 3. Encryption at Rest

**Column-Level Encryption:**

**User Model:**
- `email` - encrypted
- `phone` - encrypted
- `inn` - encrypted
- `first_name`, `last_name`, `middle_name` - encrypted
- `two_factor_secret` - encrypted
- `two_factor_recovery_codes` - encrypted
- `device_fingerprint` - encrypted

**Tenant Model:**
- `inn`, `kpp`, `ogr` - encrypted
- `legal_address`, `actual_address` - encrypted
- `phone`, `email` - encrypted

**Encryption Method:**
- Laravel's `encrypted` cast (AES-256-CBC)
- Additional pepper via `DB_ENCRYPTION_PEPPER` env variable
- Custom pepper in `DatabaseProtectionService::encryptWithPepper()`

### 4. Data Masking

**Masked Accessors:**
- `User::masked_email` - `u***@example.com`
- `User::masked_phone` - `+7 (***) ***-**-67`
- `User::masked_inn` - `********9012`
- `User::masked_name` - `И*** В***`

**Configuration:** `config/client-protection.php`

### 5. Data Exfiltration Prevention

**Rate Limiting:**
- Per-user: 60 req/min, 1000 req/hour
- Per-tenant: 300 req/min, 5000 req/hour
- Per-IP: 100 req/min, 2000 req/hour

**Result Limits:**
- Customer: 50 records per query
- Staff: 200 records per query
- Super Admin: 1000 records per query

**Export Controls:**
- `ExportGuardMiddleware` - Blocks unauthorized exports
- Export limits by role
- 2FA required for exports > 100 records
- Super-admin approval for exports > 500 records
- Anonymization by default

### 6. Hunting Detection

**Detection Mechanisms:**

1. **Pattern Analysis:** LIKE queries on contact fields
2. **Frequency Analysis:** High query velocity
3. **Result Size:** Large result sets
4. **Time Anomaly:** Unusual access hours (22:00-06:00)

**Scoring System:**
- Frequency: 0.3 points
- Pattern matching: 0.4 points
- Result size: 0.2 points
- Time anomaly: 0.1 points
- **Threshold:** 0.7

**Actions:**
- Score < 0.7: Log for monitoring
- Score ≥ 0.7: Trigger 2-hour cooldown

**Components:**
- `ClientDataScope` - Real-time hunting detection
- `HuntingDetectionJob` - Queued deep analysis
- `ClientDataProtectionService::detectHuntingPattern()`

### 7. Cross-Tenant Access Control

**Protection:**
- Automatic tenant_id filtering via `TenantScope`
- Explicit ownership checks in policies
- Cross-tenant attempt logging
- Block by default, allow only for super-admins

**Components:**
- `ClientDataProtectionService::checkCrossTenantAccess()`
- ClickHouse `ch_cross_tenant_attempts` table

### 8. Immutable Audit Logging

**ClickHouse Tables:**

1. **ch_security_audit** - All security events
2. **ch_query_audit** - Query monitoring
3. **ch_hunting_detection** - Hunting metrics
4. **ch_cross_tenant_attempts** - Cross-tenant attempts

**Materialized Views:**
- `ch_security_events_hourly` - Hourly aggregation
- `ch_hunting_summary_daily` - Daily hunting summary
- `ch_cross_tenant_daily` - Daily cross-tenant summary

**Retention:**
- Security audit: 90 days
- Query audit: 30 days
- Hunting detection: 90 days
- Cross-tenant attempts: 180 days

**Schema:** `database/clickhouse/security_audit.sql`

### 9. 152-ФЗ Compliance

**Data Retention:**
- Audit logs: 30 days
- User data: Until consent withdrawal
- Consent-withdrawn data: Anonymized after retention period

**Cleanup Jobs:**
- `CleanupExpiredDataJob` - Automated data cleanup
- Runs daily at 02:00
- Anonymizes instead of hard delete

**Configuration:**
- `COMPLIANCE_RETENTION_DAYS` env variable
- `COMPLIANCE_AUTO_DELETE` env variable

### 10. Backup Encryption

**Components:**
- `EncryptedBackupJob` - Encrypted database backups
- Per-tenant backup files
- AES-256-GCM encryption
- S3 or local storage

**Features:**
- Automatic backup scheduling
- 30-day retention
- Separate central and tenant backups
- Encryption key rotation support

## Configuration

### Environment Variables

```bash
# Database Encryption
DB_ENCRYPTION_ENABLED=true
DB_ENCRYPTION_PEPPER=<your-secret-pepper>
DB_BACKUP_ENCRYPTION=true
DB_ENCRYPTION_ALGORITHM=aes-256-gcm

# Database Security
DB_SSL_MODE=require
DB_STATEMENT_TIMEOUT=30
DB_IDLE_IN_TRANSACTION_TIMEOUT=60
DB_LOG_SLOW_QUERIES=true
DB_SLOW_QUERY_THRESHOLD=1000

# Client Protection
CLIENT_PROTECTION_RATE_LIMIT_ENABLED=true
CLIENT_PROTECTION_MAX_REQUESTS_PER_MINUTE=60
CLIENT_PROTECTION_CUSTOMER_LIMIT=50
CLIENT_PROTECTION_STAFF_LIMIT=200
CLIENT_PROTECTION_SUPER_ADMIN_LIMIT=1000

# Hunting Detection
HUNTING_DETECTION_ENABLED=true
HUNTING_DETECTION_SCORE_THRESHOLD=0.7
HUNTING_DETECTION_PATTERN_THRESHOLD=3

# Compliance
COMPLIANCE_RETENTION_DAYS=30
COMPLIANCE_AUTO_DELETE=true
COMPLIANCE_CLEANUP_SCHEDULE="0 2 * * *"

# ClickHouse Audit
CLICKHOUSE_AUDIT_ENABLED=true
CLICKHOUSE_AUDIT_TABLE=ch_security_audit
```

### Config Files

1. **config/client-protection.php** - All client protection settings
2. **config/database.php** - Encryption and security settings
3. **config/tenancy.php** - Tenancy security configuration

## Services

### DatabaseProtectionService

**Location:** `app/Services/Security/DatabaseProtectionService.php`

**Methods:**
- `encryptWithPepper()` - Encrypt with additional pepper
- `decryptWithPepper()` - Decrypt with pepper validation
- `hashContact()` - Hash for unique_contacts table
- `monitorQuery()` - Monitor for suspicious patterns
- `maskData()` - Mask sensitive data
- `validateQuery()` - Validate query safety

### ClientDataProtectionService

**Location:** `app/Services/Security/ClientDataProtectionService.php`

**Methods:**
- `checkAccess()` - Check user access permissions
- `maskCollection()` - Mask collection data
- `detectHuntingPattern()` - Detect hunting patterns
- `validateExport()` - Validate export requests
- `logDataAccess()` - Log data access
- `enforceResultLimit()` - Enforce result limits
- `checkCrossTenantAccess()` - Check cross-tenant access

### CooldownService

**Location:** `app/Services/Security/CooldownService.php`

**Integration:**
- Automatically triggered on hunting detection
- Prevents further access during cooldown period
- 2-hour default for hunting detection
- 72-hour for data export violations

## Middleware

### ExportGuardMiddleware

**Location:** `app/Http/Middleware/ExportGuardMiddleware.php`

**Features:**
- Blocks unauthorized export requests
- Enforces export limits
- Requires super-admin approval for large exports
- Logs all export attempts

**Registration:**
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'export.guard'])->group(function () {
    Route::get('/users/export', [UserController::class, 'export']);
});
```

## Jobs

### HuntingDetectionJob

**Location:** `app/Jobs/HuntingDetectionJob.php`

**Purpose:** Queued deep analysis of query patterns

**Scoring Factors:**
- Query frequency
- Pattern matching
- Result size
- Time anomaly

**Queue:** `security`

### CleanupExpiredDataJob

**Location:** `app/Jobs/CleanupExpiredDataJob.php`

**Purpose:** 152-ФЗ compliance cleanup

**Cleanup:**
- Audit logs (30 days)
- Temporary tokens
- Expired cooldowns
- Consent-withdrawn data (anonymized)

**Queue:** `maintenance`

**Schedule:** Daily at 02:00

### EncryptedBackupJob

**Location:** `app/Jobs/EncryptedBackupJob.php`

**Purpose:** Encrypted database backups

**Features:**
- Per-tenant backups
- AES-256-GCM encryption
- S3 or local storage
- 30-day retention

**Queue:** `backups`

## Models

### User Model Updates

**Encrypted Casts:**
```php
protected $casts = [
    'email' => 'encrypted',
    'phone' => 'encrypted',
    'inn' => 'encrypted',
    'first_name' => 'encrypted',
    'last_name' => 'encrypted',
    'middle_name' => 'encrypted',
    'two_factor_secret' => 'encrypted',
    'two_factor_recovery_codes' => 'encrypted',
    'device_fingerprint' => 'encrypted',
];
```

**Masked Accessors:**
- `masked_email`
- `masked_phone`
- `masked_inn`
- `masked_name`

**Global Scopes:**
- `TenantScope`
- `ClientDataScope`

### Tenant Model Updates

**Encrypted Casts:**
```php
protected $casts = [
    'inn' => 'encrypted',
    'kpp' => 'encrypted',
    'ogrn' => 'encrypted',
    'legal_address' => 'encrypted',
    'actual_address' => 'encrypted',
    'phone' => 'encrypted',
    'email' => 'encrypted',
];
```

## Testing

### Test Suite

**Location:** `tests/Feature/Security/DatabaseSecurityFortressTest.php`

**Coverage:**
- Data encryption at rest
- Tenant isolation
- SQL injection prevention
- Hunting detection
- Data exfiltration prevention
- Cross-tenant access control
- Rate limiting
- Data masking

**Run Tests:**
```bash
./vendor/bin/pest tests/Feature/Security/DatabaseSecurityFortressTest.php
```

## Monitoring & Alerts

### ClickHouse Queries

**High-Risk Security Events (Last Hour):**
```sql
SELECT 
    event_type,
    severity,
    COUNT(*) AS count,
    uniq(user_id) AS unique_users
FROM ch_security_audit
WHERE created_at >= now() - INTERVAL 1 HOUR
  AND severity >= 3
GROUP BY event_type, severity;
```

**Hunting Detection Summary (Today):**
```sql
SELECT 
    tenant_id,
    total_detections,
    cooldowns_triggered,
    avg_hunting_score
FROM ch_hunting_summary_daily
WHERE date = today();
```

**Cross-Tenant Attempts (Last 24 Hours):**
```sql
SELECT 
    user_tenant_id,
    COUNT(*) AS attempts,
    COUNTIf(blocked = true) AS blocked_attempts
FROM ch_cross_tenant_attempts
WHERE created_at >= now() - INTERVAL 24 HOUR
GROUP BY user_tenant_id;
```

### Alert Channels

**Configuration:** `config/client-protection.php`

**Thresholds:**
- High hunting score: 0.8
- Mass export attempt: 1000 records
- Cross-tenant surge: 10 attempts

**Channels:**
- Telegram (optional)
- Email (default)
- Slack (optional)

## Production Hardening

### Database Users

**Principle of Least Privilege:**

1. **Application User:**
   - Read-only access to central DB
   - Full access to tenant DB
   - No SUPER privileges

2. **Migration User:**
   - DDL privileges only
   - Used only during deployments

3. **Backup User:**
   - SELECT, LOCK TABLES privileges
   - Used for backups only

4. **Audit User:**
   - INSERT only to audit tables
   - Used for logging only

### Disk Encryption

**Infrastructure-Level:**
- AWS RDS: Enable encryption at rest
- Self-hosted: LUKS + filesystem encryption
- Backups: Encrypt with AES-256-GCM

### Key Rotation

**Encryption Keys:**
- Rotate `APP_KEY` quarterly
- Rotate `DB_ENCRYPTION_PEPPER` monthly
- Rotate `BACKUP_ENCRYPTION_KEY` monthly

**Rotation Process:**
1. Generate new key
2. Update environment variables
3. Re-encrypt sensitive data (gradual migration)
4. Test decryption with new key
5. Remove old key after validation

### Network Security

**Database Access:**
- VPC peering for application-to-DB
- SSL/TLS required for all connections
- IP whitelist for direct access
- Bastion host for administrative access

## Deployment Checklist

### Pre-Deployment

- [ ] Set `DB_ENCRYPTION_PEPPER` environment variable
- [ ] Configure `BACKUP_ENCRYPTION_KEY`
- [ ] Enable ClickHouse audit logging
- [ ] Set up S3 for encrypted backups
- [ ] Configure alert channels
- [ ] Run migration for unique_contacts table
- [ ] Run ClickHouse schema setup
- [ ] Test encryption/decryption
- [ ] Test hunting detection
- [ ] Run security test suite

### Post-Deployment

- [ ] Verify tenant isolation
- [ ] Test SQL injection prevention
- [ ] Test export controls
- [ ] Verify audit logging
- [ ] Test backup encryption
- [ ] Monitor ClickHouse queries
- [ ] Verify alert delivery
- [ ] Run performance tests
- [ ] Document key rotation schedule

## Troubleshooting

### Encryption Issues

**Problem:** Data cannot be decrypted

**Solution:**
1. Verify `APP_KEY` matches original
2. Check `DB_ENCRYPTION_PEPPER` is set
3. Ensure Laravel version compatibility
4. Test with simple string first

### Hunting Detection False Positives

**Problem:** Legitimate queries blocked

**Solution:**
1. Adjust `HUNTING_DETECTION_SCORE_THRESHOLD`
2. Review `HUNTING_DETECTION_PATTERN_THRESHOLD`
3. Add user to allowlist if necessary
4. Monitor ClickHouse for patterns

### Performance Issues

**Problem:** Slow queries due to encryption

**Solution:**
1. Add database indexes on encrypted columns (not recommended)
2. Use hashed columns for searches
3. Cache decrypted data in Redis
4. Consider column-level encryption only

### Backup Failures

**Problem:** Backup encryption fails

**Solution:**
1. Verify `BACKUP_ENCRYPTION_KEY` is set
2. Check disk space
3. Verify mysqldump/pg_dump availability
4. Test encryption key length (must be 32 bytes for AES-256)

## References

- [Laravel Encryption](https://laravel.com/docs/encryption)
- [Stancl Tenancy](https://tenancyforlaravel.com/)
- [ClickHouse Documentation](https://clickhouse.com/docs)
- [152-ФЗ (Russian Federal Law)](https://consultant.ru/document/cons_doc_LAW_27870/)
- [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)

## Version History

- **v1.0** (2026-04-23): Initial implementation
  - Multi-tenancy isolation
  - SQL injection prevention
  - Column-level encryption
  - Hunting detection
  - Data exfiltration prevention
  - Immutable audit logging
  - 152-ФZ compliance

## Support

For issues or questions:
- Create issue in GitHub repository
- Contact security team: security@catvrf.ru
- Emergency: admin@catvrf.ru
