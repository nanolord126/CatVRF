# Insider Threat Protection System

**Version:** 1.0  
**Status:** Production Ready  
**Compliance:** 152-ФЗ, GDPR, PCI DSS  

## Overview

The CatVRF Insider Threat Protection System provides comprehensive defense against internal threats (malicious/negligent staff) targeting customer data. This system enforces **Zero Trust principles** with multi-layered protection including:

- **Least Privilege RBAC** with role-based data access controls
- **Data Masking** by default for all sensitive customer information
- **Behavioral Monitoring** with ML-powered anomaly detection
- **Just-In-Time (JIT) Access** requiring Passkey + 2FA verification
- **Technical Controls** against data exfiltration
- **Automated Deprovisioning** for offboarding staff
- **Immutable Audit Logging** to ClickHouse

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Filament   │  │     API      │  │   Livewire   │      │
│  │   Resources  │  │  Endpoints   │  │   Components │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                  │                  │              │
└─────────┼──────────────────┼──────────────────┼──────────────┘
          │                  │                  │
┌─────────▼──────────────────▼──────────────────▼──────────────┐
│                   Protection Layer                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │        InsiderAccessGuardMiddleware                  │   │
│  │  - Rate limiting  - Hunting detection  - Cooldown    │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │        ClientDataProtectionService                   │   │
│  │  - Access checks  - Masking  - Export validation    │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │        InsiderThreatService                          │   │
│  │  - Behavioral scoring  - Anomaly detection  - ML    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
          │                  │                  │
┌─────────▼──────────────────▼──────────────────▼──────────────┐
│                   Data Layer                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   MySQL      │  │  ClickHouse  │  │    Redis     │      │
│  │   (masked)   │  │  (audit log) │  │   (cache)    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. Data Masking & Selective Visibility

**Default Behavior:** All customer data is masked by default for staff roles.

**Masked Fields:**
- Email: `u***@example.com`
- Phone: `+7 (***) ***-**-45`
- INN: `********4567`
- Name: `И*** В***`

**Full Access Requirements:**
- Super-admin role (always)
- JIT access elevation with Passkey + 2FA
- Explicit `viewFullClientData` permission

**Implementation:**
```php
// User model
$user->getEmailWithPermission(); // Returns masked or full based on permissions
$user->canViewFullClientData(); // Check access
$user->requestJitAccess($requester); // Request temporary elevation
```

### 2. Behavioral Monitoring

**InsiderThreatService** analyzes staff actions for suspicious patterns:

**Detection Patterns:**
- Unusual time access (outside business hours)
- Unusual location (different IP/geo)
- High-frequency actions
- Sensitive data access
- Mass operations (bulk download/export)
- Financial manipulation
- Termination risk (revoked/inactive users)
- Behavioral biometrics anomaly

**Scoring:**
- Anomaly score: 0.0 - 1.0
- Severity: low, medium, high, critical
- Block threshold: 0.85
- Alert threshold: 0.6

**Implementation:**
```php
$insiderService->analyzeClientDataAccess(
    $staff,
    $tenant,
    'view',
    $recordCount,
    ['ip_address' => '127.0.0.1']
);
```

### 3. JIT (Just-In-Time) Access

**Purpose:** Temporary elevation of privileges for viewing full customer data.

**Requirements:**
- Passkey verification (configurable)
- 2FA enabled (configurable)
- Duration: 60 minutes (configurable)

**Workflow:**
1. Staff requests JIT access
2. System verifies Passkey + 2FA
3. Access granted for configured duration
4. All actions logged to audit
5. Access automatically expires

**Implementation:**
```php
$client->requestJitAccess($requester);
$client->hasJitAccess($requester);
$client->revokeJitAccess($requester);
```

### 4. Technical Controls

**Rate Limiting:**
- Super-admin: 60 req/min
- Support: 30 req/min
- Owner: 20 req/min
- Manager: 15 req/min
- Employee: 10 req/min

**Result Limits:**
- Super-admin: 1000 records
- Support: 500 records
- Owner: 200 records
- Manager: 100 records
- Employee: 50 records

**Export Controls:**
- Disabled for staff by default
- Requires manual approval for >50 records
- 24-hour cooldown after export
- Max 1 concurrent export per tenant

