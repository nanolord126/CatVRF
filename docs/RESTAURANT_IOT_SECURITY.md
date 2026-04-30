# Restaurant IoT Security Best Practices & Audit Checklist

**Version:** 1.0  
**Date:** 2026-04-23  
**Compliance:** ФСТЭК №21 (ИСПДн), ФЗ-152, Роспотребнадзор HoReCa  
**Project:** CatVRF Restaurant IoT Security System

---

## Executive Summary

This document provides comprehensive security guidelines, best practices, and audit checklists for the CatVRF Restaurant IoT system. It covers multi-layered security architecture designed to protect IoT devices (temperature sensors, smart scales, KDS displays, printers, timers, smart locks) from unauthorized access, data tampering, DDoS attacks, and insider threats.

## Security Architecture Overview

### Defense in Depth Layers

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 6: Physical & Operational Security                    │
│ - Device placement in locked cabinets                       │
│ - Regular physical audits                                    │
│ - Onboarding/offboarding procedures                          │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ Layer 5: Monitoring & Response                              │
│ - Real-time anomaly detection                                │
│ - Automated quarantine on attack detection                   │
│ - Security event logging to ClickHouse                       │
│ - Alert notifications (email, Slack, SMS)                    │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: Application Security                               │
│ - Input validation & sanitization                            │
│ - Payload signature verification (HMAC/ECDSA)                │
│ - Behavioral analysis                                        │
│ - Rate limiting per device                                   │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Protocol Security (MQTT)                           │
│ - TLS 1.3 only                                              │
│ - mTLS (Mutual TLS) for all connections                     │
│ - QoS 2 for critical commands                               │
│ - ACL (Access Control Lists) by tenant                      │
│ - AES-256-GCM payload encryption (optional)                 │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Authentication & Authorization                     │
│ - X.509 certificates per device                              │
│ - Device attestation (MAC + serial verification)             │
│ - Certificate rotation every 30 days                        │
│ - Device revocation list (CRL)                              │
│ - Tenant isolation at all levels                            │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Network Isolation                                  │
│ - Dedicated VLAN for IoT devices (10.10.20.0/24)            │
│ - No direct internet access to devices                      │
│ - Only IoTHubService can communicate with devices           │
│ - Firewall rules restricting MQTT traffic                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 1. Threat Model for Restaurant IoT

### High-Risk Threats

| Threat | Impact | Likelihood | Mitigation |
|--------|--------|------------|------------|
| **MQTT Broker Compromise** | Critical (control all devices) | Medium | mTLS, ACL, Network isolation, Regular audits |
| **Sensor Data Tampering** | High (food safety, regulatory fines) | High | Signature verification, Anomaly detection, Device attestation |
| **DDoS on IoT Devices** | High (kitchen shutdown) | Medium | Rate limiting, Network isolation, Traffic analysis |
| **Insider Attack** | High (sabotage, data theft) | Medium | Device attestation, Audit logging, Role-based access |
| **Physical Device Theft/Modification** | Medium | Low | Physical security, Tamper-evident enclosures, Regular inventory |

### Medium-Risk Threats

| Threat | Impact | Likelihood | Mitigation |
|--------|--------|------------|------------|
| **MITM (Man-in-the-Middle)** | High | Medium | TLS 1.3, mTLS, Certificate pinning |
| **Firmware Vulnerabilities** | High | Medium | Secure OTA updates, Vendor vetting, Patch management |
| **Side-Channel Attacks** | Medium | Low | Hardware security modules, Regular firmware updates |
| **Credential Theft** | High | Medium | No credentials on devices, Certificate-based auth |

---

## 2. Security Best Practices

### 2.1 Network Security

#### VLAN Segmentation
```bash
# IoT VLAN Configuration (Example)
VLAN 20: IoT Devices (10.10.20.0/24)
  - Temperature sensors: 10.10.20.10-50
  - Smart scales: 10.10.20.51-70
  - KDS displays: 10.10.20.71-90
  - Printers: 10.10.20.91-110
  - Smart locks: 10.10.20.111-120

VLAN 10: Application Servers (10.10.10.0/24)
  - IoTHubService: 10.10.10.10
  - API Gateway: 10.10.10.11
  - Database: 10.10.10.20

# Firewall Rules
iptables -A FORWARD -s 10.10.20.0/24 -d 10.10.10.10 -p tcp --dport 8883 -j ACCEPT
iptables -A FORWARD -s 10.10.20.0/24 -d 10.10.10.10 -p tcp --dport 8084 -j ACCEPT
iptables -A FORWARD -s 10.10.20.0/24 -j DROP  # Block all other traffic
```

