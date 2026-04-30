# Quantum Threat Readiness Audit Checklist

**Version:** 1.0  
**Date:** 23.04.2026  
**Protection Level:** УЗ-3 (ИСПДн)  
**Compliance:** 152-FZ, FSTEC Приказ №21, NIST PQC Standards  

## Purpose

This checklist provides a comprehensive guide for demonstrating CatVRF's readiness for quantum computing threats to Roskomnadzor, FSTEC, and internal auditors. It covers threat detection, cryptographic measures, migration progress, and compliance verification.

---

## Section 1: Quantum Threat Assessment

### 1.1 Threat Landscape Documentation
- [ ] Quantum threat model document exists (`QUANTUM_THREAT_MODEL.md`)
- [ ] Threat matrix includes Shor's algorithm, Grover's algorithm, HNDL
- [ ] Component impact analysis completed (Passkeys, tokens, encrypted PII, biometrics)
- [ ] Scenario descriptions for each threat
- [ ] Consequence analysis for each threat
- [ ] References to FSTEC BDU, NIST, Google Security Alerts

### 1.2 Threat Detection & Monitoring
- [ ] `ThreatModelService` monitors quantum threats
- [ ] `FstecBduService` includes quantum threat data
- [ ] Quantum risk level calculation implemented
- [ ] HNDL (Harvest Now, Decrypt Later) detection rules active
- [ ] Automated alerts for quantum risk threshold breaches
- [ ] Regular threat landscape reviews scheduled

### 1.3 Risk Assessment
- [ ] Current quantum risk level documented
- [ ] Risk category determination (low, medium, high, critical)
- [ ] Component criticality mapping completed
- [ ] Risk adjustment factors defined
- [ ] Risk response triggers configured

**Audit Evidence:** Threat model document, service logs, risk assessment reports, monitoring dashboards

---

## Section 2: Cryptographic Measures

### 2.1 Crypto-Agility Framework
- [ ] `CryptoServiceInterface` defined and implemented
- [ ] Algorithm versioning in encrypted data (v1, v2, etc.)
- [ ] Key rotation support without downtime
- [ ] Configuration-driven algorithm selection
- [ ] Algorithm metadata extraction capability

### 2.2 Hybrid Encryption Implementation
- [ ] `HybridCryptoService` implemented
- [ ] AES-256 + ML-KEM (Kyber) hybrid encryption
- [ ] Fallback to classical encryption for compatibility
- [ ] Priority-based encryption selection (critical, high, medium)
- [ ] Encryption metadata tracking

### 2.3 Encrypted Data Protection
- [ ] `EncryptedCast` updated for hybrid encryption
- [ ] `EncryptedBiometricVector` updated for hybrid encryption
- [ ] KYB documents protected with PQC KEM
- [ ] Behavioral vectors encrypted with quantum-resistant algorithms
- [ ] Data retention policies for sensitive biometrics

### 2.4 Key Management
- [ ] PQC keys stored securely in Russian Federation
- [ ] Key rotation schedule defined
- [ ] Key backup and recovery procedures
- [ ] Key lifecycle management documented
- [ ] No dependency on foreign quantum services

**Audit Evidence:** Service implementations, encryption logs, key management policies, configuration files

---

## Section 3: Authentication & Access Control

### 3.1 Passkey/WebAuthn Security
- [ ] Passkey implementation reviewed for quantum vulnerabilities
- [ ] ECDSA signature risks documented
- [ ] PQC signature algorithm evaluation completed (ML-DSA/Dilithium)
- [ ] Hybrid signing implementation planned
- [ ] Behavioral biometrics as quantum-resistant fallback

### 3.2 Token Security
- [ ] Sanctum token signing algorithm documented
- [ ] JWT signature vulnerabilities assessed
- [ ] Hybrid token signing implementation planned
- [ ] Token version tracking implemented
- [ ] Token migration strategy defined

### 3.3 Multi-Factor Authentication
- [ ] MFA required for high-risk operations
- [ ] Behavioral biometrics sampling rate configured
- [ ] Liveness verification for sensitive operations
- [ ] Fresh Passkey requirement on quantum risk
- [ ] Cooldown integration with quantum risk

**Audit Evidence:** Authentication flow diagrams, security configurations, MFA policies, behavioral biometrics settings

---

## Section 4: Data Protection Measures

### 4.1 At-Rest Encryption
- [ ] AES-256-GCM implemented for all at-rest data
- [ ] Hybrid encryption for long-term data (PII, biometrics)
- [ ] Column-level encryption for sensitive fields
- [ ] Encryption key separation enforced
- [ ] Encryption algorithm versioning

### 4.2 In-Transit Encryption
- [ ] TLS 1.3 enforced for all communications
- [ ] Post-quantum cipher suites evaluated
- [ ] HSTS enabled
- [ ] Certificate management procedures
- [ ] mTLS for internal services