**Hunting Detection:**
- LIKE queries on email/phone trigger alerts
- 3 attempts → 1-hour cooldown
- Cross-tenant access blocked for non-admins

### 5. Deprovisioning & Offboarding

**Automatic Actions on Staff Revocation:**
- Immediate logout of all sessions
- Revoke all Passkey credentials
- Revoke all Sanctum tokens
- Delete temporary JIT access elevations
- Audit last 30 days of actions
- 30-day cooldown before rejoining tenant

**Implementation:**
```php
$staff->revokeAccess($adminId, 'Terminated');
// Automatically triggers deprovisioning
```

### 6. Audit & Alerting

**Immutable Logging:**
- All client data accesses logged to ClickHouse
- Includes: user_id, tenant_id, action_type, rows_affected, query_hash
- Retention: 365 days
- Cannot be modified or deleted

**Real-time Alerts:**
- High/critical severity → Telegram/Slack/Email
- Tenant owners notified for high severity
- Super-admins notified for critical severity

**Weekly Reports:**
- Sent to tenant owners every Monday
- Includes: access statistics, anomaly scores, blocked actions

## Configuration

All settings in `config/insider-protection.php`:

```php
return [
    'behavioral' => [
        'max_client_views_per_hour' => 50,
        'max_client_views_per_day' => 200,
        'hunting_score_threshold' => 0.7,
        'anomaly_score_threshold' => 0.85,
        'alert_threshold' => 0.6,
    ],
    'data_access' => [
        'max_records_per_request' => [...],
        'enable_jit_access' => true,
        'jit_access_duration_minutes' => 60,
        'jit_requires_passkey' => true,
        'jit_requires_2fa' => true,
    ],
    'masking' => [
        'mask_by_default' => true,
        'masked_user_fields' => ['email', 'phone', 'inn', ...],
    ],
    'export_control' => [
        'enable_staff_export' => false,
        'requires_manual_approval' => true,
        'export_cooldown_hours' => 24,
    ],
    'deprovisioning' => [
        'rejoin_cooldown_days' => 30,
        'audit_period_days' => 30,
    ],
];
```

## API Endpoints

### JIT Access Management

```http
POST /api/v1/users/{id}/jit-access
Authorization: Bearer {token}
Content-Type: application/json

{
  "passkey_challenge": "...",
  "totp_code": "123456"
}

Response:
{
  "success": true,
  "expires_at": "2026-04-23T15:00:00Z"
}
```

```http
DELETE /api/v1/users/{id}/jit-access
Authorization: Bearer {token}
```

### Client Data Access

```http
GET /api/v1/users?search={term}
Authorization: Bearer {token}
X-Request-ID: {uuid}

Response:
{
  "data": [
    {
      "id": 1,
      "masked_email": "u***@example.com",
      "masked_phone": "+7 (***) ***-**-45",
      ...
    }
  ],
  "meta": {
    "truncated": false,
    "max_records": 100
  }
}
```

### Threat Statistics

```http
GET /api/v1/insider-threats/stats?days=7
Authorization: Bearer {token}

Response:
{
  "total_access_events": 150,
  "total_views": 100,
  "total_searches": 40,
  "total_exports": 10,
  "avg_anomaly_score": 0.15,
  "blocked_actions": 2,
  "high_risk_events": 5
}
```

## Event System

### InsiderAnomalyDetected Event

Dispatched when anomaly score exceeds alert threshold.

```php
event(new InsiderAnomalyDetected(
    $staff,
    $tenant,
    'client_data_view',
    0.75,
    'high',
    $log
));
```

**Listeners:**
- `HandleInsiderAnomaly` - Triggers cooldown, sends notifications, locks accounts

### CooldownStarted Event

Dispatched when a cooldown period is triggered.

```php
event(new CooldownStarted(
    $cooldown,
    $triggeredBy
));
```

## Testing

### Run Pest Tests

```bash
php artisan test --filter=InsiderThreatProtectionTest
```

### Test Coverage