#### IP Whitelisting
```php
// config/restaurant_iot.php
'security' => [
    'network' => [
        'tenant_isolation_enabled' => true,
        'allowed_ip_ranges' => [
            '10.10.20.0/24',  // IoT VLAN
            '10.10.10.0/24',  // Application VLAN
        ],
        'blocked_ip_ranges' => [
            '0.0.0.0/0',  // Block all other by default
        ],
    ],
],
```

### 2.2 Certificate Management

#### Certificate Generation
```bash
# Use the provided script
./generate_device_cert.sh <device_id> <tenant_id> /etc/catvrf/certs/

# Output:
# - <device_id>.key (private key - NEVER share)
# - <device_id>.crt (public certificate)
# - Certificate fingerprint (for database)
```

#### Certificate Rotation
```php
// Scheduled job (daily)
use Modules\Restaurant\Application\Services\IoTSecurityService;
use Modules\Restaurant\Domain\Repositories\DeviceCertificateRepositoryInterface;

class RotateCertificatesCommand extends Command
{
    public function handle()
    {
        $expiringSoon = $certificateRepository->findExpiringWithin(7, $tenantId);
        
        foreach ($expiringSoon as $cert) {
            // Generate new certificate
            // Send to device via secure channel
            // Update database
            // Revoke old certificate after grace period
        }
    }
}
```

#### Certificate Revocation
```php
// Immediate revocation on compromise
$certificate = $certificateRepository->findByDeviceId($deviceId);
$revoked = $certificate->revoke('Key compromise detected');
$certificateRepository->save($revoked);

// Update CRL on MQTT broker
// Device will be unable to connect
```

### 2.3 MQTT Security

#### Topic Structure
```
catcrm/{tenant_id}/telemetry/{device_id}      # Device → Server (publish)
catcrm/{tenant_id}/commands/{device_id}        # Server → Device (subscribe)
catcrm/{tenant_id}/status/{device_id}          # Bidirectional
catcrm/{tenant_id}/alerts/{device_id}          # Device → Server (publish)
catcrm/{tenant_id}/discovery                   # Server → Devices (broadcast)
```

#### ACL Rules (Database)
```sql
-- Device can only publish to its own telemetry topic
INSERT INTO mqtt_acl (username, clientid, topic, action, permission, tenant_id)
VALUES ('tenant_1_user', 'catvrf_t1_device_001', 'catcrm/1/telemetry/001', 'publish', 'allow', 1);

-- Device can only subscribe to its own commands topic
INSERT INTO mqtt_acl (username, clientid, topic, action, permission, tenant_id)
VALUES ('tenant_1_user', 'catvrf_t1_device_001', 'catcrm/1/commands/001', 'subscribe', 'allow', 1);

-- Deny cross-tenant access
INSERT INTO mqtt_acl (username, clientid, topic, action, permission, tenant_id)
VALUES ('tenant_1_user', '%', 'catcrm/2/#', 'publish', 'deny', 1);
```

### 2.4 Application Security

#### Signature Verification
```php
// All telemetry must be signed
$payload = ['temperature' => 5.5, 'timestamp' => time()];
$signableData = json_encode($payload);
$signature = base64_encode($device->sign($signableData));

// Server verification
$verified = $securityService->verifyDeviceAndSignature(
    $deviceIdentifier,
    $signature,
    $signableData,
    $certificateFingerprint,
    $nonce  // Replay protection
);
```

#### Rate Limiting
```php
// Per-device rate limiting
'rate_limiting' => [
    'enabled' => true,
    'default_per_minute' => 60,
    'burst_per_minute' => 100,
    'window_seconds' => 60,
],

// Override per device type
$device->metadata = [
    'rate_limit_per_minute' => 120,  // High-frequency sensors
];
```

