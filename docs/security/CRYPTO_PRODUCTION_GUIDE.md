# Post-Quantum Cryptography Production Guide
## Key Rotation, Pepper Management, and Migration Strategy

**Version:** 1.0  
**Date:** 23.04.2026  
**Status:** PRODUCTION MANDATORY  
**Compliance:** ФСТЭК №21, 152-ФЗ

---

## Overview

This guide provides production deployment instructions for the post-quantum resistant cryptographic implementation in CatVRF, including key rotation procedures, pepper management, and data migration strategies.

**Target Audience:** DevOps Engineers, Security Engineers, Database Administrators

---

## 1. Environment Configuration

### 1.1 Required Environment Variables

Add the following to your `.env` file or secrets manager:

```bash
# Cryptography Configuration
CRYPTO_PEPPER=your-32-byte-pepper-here-base64-encoded
CRYPTO_PASSWORD_ALGORITHM=argon2id
CRYPTO_AES256_KEY=your-32-byte-aes256-key-here-base64-encoded

# Migration Configuration
CRYPTO_MIGRATION_DRY_RUN=true
CRYPTO_MIGRATION_BATCH_SIZE=1000
CRYPTO_QUANTUM_RISK_LEVEL=low
```

### 1.2 Secrets Manager Setup

**AWS Secrets Manager:**
```bash
aws secretsmanager create-secret \
  --name catvrf/crypto/pepper \
  --secret-string "$(openssl rand -base64 32)"

aws secretsmanager create-secret \
  --name catvrf/crypto/aes256_key \
  --secret-string "$(openssl rand -base64 32)"
```

**Azure Key Vault:**
```bash
az keyvault secret set \
  --vault-name catvrf-kv \
  --name crypto-pepper \
  --value "$(openssl rand -base64 32)"

az keyvault secret set \
  --vault-name catvrf-kv \
  --name crypto-aes256-key \
  --value "$(openssl rand -base64 32)"
```

**HashiCorp Vault:**
```bash
vault kv put secret/catvrf/crypto \
  pepper="$(openssl rand -base64 32)" \
  aes256_key="$(openssl rand -base64 32)"
```

---

## 2. Key Generation

### 2.1 Generate Pepper

The pepper is a 32-byte secret used in SHA-256 hashing to prevent rainbow table attacks.

```bash
# Generate 32-byte pepper (base64 encoded)
openssl rand -base64 32
```

**Example output:**
```
dGhpcyBpcyBhIDMyLWJ5dGUgcGVwcGVyIGZvciBzaGEyNTYgaGFzaGluZw==
```

### 2.2 Generate AES-256 Key

The AES-256 key is used for encrypting personal data at rest.

```bash
# Generate 32-byte AES-256 key (base64 encoded)
openssl rand -base64 32
```

**Example output:**
```
YW5vdGhlciAzMi1ieXRlIGtleSBmb3IgYWVzLTI1Ni1nY20gZW5jcnlwdGlvbg==
```

### 2.3 Validate Keys

Ensure keys are exactly 32 bytes when decoded:

```bash
# Validate pepper
echo "YOUR_PEPPER_BASE64" | base64 -d | wc -c
# Should output: 32

# Validate AES-256 key
echo "YOUR_AES256_KEY_BASE64" | base64 -d | wc -c
# Should output: 32
```

---

## 3. Database Migration

### 3.1 Pre-Migration Checklist

- [ ] Backup production database
- [ ] Generate and store pepper in secrets manager
- [ ] Generate and store AES-256 key in secrets manager
- [ ] Review migration job configuration
- [ ] Schedule maintenance window (2-4 hours for large datasets)
- [ ] Notify users of potential password reset requirement

### 3.2 Run Migrations

```bash
# Run database migrations
php artisan migrate

# Verify migrations completed successfully
php artisan migrate:status
```

**Expected migrations:**
- `2026_04_23_000002_add_password_salt_to_users_table`
- `2026_04_23_000003_add_migrated_at_to_unique_contacts_table`

### 3.3 Data Migration Job

The `MigrateHashesToSha256AndArgon2id` job migrates existing data to the new cryptographic scheme.

#### 3.3.1 Dry Run (Recommended First)

```bash
# Set dry run mode
CRYPTO_MIGRATION_DRY_RUN=true

# Dispatch migration job
php artisan queue:work --queue=default --tries=3 --timeout=300
```

#### 3.3.2 Production Migration

```bash
# Disable dry run
CRYPTO_MIGRATION_DRY_RUN=false

# Dispatch migration job
php artisan queue:work --queue=default --tries=3 --timeout=300
```

