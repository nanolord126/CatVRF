# Post-Quantum Cryptography (PQC) Migration Roadmap

**Version:** 1.0  
**Date:** 23.04.2026  
**Status:** Draft for Approval  
**Project:** CatVRF - AI-powered Healthcare Marketplace  

## Executive Summary

This roadmap outlines the migration from classical cryptography to Post-Quantum Cryptography (PQC) for CatVRF, addressing the quantum computing threat landscape as of April 2026. The migration follows NIST standards (ML-KEM, ML-DSA, SLH-DSA) and aligns with FSTEC Приказ №21 requirements.

**Timeline:** 2026-2030 (4-year migration)  
**Budget:** TBD (requires resource allocation)  
**Risk Level:** High (quantum threats are active now via HNDL)

---

## Phase 1: Foundation & Crypto-Agility (Q2 2026)

### Objectives
- Implement crypto-agility framework
- Add quantum threat detection to existing services
- Create hybrid crypto wrapper service
- Establish PQC testing environment

### Tasks

#### 1.1 Crypto-Agility Framework
- [ ] Design `CryptoServiceInterface` with version support
- [ ] Implement algorithm versioning in encrypted data
- [ ] Add key rotation support without downtime
- [ ] Configuration-driven algorithm selection
- [ ] **Owner:** Security Team
- [ ] **Deadline:** End of May 2026

#### 1.2 Quantum Threat Detection
- [ ] Update `ThreatModelService` with quantum risk calculation
- [ ] Update `FstecBduService` with quantum threat monitoring
- [ ] Add HNDL (Harvest Now, Decrypt Later) detection rules
- [ ] Implement quantum risk response triggers
- [ ] **Owner:** Security Team
- [ ] **Deadline:** End of May 2026

#### 1.3 Hybrid Crypto Service
- [ ] Implement `HybridCryptoService` (AES-256 + ML-KEM placeholder)
- [ ] Add fallback to classical encryption
- [ ] Implement crypto metadata extraction
- [ ] Add priority-based encryption selection
- [ ] **Owner:** Security Team
- [ ] **Deadline:** End of June 2026

#### 1.4 Testing Environment
- [ ] Set up staging environment for PQC testing
- [ ] Install liboqs/php-pqc dependencies
- [ ] Create performance benchmarks
- [ ] Establish regression test suite
- [ ] **Owner:** DevOps Team
- [ ] **Deadline:** End of June 2026

### Success Criteria
- Crypto-agility framework deployed to production
- Quantum threats monitored in ThreatModelService
- Hybrid crypto service operational in staging
- PQC testing environment ready

### Risks & Mitigations
- **Risk:** PQC libraries not mature enough for production
  - **Mitigation:** Use hybrid approach with classical fallback
- **Risk:** Performance degradation
  - **Mitigation:** Benchmark and optimize before production deployment

---

## Phase 2: Long-Term Data Protection (Q3-Q4 2026)

### Objectives
- Migrate encrypted PII to hybrid encryption
- Migrate biometric data to hybrid encryption
- Implement PQC key encapsulation for KYB documents
- Background re-encryption of existing critical data

### Tasks

#### 2.1 Encrypted PII Migration
- [ ] Update `EncryptedCast` to use `HybridCryptoService`
- [ ] Migrate email, phone, passport columns
- [ ] Implement gradual migration with version tracking
- [ ] Add migration monitoring and alerts
- [ ] **Owner:** Backend Team
- [ ] **Deadline:** End of August 2026

#### 2.2 Biometric Data Migration
- [ ] Update `EncryptedBiometricVector` to use hybrid encryption
- [ ] Migrate face embeddings and liveness data
- [ ] Migrate behavioral vectors
- [ ] Implement data retention policy for raw biometrics
- [ ] **Owner:** Backend Team
- [ ] **Deadline:** End of September 2026

#### 2.3 KYB Documents Protection
- [ ] Implement PQC KEM for new KYB document uploads
- [ ] Migrate existing KYB documents to hybrid encryption
- [ ] Add document-level encryption metadata
- [ ] Implement secure document access logging
- [ ] **Owner:** KYB Team
- [ ] **Deadline:** End of October 2026