#### Anomaly Detection
```php
// Automatic detection of abnormal behavior
'anomaly_detection' => [
    'enabled' => true,
    'deviation_threshold' => 0.5,  // 50% deviation triggers alert
    'history_size' => 10,          // Compare against last 10 readings
    'quarantine_threshold' => 3,   // 3 anomalies → quarantine
    'time_window_minutes' => 10,   // Within 10 minutes
],
```

### 2.5 Data Privacy (152-ФЗ Compliance)

#### PII Anonymization
```php
// Never send raw personal data to external AI/LLMs
$anonymizedData = [
    'device_id' => hash('sha256', $device->id),  // Hashed identifier
    'temperature' => $telemetry->value,
    'timestamp' => $telemetry->recordedAt,
    // No customer names, order details, or PII
];
```

#### Data Retention
```php
'telemetry' => [
    'retention_days' => 90,  // Delete after 90 days
    'anonymize_after_days' => 30,  // Anonymize after 30 days
],

'security_events' => [
    'retention_days' => 365,  // Keep security events longer
],
```

---

## 3. Audit Checklist (ФСТЭК №21 & HoReCa)

### 3.1 Pre-Deployment Checklist

- [ ] **Network Isolation**
  - [ ] IoT devices on dedicated VLAN
  - [ ] No direct internet access to devices
  - [ ] Firewall rules restrict MQTT traffic
  - [ ] Only IoTHubService can communicate with devices

- [ ] **TLS/mTLS Configuration**
  - [ ] TLS 1.3 only (TLS 1.0/1.1/1.2 disabled)
  - [ ] mTLS enabled for all connections
  - [ ] Certificate chain validation enabled
  - [ ] CRL (Certificate Revocation List) configured
  - [ ] Certificate depth limit set to 5

- [ ] **Certificate Management**
  - [ ] CA certificate securely stored
  - [ ] Device certificates generated per device
  - [ ] Certificate fingerprint stored in database
  - [ ] Certificate rotation schedule configured (30 days)
  - [ ] Certificate expiration monitoring enabled

- [ ] **MQTT Broker Security**
  - [ ] ACL rules enforce tenant isolation
  - [ ] Topic structure follows naming convention
  - [ ] QoS 2 for critical commands
  - [ ] Rate limiting configured
  - [ ] Connection limits set

- [ ] **Application Security**
  - [ ] Signature verification enabled
  - [ ] Replay protection (nonce) enabled
  - [ ] Rate limiting per device configured
  - [ ] Anomaly detection enabled
  - [ ] Automatic quarantine on critical events

- [ ] **Data Privacy (152-ФЗ)**
  - [ ] PII anonymization implemented
  - [ ] Data retention policy configured
  - [ ] Audit logging enabled
  - [ ] Access logs retained per regulation

### 3.2 Operational Checklist (Monthly)

- [ ] **Certificate Review**
  - [ ] Check for certificates expiring within 30 days
  - [ ] Verify no revoked certificates in use
  - [ ] Review certificate issuance logs
  - [ ] Update CRL if needed

- [ ] **Security Event Review**
  - [ ] Review critical and emergency events
  - [ ] Investigate quarantine triggers
  - [ ] Analyze anomaly patterns
  - [ ] Update anomaly thresholds if needed

- [ ] **Device Audit**
  - [ ] Verify all registered devices are physically present
  - [ ] Check for unauthorized devices
  - [ ] Review device firmware versions
  - [ ] Update firmware if security patches available

- [ ] **Network Audit**
  - [ ] Review firewall logs for blocked traffic
  - [ ] Check for unauthorized connection attempts
  - [ ] Verify VLAN segmentation is intact
  - [ ] Review IP whitelist/blacklist

- [ ] **Performance Review**
  - [ ] Monitor MQTT broker performance
  - [ ] Check for rate limit violations
  - [ ] Review connection patterns
  - [ ] Analyze telemetry volume

### 3.3 Incident Response Checklist

- [ ] **Immediate Actions**
  - [ ] Identify affected devices
  - [ ] Quarantine compromised devices
  - [ ] Revoke certificates if needed
  - [ ] Block IPs if attack is network-based

- [ ] **Investigation**
  - [ ] Review security event logs
  - [ ] Analyze telemetry for anomalies
  - [ ] Check for data exfiltration
  - [ ] Determine attack vector

