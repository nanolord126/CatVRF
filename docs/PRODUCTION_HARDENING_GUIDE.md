# Production Hardening Guide

**CatVRF Database Security Fortress - Production Deployment**

This guide provides step-by-step instructions for hardening the CatVRF database security fortress in production environments.

## Pre-Deployment Checklist

### 1. Environment Variables

```bash
# Database Encryption (REQUIRED)
DB_ENCRYPTION_ENABLED=true
DB_ENCRYPTION_PEPPER=<generate-32-byte-random-string>
DB_BACKUP_ENCRYPTION=true
DB_ENCRYPTION_ALGORITHM=aes-256-gcm

# Database Security (REQUIRED)
DB_SSL_MODE=require
DB_STATEMENT_TIMEOUT=30
DB_IDLE_IN_TRANSACTION_TIMEOUT=60
DB_LOG_SLOW_QUERIES=true
DB_SLOW_QUERY_THRESHOLD=1000

# Client Protection (REQUIRED)
CLIENT_PROTECTION_RATE_LIMIT_ENABLED=true
CLIENT_PROTECTION_MAX_REQUESTS_PER_MINUTE=60
CLIENT_PROTECTION_CUSTOMER_LIMIT=50
HUNTING_DETECTION_ENABLED=true
HUNTING_DETECTION_SCORE_THRESHOLD=0.7

# Compliance (REQUIRED)
COMPLIANCE_RETENTION_DAYS=30
COMPLIANCE_AUTO_DELETE=true
COMPLIANCE_CLEANUP_SCHEDULE="0 2 * * *"

# Backup Encryption (REQUIRED)
BACKUP_ENCRYPTION_KEY=<generate-32-byte-random-string>
```

### 2. Generate Encryption Keys

```bash
# Generate DB_ENCRYPTION_PEPPER (32 bytes)
openssl rand -base64 32

# Generate BACKUP_ENCRYPTION_KEY (32 bytes)
openssl rand -base64 32
```

**Important:** Store these keys securely in a secrets manager (HashiCorp Vault, AWS Secrets Manager, etc.). Never commit to version control.

## Database User Setup

### Principle of Least Privilege

Create separate database users for different purposes:

### MySQL/MariaDB Setup

```sql
-- 1. Application User (Tenant DBs)
CREATE USER 'catvrf_app'@'%' IDENTIFIED BY '<strong-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON catvrf_tenant_*.* TO 'catvrf_app'@'%';

-- 2. Application User (Central DB - Read Only)
CREATE USER 'catvrf_app_ro'@'%' IDENTIFIED BY '<strong-password>';
GRANT SELECT ON catvrf_central.* TO 'catvrf_app_ro'@'%';

-- 3. Migration User
CREATE USER 'catvrf_migrate'@'%' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON catvrf_central.* TO 'catvrf_migrate'@'%';
GRANT ALL PRIVILEGES ON catvrf_tenant_*.* TO 'catvrf_migrate'@'%';

-- 4. Backup User
CREATE USER 'catvrf_backup'@'%' IDENTIFIED BY '<strong-password>';
GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT ON *.* TO 'catvrf_backup'@'%';

-- 5. Audit User (ClickHouse)
-- In ClickHouse, create user with INSERT only:
CREATE USER catvrf_audit IDENTIFIED BY '<strong-password>';
GRANT INSERT ON catvrf_analytics.* TO catvrf_audit;
```

### PostgreSQL Setup

```sql
-- 1. Application User
CREATE USER catvrf_app WITH PASSWORD '<strong-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO catvrf_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO catvrf_app;

-- 2. Application User (Read Only)
CREATE USER catvrf_app_ro WITH PASSWORD '<strong-password>';
GRANT SELECT ON ALL TABLES IN SCHEMA public TO catvrf_app_ro;

-- 3. Migration User
CREATE USER catvrf_migrate WITH PASSWORD '<strong-password>';
GRANT ALL PRIVILEGES ON SCHEMA public TO catvrf_migrate;

-- 4. Backup User
CREATE USER catvrf_backup WITH PASSWORD '<strong-password>';
GRANT SELECT ON ALL TABLES IN SCHEMA public TO catvrf_backup;
```