#### 2.4 Background Re-encryption
- [ ] Create `ReencryptCriticalDataJob` queue job
- [ ] Implement batch processing with rate limiting
- [ ] Add progress tracking and resume capability
- [ ] Monitor performance impact
- [ ] **Owner:** Backend Team
- [ ] **Deadline:** End of November 2026

### Success Criteria
- 100% of PII encrypted with hybrid crypto
- 100% of biometric data encrypted with hybrid crypto
- New KYB documents use PQC KEM
- Existing critical data re-encrypted

### Risks & Mitigations
- **Risk:** Database performance degradation during re-encryption
  - **Mitigation:** Use background jobs with rate limiting
- **Risk:** Data corruption during migration
  - **Mitigation:** Implement rollback and verification checks

---

## Phase 3: Authentication Transition (Q1-Q2 2027)

### Objectives
- Evaluate Passkey/WebAuthn PQC options
- Implement hybrid signing for Sanctum tokens
- Add behavioral biometrics as quantum-resistant fallback
- Test PQC authentication in staging

### Tasks

#### 3.1 Passkey/WebAuthn Evaluation
- [ ] Research PQC signature algorithms (ML-DSA/Dilithium)
- [ ] Evaluate WebAuthn PQC extensions
- [ ] Test browser compatibility
- [ ] Create POC for PQC Passkeys
- [ ] **Owner:** Auth Team
- [ ] **Deadline:** End of January 2027

#### 3.2 Sanctum Token Hybrid Signing
- [ ] Implement hybrid token signing (ECDSA + ML-DSA)
- [ ] Update token validation logic
- [ ] Add token version tracking
- [ ] Implement graceful token migration
- [ ] **Owner:** Auth Team
- [ ] **Deadline:** End of February 2027

#### 3.3 Behavioral Biometrics Enhancement
- [ ] Enhance behavioral biometrics as quantum-resistant factor
- [ ] Increase sampling rate for high-risk operations
- [ ] Implement behavioral anomaly detection
- [ ] Add liveness verification for sensitive operations
- [ ] **Owner:** Security Team
- [ ] **Deadline:** End of March 2027

#### 3.4 Staging Testing
- [ ] Deploy PQC authentication to staging
- [ ] Conduct load testing
- [ ] Test fallback mechanisms
- [ ] Gather performance metrics
- [ ] **Owner:** QA Team
- [ ] **Deadline:** End of April 2027

### Success Criteria
- PQC Passkey POC completed
- Sanctum tokens use hybrid signing in staging
- Behavioral biometrics enhanced as quantum-resistant
- Staging testing passes all acceptance criteria

### Risks & Mitigations
- **Risk:** Browser不支持 PQC signatures
  - **Mitigation:** Use hybrid approach with classical fallback
- **Risk:** User experience degradation
  - **Mitigation:** Transparent migration with fallback mechanisms

---

## Phase 4: Full PQC Migration (2028-2030)

### Objectives
- Replace ECDSA with ML-DSA for new registrations
- Phase out classical-only encryption
- Full audit and compliance verification
- Continuous monitoring of quantum threat landscape

### Tasks

#### 4.1 ML-DSA Implementation
- [ ] Implement ML-DSA (Dilithium) signature algorithm
- [ ] Update Passkey registration to use ML-DSA
- [ ] Phase out ECDSA for new registrations
- [ ] Maintain backward compatibility
- [ ] **Owner:** Auth Team
- [ ] **Deadline:** Q2 2028

#### 4.2 Classical-Only Phase-Out
- [ ] Disable classical-only encryption for new data
- [ ] Force hybrid encryption for all critical data
- [ ] Update documentation and training
- [ ] Monitor compliance
- [ ] **Owner:** Security Team
- [ ] **Deadline:** Q3 2028

#### 4.3 Audit & Compliance
- [ ] Conduct full PQC migration audit
- [ ] Verify FSTEC Приказ №21 compliance
- [ ] Update 152-FZ documentation
- [ ] Prepare Roskomnadzor audit report
- [ ] **Owner:** Compliance Team
- [ ] **Deadline:** Q4 2028

#### 4.4 Continuous Monitoring
- [ ] Monitor quantum threat landscape updates
- [ ] Track PQC standard updates (NIST, FSTEC)
- [ ] Regular security assessments
- [ ] Update threat model as needed
- [ ] **Owner:** Security Team
- [ ] **Deadline:** Ongoing (2029-2030)