**Job Configuration:**
- Batch size: 1000 records per batch
- Timeout: 300 seconds per batch
- Retry: 3 attempts on failure
- Queues: Use dedicated migration queue for large datasets

#### 3.3.3 Monitor Migration

```bash
# Check migration progress
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### 3.4 Post-Migration Verification

```sql
-- Verify password_salt column is populated
SELECT COUNT(*) FROM users WHERE password_salt IS NULL;

-- Verify migrated_at column is populated for contacts
SELECT COUNT(*) FROM unique_contacts WHERE migrated_at IS NULL;

-- Both should return 0
```

---

## 4. Key Rotation Strategy

### 4.1 Rotation Schedule

| Key Type | Rotation Frequency | Trigger |
|----------|-------------------|---------|
| Pepper | Every 365 days | Scheduled rotation |
| AES-256 Key | Every 180 days | Scheduled rotation |
| HMAC Key | Every 90 days | Scheduled rotation |

### 4.2 Pepper Rotation Procedure

**Important:** Pepper rotation requires re-hashing all contacts. This is a major operation.

#### Step 1: Generate New Pepper

```bash
# Generate new pepper
NEW_PEPPER=$(openssl rand -base64 32)
```

#### Step 2: Update Configuration

```bash
# Update secrets manager
aws secretsmanager update-secret \
  --secret-id catvrf/crypto/pepper \
  --secret-string "$NEW_PEPPER"
```

#### Step 3: Re-hash Contacts

Since SHA-256 is one-way, you must re-register all contacts:

```php
// Create rotation job
php artisan make:job RotateContactPepper

// In the job:
// 1. Get all unique contacts
// 2. For each contact, re-hash with new pepper
// 3. Update unique_contacts table
// 4. Log operation
```

```bash
# Dispatch rotation job
php artisan queue:work --queue=default
```

#### Step 4: Verify Rotation

```sql
-- Verify all contacts have been re-hashed
SELECT COUNT(*) FROM unique_contacts WHERE migrated_at < NOW() - INTERVAL 1 DAY;
```

### 4.3 AES-256 Key Rotation Procedure

**Important:** AES-256 key rotation requires re-encrypting all PII.

#### Step 1: Generate New Key

```bash
# Generate new AES-256 key
NEW_KEY=$(openssl rand -base64 32)
```

#### Step 2: Update Configuration

```bash
# Update secrets manager
aws secretsmanager update-secret \
  --secret-id catvrf/crypto/aes256_key \
  --secret-string "$NEW_KEY"
```

#### Step 3: Re-encrypt PII

```php
// Create re-encryption job
php artisan make:job ReencryptPersonalData

// In the job:
// 1. Get all encrypted PII records
// 2. Decrypt with old key
// 3. Encrypt with new key
// 4. Update records
// 5. Log operation
```

```bash
# Dispatch re-encryption job
php artisan queue:work --queue=default
```

#### Step 4: Verify Rotation

```bash
# Test decryption with new key
php artisan tinker
>>> $crypto = app(App\Services\Security\CryptoService::class);
>>> $encrypted = 'v2:...'; // Get from database
>>> $decrypted = $crypto->aes256Decrypt($encrypted);
>>> // Should return original plaintext
```

### 4.4 Zero-Downtime Rotation

For zero-downtime rotation, use the versioning system:

1. **Dual-key period:** Support both old and new keys during rotation
2. **Gradual migration:** Migrate records incrementally
3. **Version check:** Check encryption version before decrypting
4. **Fallback:** Keep old key available for 7 days after rotation

```php
// In CryptoService, support version checking:
public function aes256Decrypt(string $encrypted): string
{
    if (str_starts_with($encrypted, 'v1:')) {
        // Decrypt with old key
        return $this->decryptWithOldKey($encrypted);
    }
    
    if (str_starts_with($encrypted, 'v2:')) {
        // Decrypt with new key
        return $this->decryptWithNewKey($encrypted);
    }
    
    throw new \RuntimeException('Unknown encryption version');
}
```

---

## 5. Pepper Management Best Practices

### 5.1 Storage Requirements

- **Never** commit pepper to version control
- **Always** store in secrets manager or HSM
- **Use** environment variables only for local development
- **Rotate** pepper every 365 days
- **Monitor** pepper access logs

### 5.2 Access Control

**IAM Policy Example (AWS):**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "secretsmanager:GetSecretValue",
        "secretsmanager:DescribeSecret"
      ],
      "Resource": "arn:aws:secretsmanager:region:account:secret:catvrf/crypto/*",
      "Condition": {
        "IpAddress": {
          "aws:SourceIp": ["10.0.0.0/8"]
        }
      }
    }
  ]
}
```

