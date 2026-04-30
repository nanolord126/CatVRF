# Onboarding System Security Checklist

**Based on 200+ security articles by CatVRF Security Team**  
**Compliance:** 152-ФЗ, FZ-323, GDPR, PCI DSS

---

## Critical Security Requirements

### 1. PII Protection (152-ФZ Compliance)

- [x] **Consent Management:** User consent required before processing personal data
- [x] **Data Minimization:** Only collect necessary data (FIO, INN, documents)
- [x] **Right to Deletion:** Users can request data deletion
- [x] **Data Portability:** Users can export their data
- [x] **Anonymization:** Medical data anonymized before external AI processing
- [x] **Encryption at Rest:** All documents stored encrypted (AES-256)
- [x] **Encryption in Transit:** TLS 1.3 for all API calls

### 2. Fraud Prevention

- [x] **Rate Limiting:** 3 verification attempts per hour per user
- [x] **Fraud Control Service:** All operations pass through fraud check
- [x] **IP Monitoring:** Track suspicious IP addresses
- [x] **Device Fingerprinting:** Detect new devices
- [x] **Behavioral Analysis:** ML-based fraud detection
- [x] **Manual Review Queue:** Suspicious cases flagged for review
- [x] **Audit Trail:** Complete audit log in ClickHouse

### 3. Multi-tenancy Safety

- [x] **Tenant ID Scoping:** All queries scoped to tenant_id
- [x] **Zero Data Leakage:** Strict isolation between tenants
- [x] **Row-Level Security:** Database-level tenant filtering
- [x] **API Key Isolation:** Separate API keys per tenant
- [x] **Storage Isolation:** Separate S3 prefixes per tenant

### 4. AI/ML Security

- [x] **Liveness Detection:** Prevent photo/substitution attacks
- [x] **Deepfake Detection:** AI-based deepfake identification
- [x] **Face Matching:** Compare selfie with passport photo
- [x] **Score Thresholds:** Configurable auto-approve thresholds
- [x] **Fallback Handling:** Graceful degradation on AI failure
- [x] **Rate Limiting:** Prevent AI abuse
- [x] **Provider Isolation:** Multiple AI providers for redundancy

### 5. Document Security

- [x] **Secure Storage:** S3 with encryption at rest
- [x] **Access Control:** Role-based document access
- [x] **Temporary URLs:** Signed URLs with expiration
- [x] **Virus Scanning:** Scan uploaded documents (ClamAV)
- [x] **File Type Validation:** Strict MIME type checking
- [x] **Size Limits:** Max 10MB for documents, 5MB for photos
- [x] **OCR Privacy:** OCR processing in isolated environment

### 6. API Security

- [x] **Authentication:** Sanctum tokens required
- [x] **Authorization:** Role-based access control
- [x] **CORS:** Strict CORS policies
- [x] **Input Validation:** All inputs validated and sanitized
- [x] **Output Encoding:** Prevent XSS attacks
- [x] **SQL Injection:** Parameterized queries only
- [x] **CSRF Protection:** CSRF tokens on state-changing operations

### 7. Logging & Monitoring

- [x] **Audit Logging:** All operations logged to audit channel
- [x] **Security Logging:** Security events to security channel
- [x] **Fraud Alerts:** Suspicious activities to fraud_alert channel
- [x] **PII Masking:** Sensitive data masked in logs
- [x] **Log Retention:** 1 year retention for audit logs
- [x] **Log Integrity:** Immutable log storage
- [x] **Real-time Alerts:** PagerDuty/Slack integration

### 8. Network Security

- [ ] **WAF:** Web Application Firewall (Cloudflare/AWS WAF)
- [ ] **DDoS Protection:** DDoS mitigation (Cloudflare)
- [ ] **IP Allowlisting:** Admin panel IP restrictions
- [ ] **VPN Access:** VPN required for admin access
- [ ] **Network Segmentation:** Separate VPC for sensitive data
- [ ] **Firewall Rules:** Strict inbound/outbound rules

### 9. Infrastructure Security

- [ ] **Secrets Management:** Doppler/AWS Secrets Manager
- [ ] **Key Rotation:** Automatic key rotation every 90 days
- [ ] **Backup Encryption:** Backups encrypted at rest
- [ ] **Disaster Recovery:** Multi-region backup strategy
- [ ] **Patch Management:** Automated security patching
- [ ] **Vulnerability Scanning:** Daily automated scans
- [ ] **Penetration Testing:** Quarterly pentest by external firm

### 10. Compliance & Legal

- [x] **152-ФZ:** Personal data protection compliance
- [x] **FZ-323:** Medical data protection compliance
- [x] **GDPR:** EU data protection compliance
- [ ] **PCI DSS:** Payment card data compliance (if applicable)
- [ ] **SOC 2:** Security audit certification
- [ ] **ISO 27001:** Information security management
- [ ] **Privacy Policy:** Updated privacy policy

---

## High-Risk Areas

### 1. Document Upload Endpoint

**Risk:** File upload vulnerabilities, malware injection

**Mitigations:**
- [x] File type validation (MIME + magic bytes)
- [x] File size limits
- [x] Virus scanning (ClamAV)
- [x] Secure storage (encrypted S3)
- [ ] Sandboxed OCR processing
- [ ] File quarantine for suspicious files

### 2. AI Verification Endpoint

**Risk:** Deepfake bypass, replay attacks

**Mitigations:**
- [x] Liveness detection
- [x] Deepfake detection
- [x] Rate limiting
- [x] Challenge-response
- [ ] Biometric liveness challenge
- [ ] Device attestation

### 3. INN Validation Endpoint

**Risk:** Data exfiltration, API abuse

**Mitigations:**
- [x] Rate limiting
- [x] Fraud control
- [x] Caching (1 hour TTL)
- [x] Audit logging
- [ ] API key rotation
- [ ] IP allowlisting

### 4. Moderation Panel

**Risk:** Unauthorized access, data leakage

**Mitigations:**
- [ ] MFA required
- [ ] IP allowlisting
- [ ] Session timeout (15 min)
- [ ] Audit log for all actions
- [ ] Role-based permissions
- [ ] VPN requirement

---

## Incident Response

### Detection

- [ ] Real-time fraud alerts (Slack/PagerDuty)
- [ ] Anomaly detection (Prometheus alerts)
- [ ] SIEM integration (Splunk/ELK)
- [ ] User behavior analytics

### Response

- [ ] Incident response plan documented
- [ ] On-call rotation established
- [ ] Escalation matrix defined
- [ ] Communication templates ready
- [ ] Forensic capabilities enabled

### Recovery

- [ ] Backup restoration tested
- [ ] Disaster recovery plan tested
- [ ] Rollback procedures documented
- [ ] Post-incident review process

---

## Security Metrics

### Key Performance Indicators

- Fraud detection rate (>95%)
- False positive rate (<5%)
- Average verification time (<30s)
- API availability (>99.9%)
- Vulnerability remediation time (<7 days)

### Security KPIs

- Number of fraud attempts blocked
- Number of suspicious accounts flagged
- Average time to detect security incident
- Number of security incidents per quarter
- Security training completion rate

---

## Regular Security Reviews

- [ ] **Weekly:** Review fraud_alert logs
- [ ] **Monthly:** Security patch assessment
- [ ] **Quarterly:** Penetration testing
- [ ] **Semi-annually:** Security audit
- [ ] **Annually:** Compliance review

---

**Last Updated:** April 19, 2026  
**Next Review:** July 19, 2026  
**Security Team:** security@catvrf.ru