### Success Criteria
- ML-DSA deployed for new Passkey registrations
- Classical-only encryption disabled
- Full audit passed
- Continuous monitoring operational

### Risks & Mitigations
- **Risk:** PQC standards change before migration complete
  - **Mitigation:** Crypto-agility framework allows algorithm updates
- **Risk:** Unexpected vulnerabilities in PQC algorithms
  - **Mitigation:** Hybrid approach provides defense in depth

---

## Resource Requirements

### Team Allocation
- **Security Team:** 2 FTE (full-time equivalent)
- **Backend Team:** 3 FTE
- **Auth Team:** 2 FTE
- **DevOps Team:** 1 FTE
- **QA Team:** 1 FTE
- **Compliance Team:** 0.5 FTE

### Budget Estimates
- **Development:** TBD
- **Infrastructure:** Additional servers for PQC operations
- **Training:** PQC training for development team
- **Consulting:** External PQC expert consultation (optional)
- **Audit:** External security audit for PQC compliance

### Technology Stack
- **PQC Library:** liboqs or php-pqc extension
- **Hybrid Crypto:** Custom implementation (HybridCryptoService)
- **Key Management:** Existing Laravel encryption + KMS integration
- **Monitoring:** Existing ThreatModelService + FstecBduService

---

## Compliance Alignment

### FSTEC Приказ №21
- **М.2.4:** Защита от подмены - PQC signatures
- **М.2.7:** Биометрическая аутентификация - Quantum-resistant behavioral biometrics
- **М.11:** Шифрование - Hybrid encryption (AES-256 + PQC)

### 152-FZ (Personal Data Protection)
- Enhanced encryption for biometric data
- Data retention policies for quantum-resilient processing
- Consent updates for PQC processing

### NIST Standards
- **FIPS 203:** ML-KEM (Kyber) for key encapsulation
- **FIPS 204:** ML-DSA (Dilithium) for digital signatures
- **FIPS 205:** SLH-DSA (SPHINCS+) for stateless hash-based signatures

---

## Monitoring & Reporting

### KPIs
- **Encryption Migration Rate:** % of data encrypted with hybrid crypto
- **PQC Adoption Rate:** % of new registrations using PQC
- **Quantum Risk Level:** Current quantum threat risk score
- **Performance Impact:** Latency increase due to PQC operations
- **Audit Findings:** Number of compliance issues

### Reporting Frequency
- **Weekly:** Migration progress updates
- **Monthly:** Quantum threat landscape review
- **Quarterly:** Executive summary and risk assessment
- **Annual:** Full compliance audit

### Escalation Matrix
- **Quantum Risk ≥ 0.7 (High):** Security team alert within 24 hours
- **Quantum Risk ≥ 0.9 (Critical):** Executive notification within 4 hours
- **Migration Blockers:** Weekly escalation to CTO

---

## Appendix A: PQC Algorithm Selection

### ML-KEM (Kyber)
- **Use Case:** Key encapsulation for hybrid encryption
- **Security Level:** NIST Level 5 (highest)
- **Performance:** Moderate overhead
- **Status:** NIST Standard (FIPS 203)

### ML-DSA (Dilithium)
- **Use Case:** Digital signatures for Passkeys and tokens
- **Security Level:** NIST Level 5
- **Performance:** Good performance
- **Status:** NIST Standard (FIPS 204)

### SLH-DSA (SPHINCS+)
- **Use Case:** Stateless hash-based signatures (backup)
- **Security Level:** NIST Level 5
- **Performance:** Higher overhead
- **Status:** NIST Standard (FIPS 205)

---

## Appendix B: Rollback Plan

### Phase 1 Rollback
- Revert to classical encryption if hybrid crypto causes critical issues
- Disable quantum threat monitoring if performance impact is severe

### Phase 2 Rollback
- Stop background re-encryption if database performance degrades
- Revert to classical encryption for new data if issues detected

### Phase 3 Rollback
- Disable PQC authentication if user experience degrades
- Revert to ECDSA if ML-DSA has vulnerabilities

### Phase 4 Rollback
- Enable classical-only encryption if PQC algorithms have critical vulnerabilities
- Revert to ECDSA if ML-DSA is broken

---

**Document Owner:** CTO / Security Lead  
**Review Cycle:** Monthly  
**Next Review:** May 2026  
**Approval Required:** CTO, CISO, Compliance Officer