## Disk Encryption

### AWS RDS

```bash
# Enable encryption at rest when creating RDS instance
aws rds create-db-instance \
  --db-instance-identifier catvrf-production \
  --allocated-storage 100 \
  --db-instance-class db.t3.medium \
  --engine mysql \
  --master-username admin \
  --master-user-password <strong-password> \
  --storage-encrypted \
  --kms-key-id <your-kms-key-id>
```

### Self-Hosted (Linux)

```bash
# 1. Install cryptsetup
sudo apt-get install cryptsetup

# 2. Create encrypted partition
sudo cryptsetup -y luksFormat /dev/sdb1
sudo cryptsetup luksOpen /dev/sdb1 encrypted_data

# 3. Create filesystem
sudo mkfs.ext4 /dev/mapper/encrypted_data

# 4. Mount
sudo mount /dev/mapper/encrypted_data /var/lib/mysql

# 5. Add to /etc/crypttab for auto-mount
echo "encrypted_data /dev/sdb1 none luks" | sudo tee -a /etc/crypttab

# 6. Add to /etc/fstab
echo "/dev/mapper/encrypted_data /var/lib/mysql ext4 defaults 0 2" | sudo tee -a /etc/fstab
```

## Network Security

### VPC Configuration (AWS)

```bash
# 1. Create VPC
aws ec2 create-vpc --cidr-block 10.0.0.0/16

# 2. Create private subnets for databases
aws ec2 create-subnet --vpc-id <vpc-id> --cidr-block 10.0.1.0/24

# 3. Create security group for databases
aws ec2 create-security-group --group-name db-sg --description "Database SG"

# 4. Allow only application servers
aws ec2 authorize-security-group-ingress \
  --group-id <sg-id> \
  --protocol tcp \
  --port 3306 \
  --source-group <app-server-sg-id>

# 5. Deny all other access
aws ec2 revoke-security-group-ingress --group-id <sg-id> --protocol all --port all
```

### SSL/TLS Configuration

```bash
# 1. Generate SSL certificates
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/mysql/ssl/server-key.pem \
  -out /etc/mysql/ssl/server-cert.pem

# 2. Configure MySQL to use SSL
# Add to my.cnf:
[mysqld]
require_secure_transport=ON
ssl-ca=/etc/mysql/ssl/ca-cert.pem
ssl-cert=/etc/mysql/ssl/server-cert.pem
ssl-key=/etc/mysql/ssl/server-key.pem

# 3. Restart MySQL
sudo systemctl restart mysql
```

## ClickHouse Setup

### Installation

```bash
# 1. Install ClickHouse
curl https://clickhouse.com/ | sh

# 2. Configure security
# /etc/clickhouse-server/config.xml:
<users>
  <catvrf_app>
    <password>strong-password</password>
    <networks>
      <ip>::/0</ip>
    </networks>
    <profile>default</profile>
    <quota>default</quota>
    <allow_databases>
      <database>catvrf_analytics</database>
    </allow_databases>
  </catvrf_app>
</users>

# 3. Enable query logging
<yandex>
  <log_queries>1</log_queries>
  <log_queries_min_type>2</log_queries_min_type>
  <log_queries_min_query_duration_ms>100</log_queries_min_query_duration_ms>
</yandex>

# 4. Start service
sudo systemctl start clickhouse-server
```

### Run Security Schema

```bash
# Execute security audit schema
clickhouse-client --host localhost --user catvrf_app --password <password> \
  --multiquery < database/clickhouse/security_audit.sql
```

## Backup Configuration

### S3 Setup