### 5.3 Audit Logging

Enable audit logging for pepper access:

```bash
# AWS CloudTrail
aws cloudtrail create-trail \
  --name catvrf-crypto-audit \
  --s3-bucket-name catvrf-audit-logs

# Azure Monitor
az monitor diagnostic-settings create \
  --name catvrf-crypto-audit \
  --resource /subscriptions/{sub}/resourceGroups/{rg}/providers/Microsoft.KeyVault/vaults/catvrf-kv \
  --logs '[{"category":"AuditEvent","enabled":true}]'
```

---

## 6. Migration Strategy

### 6.1 Phased Migration Approach

#### Phase 1: Preparation (Week 1)
- [ ] Generate pepper and AES-256 key
- [ ] Store keys in secrets manager
- [ ] Update configuration files
- [ ] Run database migrations in staging
- [ ] Test migration job in staging
- [ ] Document rollback procedure

#### Phase 2: Staging Deployment (Week 2)
- [ ] Deploy to staging environment
- [ ] Run migration job with dry-run
- [ ] Verify all tests pass
- [ ] Performance testing
- [ ] Security review

#### Phase 3: Production Deployment (Week 3)
- [ ] Schedule maintenance window
- [ ] Backup production database
- [ ] Deploy to production (blue-green deployment)
- [ ] Run database migrations
- [ ] Execute migration job
- [ ] Verify migration success
- [ ] Monitor for errors

#### Phase 4: Post-Deployment (Week 4)
- [ ] Monitor application performance
- [ ] Review error logs
- [ ] Verify password reset flow
- [ ] User feedback collection
- [ ] Documentation update

### 6.2 Rollback Procedure

If migration fails, rollback to previous state:

```bash
# Rollback database migrations
php artisan migrate:rollback --step=2

# Restore previous configuration
# (from backup or version control)

# Restart application
php artisan cache:clear
php artisan config:clear
php artisan queue:restart
```

### 6.3 Monitoring and Alerts

Set up monitoring for:

**Metrics:**
- Migration job completion rate
- Password reset success rate
- Contact hash verification failures
- Encryption/decryption latency
- Key access attempts

**Alerts:**
- Migration job failure
- Key access from unauthorized IP
- High encryption latency (>100ms)
- Password reset failure rate >5%

---

## 7. Testing

### 7.1 Pre-Deployment Testing

```bash
# Run unit tests
./vendor/bin/pest tests/Unit/Services/Security/CryptoServiceTest.php

# Run feature tests
./vendor/bin/pest tests/Feature/Security/

# Run migration tests
./vendor/bin/pest tests/Unit/Jobs/Security/MigrateHashesToSha256AndArgon2idTest.php
```

### 7.2 Load Testing

Test performance under load:

```bash
# Using k6
k6 run k6/crash-test-medical.js --vus 100 --duration 5m
```

**Target Metrics:**
- SHA-256 hash: <1ms
- Argon2id hash: <100ms
- AES-256-GCM encrypt: <5ms per 1KB
- AES-256-GCM decrypt: <5ms per 1KB

### 7.3 Security Testing

```bash
# Run security scan
./vendor/bin/pest tests/Security/

# Check for weak hashes
php artisan tinker
>>> $crypto = app(App\Services\Security\CryptoService::class);
>>> $hash = $crypto->hashContact('test@example.com');
>>> // Verify hash length is 64 characters
>>> // Verify hash is hex-encoded
```

---

## 8. Troubleshooting

### 8.1 Common Issues

#### Issue: Migration Job Fails

**Symptoms:** Job fails with timeout or error

**Solutions:**
1. Reduce batch size in `config/crypto.php`
2. Increase queue worker timeout
3. Check database connection
4. Verify pepper is accessible

```bash
# Check queue configuration
php artisan queue:work --help

# Retry failed jobs
php artisan queue:retry all
```

#### Issue: Password Reset Required

**Symptoms:** Users cannot login after migration

**Solutions:**
1. Verify `password_reset_required` flag is set
2. Check password reset email flow
3. Ensure SMTP is configured
4. Test password reset link

```sql
-- Check users requiring password reset
SELECT COUNT(*) FROM users WHERE password_reset_required = 1;
```

#### Issue: Pepper Not Accessible

**Symptoms:** Application throws "pepper not configured" error