### 4.3 Data Retention & Minimization
- [ ] Data retention policies defined for biometrics
- [ ] Automatic purging of expired data
- [ ] Data minimization principles applied
- [ ] Consent management for PQC processing
- [ ] Right to be forgotten implemented

**Audit Evidence:** Encryption configurations, TLS certificates, retention policies, consent records

---

## Section 5: PQC Migration Progress

### 5.1 Migration Roadmap
- [ ] PQC migration roadmap exists (`PQC_MIGRATION_ROADMAP.md`)
- [ ] Phase 1 (Foundation) completed
- [ ] Phase 2 (Long-term data) in progress/completed
- [ ] Phase 3 (Authentication) planned
- [ ] Phase 4 (Full migration) scheduled

### 5.2 Migration Tracking
- [ ] Migration progress dashboard active
- [ ] Percentage of data migrated to hybrid encryption
- [ ] Percentage of new registrations using PQC
- [ ] Performance impact metrics tracked
- [ ] Rollback procedures documented

### 5.3 Testing & Validation
- [ ] Staging environment for PQC testing
- [ ] Performance benchmarks completed
- [ ] Regression test suite implemented
- [ ] Security testing of PQC implementations
- [ ] User acceptance testing completed

**Audit Evidence:** Migration roadmap, progress reports, test results, performance metrics

---

## Section 6: Compliance Verification

### 6.1 FSTEC Приказ №21 Compliance
- [ ] М.2.1 Идентификация и аутентификация - Implemented with PQC enhancements
- [ ] М.2.4 Защита от подмены - PQC signatures planned
- [ ] М.2.7 Биометрическая аутентификация - Quantum-resistant behavioral biometrics
- [ ] М.11 Шифрование - Hybrid encryption (AES-256 + PQC)
- [ ] All measures mapped to quantum enhancements

### 6.2 152-FZ Compliance
- [ ] Biometric data protection enhanced with PQC
- [ ] Data localization maintained (PQC keys in RF)
- [ ] Consent updates for quantum-resilient processing
- [ ] Data subject rights maintained
- [ ] Breach notification procedures updated

### 6.3 NIST PQC Standards Alignment
- [ ] ML-KEM (FIPS 203) implementation planned
- [ ] ML-DSA (FIPS 204) implementation planned
- [ ] SLH-DSA (FIPS 205) evaluated as backup
- [ ] NIST security level 5 adopted
- [ ] Standard updates monitored

### 6.4 Documentation
- [ ] Threat model document updated
- [ ] Migration roadmap maintained
- [ ] Security policies updated for PQC
- [ ] Incident response procedures updated
- [ ] Training materials for staff

**Audit Evidence:** Compliance matrix, policy documents, training records, incident response procedures

---

## Section 7: Incident Response

### 7.1 Quantum-Specific Incidents
- [ ] HNDL attack detection procedures
- [ ] Quantum vulnerability response plan
- [ ] PQC algorithm failure fallback
- [ ] Key compromise response procedures
- [ ] Data breach notification for quantum incidents

### 7.2 Monitoring & Alerting
- [ ] Real-time quantum risk monitoring
- [ ] Automated alerts for threshold breaches
- [ ] Security team notification procedures
- [ ] Executive notification for critical risks
- [ ] Incident escalation matrix

### 7.3 Recovery Procedures
- [ ] Data re-encryption procedures
- [ ] Key rotation emergency procedures
- [ ] System rollback procedures
- [ ] Backup restoration verification
- [ ] Post-incident analysis

**Audit Evidence:** Incident response plans, monitoring logs, alert configurations, drill reports

---

## Section 8: Staff Training & Awareness

### 8.1 Training Programs
- [ ] Quantum computing threats training for developers
- [ ] PQC implementation training for security team
- [ ] Operational procedures training for ops team
- [ ] Executive awareness briefings
- [ ] Regular refresher training scheduled

### 8.2 Documentation
- [ ] Developer guides for PQC implementation
- [ ] Operator manuals for hybrid crypto
- [ ] Troubleshooting guides
- [ ] Knowledge base articles
- [ ] FAQ documents

### 8.3 Awareness
- [ ] All staff aware of quantum threats
- [ ] Security champions identified
- [ ] Regular security communications
- [ ] Phishing simulations for quantum threats
- [ ] Security culture assessment

**Audit Evidence:** Training records, documentation library, communication logs, assessment reports

---

## Section 9: Third-Party & Supply Chain

### 9.1 Vendor Assessment
- [ ] PQC library vendors assessed
- [ ] Cloud provider quantum readiness evaluated
- [ ] Third-party encryption services reviewed
- [ ] Supply chain security for PQC components
- [ ] Vendor contracts updated for PQC requirements

### 9.2 Dependencies
- [ ] PQC library dependencies documented
- [ ] Vulnerability scanning for PQC components
- [ ] Update procedures for PQC libraries
- [ ] Backup plans for library failures
- [ ] Open source security assessments