```bash
# 1. Create S3 bucket
aws s3api create-bucket \
  --bucket catvrf-backups \
  --region us-east-1

# 2. Enable versioning
aws s3api put-bucket-versioning \
  --bucket catvrf-backups \
  --versioning-configuration Status=Enabled

# 3. Enable encryption
aws s3api put-bucket-encryption \
  --bucket catvrf-backups \
  --server-side-encryption-configuration \
  '{"Rules":[{"ApplyServerSideEncryptionByDefault":{"SSEAlgorithm":"AES256"}}]}'

# 4. Set lifecycle policy (30-day retention)
aws s3api put-bucket-lifecycle-configuration \
  --bucket catvrf-backups \
  --lifecycle-configuration \
  '{"Rules":[{"ID":"DeleteOldBackups","Status":"Enabled","Expiration":{"Days":30}}]}'
```

### Configure Laravel Backup

```bash
# Install spatie/laravel-backup
composer require spatie/laravel-backup

# Publish config
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Configure config/backup.php
# Set storage to s3
# Enable encryption
# Set retention to 30 days
```

### Schedule Backups

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Daily central backup at 01:00
    $schedule->job(new EncryptedBackupJob(null, true))
        ->dailyAt('01:00');

    // Hourly incremental backups
    $schedule->job(new EncryptedBackupJob())
        ->hourly();

    // Cleanup old backups daily at 03:00
    $schedule->job(new CleanupExpiredDataJob())
        ->dailyAt('03:00');
}
```

## Monitoring Setup

### Prometheus Metrics

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'catvrf-app'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: '/metrics'
```

### Grafana Dashboards

Import the following dashboards:
1. Database Security Events
2. Hunting Detection Summary
3. Query Performance
4. Cross-Tenant Attempts

### Alert Rules

```yaml
# alerting_rules.yml
groups:
  - name: database_security
    rules:
      - alert: HighHuntingScore
        expr: hunting_score > 0.8
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High hunting score detected"
          description: "User {{ $labels.user_id }} has hunting score {{ $value }}"

      - alert: MassExportAttempt
        expr: export_records > 1000
        for: 1m
        labels:
          severity: warning
        annotations:
          summary: "Mass export attempt detected"
          description: "Export of {{ $value }} records attempted"

      - alert: CrossTenantSurge
        expr: cross_tenant_attempts > 10
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Cross-tenant access surge"
          description: "{{ $value }} cross-tenant attempts detected"
```

## Key Rotation

### Encryption Key Rotation

```bash
# 1. Generate new keys
NEW_PEPPER=$(openssl rand -base64 32)
NEW_BACKUP_KEY=$(openssl rand -base64 32)

# 2. Update environment variables
# Update in secrets manager or .env file

# 3. Re-encrypt sensitive data (gradual migration)
php artisan security:rotate-encryption-keys

# 4. Test decryption with new key
php artisan security:test-encryption

# 5. Remove old key after validation
# Wait 7 days before removing old key
```

### Database Password Rotation

```bash
# 1. Generate new password
NEW_PASSWORD=$(openssl rand -base64 32)

# 2. Update database user password
mysql -u root -p -e "ALTER USER 'catvrf_app'@'%' IDENTIFIED BY '$NEW_PASSWORD';"

# 3. Update environment variables
DB_PASSWORD=$NEW_PASSWORD

# 4. Test connection
php artisan db:show
```

## Performance Tuning

### MySQL/MariaDB

```ini
# /etc/mysql/my.cnf
[mysqld]
# Connection settings
max_connections = 500
max_connect_errors = 100

# Buffer settings
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# Query cache (MySQL 5.7 and below)
query_cache_size = 128M
query_cache_type = 1

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1

# Binary log (for backups)
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M
```

### PostgreSQL

```ini
# /etc/postgresql/14/main/postgresql.conf
# Connection settings
max_connections = 200

# Memory settings
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
work_mem = 16MB

# WAL settings
wal_buffers = 16MB
checkpoint_completion_target = 0.9
max_wal_size = 1GB

# Query logging
log_min_duration_statement = 1000
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '

# Performance
random_page_cost = 1.1
effective_io_concurrency = 200
```

### ClickHouse

```xml
<!-- /etc/clickhouse-server/config.xml -->
<yandex>
  <max_memory_usage>8000000000</max_memory_usage>
  
  <max_concurrent_queries>100</max_concurrent_queries>
  
  <max_threads>8</max_threads>
  
  <background_pool_size>16</background_pool_size>
  
  <max_table_size_to_drop>10000000000</max_table_size_to_drop>
  
  <max_partition_size_to_drop>100000000000</max_partition_size_to_drop>
</yandex>
```