- [ ] **Recovery**
  - [ ] Restore devices from backup (if applicable)
  - [ ] Issue new certificates
  - [ ] Update firmware if needed
  - [ ] Remove quarantine after verification

- [ ] **Post-Incident**
  - [ ] Document incident timeline
  - [ ] Update security procedures
  - [ ] Retrain staff if needed
  - [ ] Report to authorities if required (152-ФЗ)

### 3.4 Compliance Checklist (ФСТЭК №21)

- [ ] **Access Control**
  - [ ] Role-based access control implemented
  - [ ] Multi-factor authentication for admin access
  - [ ] Regular access reviews
  - [ ] Privileged access logging

- [ ] **Data Protection**
  - [ ] Encryption at rest (database)
  - [ ] Encryption in transit (TLS 1.3)
  - [ ] Data masking for PII
  - [ ] Secure key management

- [ ] **Monitoring & Logging**
  - [ ] All access attempts logged
  - [ ] Security events logged with timestamps
  - [ ] Log integrity protection
  - [ ] Log retention per regulation

- [ ] **Incident Management**
  - [ ] Incident response plan documented
  - [ ] Incident response team identified
  - [ ] Regular incident response drills
  - [ ] Incident reporting procedures

- [ ] **Change Management**
  - [ ] All changes approved
  - [ ] Change logging
  - [ ] Rollback procedures
  - [ ] Testing in staging environment

### 3.5 HoReCa Industry Checklist

- [ ] **Food Safety (Роспотребнадзор)**
  - [ ] Temperature monitoring continuous
  - [ ] Alerts configured for threshold violations
  - [ ] Data retention meets regulatory requirements
  - [ ] Audit trail for temperature logs

- [ ] **Operational Continuity**
  - [ ] Redundant MQTT broker configured
  - [ ] Device failover procedures
  - [ ] Backup power for critical devices
  - [ ] Offline mode for essential functions

- [ ] **Staff Training**
  - [ ] Security awareness training completed
  - [ ] Device handling procedures documented
  - [ ] Incident reporting procedures known
  - [ ] Regular refresher training scheduled

---

## 4. Security Metrics & KPIs

### Key Security Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Certificate Coverage** | 100% | % of devices with valid certificates |
| **Certificate Expiration** | 0 expired | Number of expired certificates |
| **Security Event Response Time** | < 5 min | Time from detection to quarantine |
| **Anomaly Detection Accuracy** | > 95% | True positives / total detections |
| **Rate Limit Violations** | < 1% | % of requests exceeding rate limit |
| **Failed Authentication Attempts** | < 0.1% | % of failed auth attempts |
| **Device Quarantine Rate** | < 0.5% | % of devices quarantined monthly |

### Monitoring Dashboard

```php
// Recommended Prometheus metrics
iot_security_authentication_total{status="success|failure"}
iot_security_signature_verification_total{result="valid|invalid"}
iot_security_quarantine_total{reason="..."}
iot_security_anomaly_detected_total{metric_type="..."}
iot_security_rate_limit_exceeded_total{device_id="..."}
iot_mqtt_connections_total{tenant_id="..."}
iot_mqtt_messages_total{direction="publish|subscribe"}
```

---

## 5. Incident Response Playbook

### Scenario 1: Suspicious Temperature Readings

**Detection:** Anomaly detection triggers alert for temperature sensor showing sudden spike from 4°C to 25°C

**Response:**
1. Verify reading is not legitimate (check physical sensor)
2. Quarantine device automatically
3. Notify restaurant manager and IT team
4. Investigate for signs of tampering
5. If tampering confirmed, revoke certificate and replace device
6. Document incident and update security procedures

### Scenario 2: Brute Force Authentication Attempts

**Detection:** Multiple failed authentication attempts from same IP

**Response:**
1. Block IP at firewall level
2. Quarantine affected devices
3. Analyze logs to determine attack source
4. If internal, investigate staff access
5. If external, report to security team
6. Update rate limiting rules

### Scenario 3: Certificate Compromise

**Detection:** Certificate private key leaked or device stolen

**Response:**
1. Immediately revoke certificate in CRL
2. Quarantine all devices using compromised certificate
3. Issue new certificates to affected devices
4. Investigate cause of compromise
5. Update physical security procedures
6. Conduct security audit

### Scenario 4: DDoS Attack on MQTT Broker