**Solutions:**
1. Verify environment variable is set
2. Check secrets manager permissions
3. Verify IAM roles
4. Test key retrieval

```bash
# Test pepper retrieval
php artisan tinker
>>> config('crypto.pepper');
// Should return pepper string
```

#### Issue: AES-256 Decryption Fails

**Symptoms:** "AES-256-GCM decryption failed" error

**Solutions:**
1. Verify key is 32 bytes
2. Check encryption version (v1 vs v2)
3. Ensure key hasn't been rotated
4. Test with known plaintext

```php
// Test encryption/decryption
$crypto = app(App\Services\Security\CryptoService::class);
$encrypted = $crypto->aes256Encrypt('test');
$decrypted = $crypto->aes256Decrypt($encrypted);
// Should return 'test'
```

### 8.2 Emergency Procedures

#### Emergency: Pepper Compromised

1. **Immediate:** Rotate pepper immediately
2. **Short-term:** Re-hash all contacts
3. **Long-term:** Investigate breach source

```bash
# Emergency pepper rotation
NEW_PEPPER=$(openssl rand -base64 32)
aws secretsmanager update-secret \
  --secret-id catvrf/crypto/pepper \
  --secret-string "$NEW_PEPPER"
```

#### Emergency: AES-256 Key Compromised

1. **Immediate:** Rotate AES-256 key
2. **Short-term:** Re-encrypt all PII
3. **Long-term:** Investigate breach source

```bash
# Emergency key rotation
NEW_KEY=$(openssl rand -base64 32)
aws secretsmanager update-secret \
  --secret-id catvrf/crypto/aes256_key \
  --secret-string "$NEW_KEY"
```

---

## 9. Compliance

### 9.1 ФСТЭК №21 Compliance

**Required Measures:**
- Мера 11: Шифрование персональных данных ✅
- Мера 12: Обеспечение целостности и доступности ✅
- Мера 13: Защита от НСД к информации ✅

**Audit Trail:**
```sql
-- Enable audit logging for crypto operations
CREATE TABLE crypto_audit_log (
    id BIGSERIAL PRIMARY KEY,
    operation VARCHAR(50) NOT NULL,
    user_id BIGINT,
    ip_address INET,
    timestamp TIMESTAMP DEFAULT NOW(),
    success BOOLEAN DEFAULT TRUE
);
```

### 9.2 152-ФЗ Compliance

**Requirements:**
- Pepper stored securely (HSM or secrets manager) ✅
- AES-256-GCM for PII encryption ✅
- Audit logging for all crypto operations ✅
- Data retention policy enforcement ✅

---

## 10. Maintenance Schedule

### 10.1 Daily
- Monitor migration job status
- Check error logs for crypto failures
- Verify key access logs

### 10.2 Weekly
- Review security alerts
- Check key rotation schedule
- Test backup restoration

### 10.3 Monthly
- Review key access patterns
- Update documentation
- Conduct security review

### 10.4 Quarterly
- Rotate AES-256 key
- Review quantum risk level
- Update threat model
- Conduct penetration testing

### 10.5 Yearly
- Rotate pepper
- Full security audit
- Update compliance documentation
- Review PQC migration timeline

---

## 11. Contact Information

**Security Team:** security@catvrf.ru  
**DevOps Team:** devops@catvrf.ru  
**Emergency Contact:** +7 (XXX) XXX-XX-XX

---

## Appendix A: Configuration Reference

### config/crypto.php

```php
return [
    'pepper' => env('CRYPTO_PEPPER'),
    'aes256_key' => env('CRYPTO_AES256_KEY'),
    'password_algorithm' => env('CRYPTO_PASSWORD_ALGORITHM', 'argon2id'),
    'argon2id' => [
        'memory_cost' => env('CRYPTO_ARGON2ID_MEMORY', 19456),
        'time_cost' => env('CRYPTO_ARGON2ID_TIME', 2),
        'threads' => env('CRYPTO_ARGON2ID_THREADS', 1),
    ],
    'contact_hash_algorithm' => 'sha256',
    'behavioral_hash_algorithm' => 'sha256',
    'hmac_algorithm' => 'sha256',
    'quantum_risk_level' => env('CRYPTO_QUANTUM_RISK_LEVEL', 'low'),
    'migration' => [
        'dry_run' => env('CRYPTO_MIGRATION_DRY_RUN', true),
        'batch_size' => env('CRYPTO_MIGRATION_BATCH_SIZE', 1000),
    ],
];
```

---

**Document Owner:** Security Team  
**Last Updated:** 2026-04-23  
**Next Review:** 2026-10-23