## Security Audits

### Monthly Checklist

- [ ] Review ClickHouse security event logs
- [ ] Check hunting detection trends
- [ ] Verify backup encryption
- [ ] Test restore from backup
- [ ] Review database user privileges
- [ ] Check SSL certificate expiry
- [ ] Review rate limiting effectiveness
- [ ] Audit cross-tenant attempts
- [ ] Verify key rotation schedule
- [ ] Review alert delivery

### Quarterly Checklist

- [ ] Rotate encryption keys
- [ ] Rotate database passwords
- [ ] Review and update security policies
- [ ] Penetration testing
- [ ] Security training for team
- [ ] Review compliance (152-ФZ)
- [ ] Update documentation
- [ ] Review access logs
- [ ] Test disaster recovery
- [ ] Performance audit

## Incident Response

### Security Incident Procedure

1. **Detection**
   - Monitor ClickHouse alerts
   - Review Grafana dashboards
   - Check error logs

2. **Containment**
   - Block suspicious IP addresses
   - Trigger cooldown for affected users
   - Disable affected accounts if necessary

3. **Investigation**
   - Analyze ClickHouse query logs
   - Review hunting detection scores
   - Check cross-tenant attempts
   - Correlate with application logs

4. **Remediation**
   - Patch vulnerabilities
   - Update security rules
   - Rotate compromised keys
   - Notify affected users

5. **Recovery**
   - Restore from backup if needed
   - Verify data integrity
   - Monitor for recurrence
   - Document incident

### Emergency Contacts

- Security Team: security@catvrf.ru
- DevOps Team: devops@catvrf.ru
- On-Call: +7-XXX-XXX-XXXX
- Legal: legal@catvrf.ru

## Troubleshooting

### Common Issues

**Issue: Encryption/Decryption Fails**
```bash
# Check APP_KEY matches
php artisan key:generate

# Verify DB_ENCRYPTION_PEPPER is set
echo $DB_ENCRYPTION_PEPPER

# Test encryption
php artisan security:test-encryption
```

**Issue: Hunting Detection False Positives**
```bash
# Adjust threshold
# Update .env
HUNTING_DETECTION_SCORE_THRESHOLD=0.8

# Clear cache
php artisan cache:clear
```

**Issue: Backup Fails**
```bash
# Check disk space
df -h

# Verify mysqldump availability
which mysqldump

# Test backup manually
php artisan backup:run --only-db
```

**Issue: ClickHouse Connection Fails**
```bash
# Check ClickHouse status
sudo systemctl status clickhouse-server

# Verify configuration
clickhouse-client --host localhost --query "SELECT 1"

# Check firewall
sudo ufw status
```

## Compliance Verification

### 152-ФЗ Checklist

- [ ] Data encryption at rest enabled
- [ ] Data encryption in transit enabled (SSL/TLS)
- [ ] Audit logging enabled and functional
- [ ] Data retention policy implemented (30 days)
- [ ] Consent withdrawal mechanism functional
- [ ] Data anonymization on consent withdrawal
- [ ] Automated cleanup jobs scheduled
- [ ] Backup encryption enabled
- [ ] Access control implemented
- [ ] Cross-tenant isolation verified

### OWASP Checklist

- [ ] SQL injection prevention (Eloquent/Query Builder)
- [ ] Parameterized queries enforced
- [ ] Input validation on all endpoints
- [ ] Output encoding for XSS prevention
- [ ] Authentication mechanism secure
- [ ] Session management secure
- [ ] CSRF protection enabled
- [ ] Security headers configured
- [ ] Error handling secure (no stack traces)
- [ ] Logging and monitoring enabled

## Support

For production issues:
- Emergency: admin@catvrf.ru
- Security: security@catvrf.ru
- Documentation: docs/DATABASE_SECURITY_FORTRESS_2026.md

## Version History

- **v1.0** (2026-04-23): Initial production hardening guide