**Detection:** Sudden spike in connection attempts, high CPU usage

**Response:**
1. Enable rate limiting at network level
2. Block suspicious IP ranges
3. Scale MQTT broker horizontally
4. Enable traffic filtering
5. Notify ISP for upstream blocking
6. Post-incident analysis and hardening

---

## 6. Compliance References

### Russian Federation Regulations

- **ФЗ-152 (Personal Data Law):** Requirements for personal data processing, encryption, and retention
- **ФЗ-323 (Healthcare):** Requirements for medical device data handling
- **ФСТЭК №21 (ИСПДн):** Information security requirements for personal data systems
- **Роспотребнадзор:** Food safety and temperature monitoring requirements

### International Standards

- **ISO 27001:** Information security management
- **ISO 27034:** Application security
- **IEC 62443:** Industrial automation and control systems security
- **NIST Cybersecurity Framework:** Security best practices

### Industry Standards

- **PCI DSS:** Payment card industry security (if IoT devices handle payments)
- **GDPR:** EU data protection (if serving EU customers)

---

## 7. Security Training Requirements

### Staff Training Topics

1. **Device Handling**
   - Proper installation and placement
   - Physical security measures
   - Reporting lost or stolen devices

2. **Security Awareness**
   - Recognizing suspicious device behavior
   - Reporting security incidents
   - Password and credential hygiene

3. **Operational Procedures**
   - Device onboarding process
   - Certificate management
   - Troubleshooting common issues

4. **Compliance**
   - Understanding regulatory requirements
   - Data privacy obligations
   - Audit preparation

### Training Schedule

- **New Hires:** Within first week
- **Refresher Training:** Quarterly
- **Security Updates:** Monthly
- **Incident Response Drills:** Biannually

---

## 8. Contact Information

### Security Team

- **Security Lead:** [Name] - [Email] - [Phone]
- **IoT Security Engineer:** [Name] - [Email] - [Phone]
- **Incident Response Team:** [Email] - [Phone]

### Emergency Contacts

- **24/7 Security Hotline:** [Phone]
- **On-Call Engineer:** [Phone]
- **Management Escalation:** [Phone]

---

## 9. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-23 | CatVRF Security Team | Initial version |

---

## Appendix A: Security Configuration Files

### A.1 Environment Variables (.env)

```env
# IoT Security Settings
IOT_TLS_ENABLED=true
IOT_TLS_VERSION=TLSv1.3
IOT_MTLS_ENABLED=true
IOT_RATE_LIMIT_ENABLED=true
IOT_ANOMALY_DETECTION_ENABLED=true
IOT_AUTO_QUARANTINE_ENABLED=true
IOT_REPLAY_PROTECTION_ENABLED=true

# Certificate Paths
IOT_TLS_CA_FILE=/etc/catvrf/certs/ca.crt
IOT_TLS_CERT_FILE=/etc/catvrf/certs/client.crt
IOT_TLS_KEY_FILE=/etc/catvrf/certs/client.key

# Rate Limiting
IOT_RATE_LIMIT_DEFAULT=60
IOT_RATE_LIMIT_BURST=100

# Anomaly Detection
IOT_ANOMALY_DEVIATION_THRESHOLD=0.5
IOT_ANOMALY_QUARANTINE_THRESHOLD=3
IOT_ANOMALY_TIME_WINDOW=10

# Certificate Management
IOT_CERT_ROTATION_DAYS=30
IOT_CERT_WARNING_DAYS=7
```

### A.2 Laravel Service Provider

```php
// app/Providers/IoTSecurityServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Restaurant\Application\Services\IoTSecurityService;
use Modules\Restaurant\Infrastructure\Repositories\EloquentDeviceCertificateRepository;
use Modules\Restaurant\Infrastructure\Repositories\EloquentIoTSecurityEventRepository;

class IoTSecurityServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(IoTSecurityService::class, function ($app) {
            return new IoTSecurityService(
                $app->make(IoTDeviceRepositoryInterface::class),
                $app->make(DeviceCertificateRepositoryInterface::class),
                $app->make(IoTSecurityEventRepositoryInterface::class),
            );
        });
    }
}
```

---

**Document Classification:** Internal Use Only  
**Last Updated:** 2026-04-23  
**Next Review:** 2026-07-23