### 9.3 Outsourced Services
- [ ] KYB document storage providers assessed
- [ ] Biometric processing services reviewed
- [ ] Cloud services quantum readiness verified
- [ ] Data processing agreements updated
- [ ] Service level agreements reviewed

**Audit Evidence:** Vendor assessments, dependency inventories, contracts, SLAs

---

## Section 10: Continuous Improvement

### 10.1 Monitoring
- [ ] Quantum threat landscape monitoring
- [ ] PQC standard updates tracking
- [ ] Performance metrics monitoring
- [ ] Security metrics tracking
- [ ] User experience monitoring

### 10.2 Reviews & Updates
- [ ] Quarterly threat model reviews
- [ ] Monthly migration progress reviews
- [ ] Annual compliance audits
- [ ] Regular security assessments
- [ ] Policy reviews and updates

### 10.3 Feedback & Improvement
- [ ] Incident post-mortems
- [ ] Lessons learned documentation
- [ ] Process improvement initiatives
- [ ] Technology watch for new PQC developments
- [ ] Industry best practices monitoring

**Audit Evidence:** Review schedules, assessment reports, improvement plans, lessons learned

---

## Audit Scoring

### Scoring Criteria
- **Fully Compliant (3):** All requirements met with documentation
- **Partially Compliant (2):** Most requirements met, some gaps
- **Not Compliant (1):** Significant gaps or missing implementation
- **Not Applicable (0):** Requirement does not apply

### Minimum Passing Score
- **Critical Sections (1-4):** Minimum 2.5 average
- **Overall:** Minimum 2.0 average

### Audit Report Template
```
Section | Score | Notes | Evidence
--------|-------|-------|----------
1. Threat Assessment | ___ | | |
2. Cryptographic Measures | ___ | | |
3. Authentication | ___ | | |
4. Data Protection | ___ | | |
5. Migration Progress | ___ | | |
6. Compliance | ___ | | |
7. Incident Response | ___ | | |
8. Training | ___ | | |
9. Third-Party | ___ | | |
10. Continuous Improvement | ___ | | |
TOTAL | ___ | | |
```

---

## Preparation Checklist for Roskomnadzor/FSTEC Audit

### Documentation Package
- [ ] Quantum threat model document
- [ ] PQC migration roadmap
- [ ] Compliance matrix (152-FZ, FSTEC №21, NIST)
- [ ] Security policies updated for PQC
- [ ] Incident response procedures
- [ ] Training records

### Technical Demonstrations
- [ ] Hybrid crypto service demonstration
- [ ] Quantum threat monitoring dashboard
- [ ] Encrypted data inspection (metadata)
- [ ] Authentication flow with behavioral biometrics
- [ ] Key management procedures

### Evidence Collection
- [ ] Service logs (ThreatModelService, FstecBduService)
- [ ] Encryption logs (HybridCryptoService)
- [ ] Migration progress reports
- [ ] Performance metrics
- [ ] Security assessment reports

### Personnel Readiness
- [ ] Security team briefed on quantum threats
- [ ] Technical staff trained on PQC implementation
- [ ] Executive team aware of migration status
- [ ] Spokesperson designated for audit
- [ ] Subject matter experts available

---

## Appendix A: Common Audit Questions

### Threat Assessment
**Q: How do you assess quantum computing threats?**  
A: We use ThreatModelService with quantum risk calculation based on FSTEC BDU data, NIST research, and Google Security Alerts. The risk level considers base threat (0.3), HNDL risk (0.4 active), and component criticality.

**Q: What is your current quantum risk level?**  
A: Our current quantum risk level is [X], categorized as [low/medium/high/critical]. This is calculated based on active HNDL threats, CRQC timeline (2030 per MIPT/FSTEC), and component criticality.

### Cryptographic Measures
**Q: How do you protect against HNDL attacks?**  
A: We implement hybrid encryption (AES-256 + ML-KEM) for long-term data (PII, biometrics, KYB documents). Both algorithms must be broken to compromise data, providing defense in depth.

**Q: What is your PQC migration timeline?**  
A: We follow a 4-phase roadmap (2026-2030): Foundation (Q2 2026), Long-term data protection (Q3-Q4 2026), Authentication transition (Q1-Q2 2027), Full migration (2028-2030).

### Compliance
**Q: How does PQC align with FSTEC Приказ №21?**  
A: We enhance existing measures: М.2.4 with PQC signatures, М.2.7 with quantum-resistant behavioral biometrics, М.11 with hybrid encryption. All measures maintain compliance while adding quantum resilience.

**Q: Is your data localized per 152-FZ?**  
A: Yes, all PQC keys and encrypted data are stored in the Russian Federation. We do not depend on foreign quantum services.

---

**Document Owner:** CISO / Compliance Officer  
**Review Cycle:** Quarterly  
**Next Audit:** [Date]  
**Auditor:** [Name/Organization]