- ✅ Staff can view client data with masking
- ✅ Staff cannot view full data without JIT access
- ✅ JIT access requires Passkey + 2FA
- ✅ Mass access is detected as threat
- ✅ Hunting patterns are detected and blocked
- ✅ Cross-tenant access is blocked
- ✅ Super-admins have elevated access
- ✅ Client view threshold triggers cooldown
- ✅ Export attempts by staff are blocked
- ✅ Insider threat logs are created
- ✅ Deprovisioned staff cannot access data
- ✅ Rate limiting prevents high-frequency requests
- ✅ Customers accessing own data are not flagged
- ✅ JIT access expires after duration

## Compliance Checklist

### 152-ФЗ (Russian Federal Law)

- ✅ Personal data anonymization before external processing
- ✅ Encrypted storage of sensitive fields (email, phone, INN)
- ✅ Access control with role-based permissions
- ✅ Immutable audit logging (ClickHouse)
- ✅ Data breach detection and notification
- ✅ Cross-border data transfer controls

### GDPR

- ✅ Data minimization (masked by default)
- ✅ Purpose limitation (JIT access for specific tasks)
- ✅ Access rights (users can view their data)
- ✅ Right to be forgotten (soft delete + anonymization)
- ✅ Data portability (export with approval)
- ✅ Breach notification (real-time alerts)

### PCI DSS

- ✅ Restricted access to cardholder data
- ✅ Unique identification for each user
- ✅ Physical access controls (Passkey)
- ✅ Logging and monitoring
- ✅ Regular testing of security systems
- ✅ Incident response plan

## Production Deployment

### 1. Configuration

```bash
# .env
CLICKHOUSE_AUDIT_ENABLED=true
TELEGRAM_SECURITY_ALERT_ENABLED=true
SLACK_SECURITY_ALERT_ENABLED=true
EMAIL_SECURITY_ALERT_ENABLED=true
```

### 2. Middleware Registration

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \App\Http\Middleware\InsiderAccessGuardMiddleware::class,
    ],
];
```

### 3. Event Listeners Registration

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \App\Events\InsiderAnomalyDetected::class => [
        \App\Listeners\Security\HandleInsiderAnomaly::class,
    ],
];
```

### 4. ClickHouse Setup

```sql
CREATE TABLE security_events (
    event_type String,
    channel String,
    data String,
    created_at DateTime
) ENGINE = MergeTree()
ORDER BY (created_at, event_type);
```

### 5. Monitoring

- Set up Grafana dashboards for anomaly scores
- Configure alerts for critical threats
- Monitor ClickHouse query performance
- Track cooldown periods

## Troubleshooting

### Issue: Staff cannot view customer data

**Check:**
1. Is the user's role correct?
2. Is JIT access enabled in config?
3. Does the user have Passkey registered?
4. Is 2FA enabled?
5. Is the user under cooldown?

### Issue: False positive threat detection

**Solution:**
1. Review anomaly score in InsiderThreatLog
2. Adjust thresholds in config/insider-protection.php
3. Override cooldown manually if needed
4. Whitelist trusted IPs if needed

### Issue: High CPU usage from anomaly detection

**Solution:**
1. Reduce frequency of behavioral scoring
2. Cache ML model predictions
3. Use queue for async analysis
4. Optimize ClickHouse queries

## Security Best Practices

1. **Never disable JIT access** in production
2. **Always require Passkey** for sensitive operations
3. **Review insider threat logs** weekly
4. **Rotate encryption keys** quarterly
5. **Test deprovisioning** regularly
6. **Audit super-admin access** monthly
7. **Keep thresholds conservative** (better to block than leak)
8. **Monitor ClickHouse** for audit log integrity

## Support & Escalation

**Level 1:** Tenant Owner - Handle low/medium threats within tenant  
**Level 2:** Super-Admin - Handle high threats, cross-tenant issues  
**Level 3:** Security Team - Handle critical threats, system-wide incidents  

**Contact:** security@catvrf.ru  
**Emergency:** +7 (XXX) XXX-XX-XX  

## Changelog

### v1.0 (2026-04-23)
- Initial release
- Behavioral monitoring with ML integration
- JIT access with Passkey + 2FA
- Data masking by default
- Immutable audit logging to ClickHouse
- Automated deprovisioning
- Comprehensive Pest test suite
- Full compliance with 152-ФЗ, GDPR, PCI DSS

---

**Document Owner:** Security Team  
**Last Updated:** 2026-04-23  
**Next Review:** 2026-07-23
