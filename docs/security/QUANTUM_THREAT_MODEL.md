# Quantum Threat Model for CatVRF

**Version:** 1.0  
**Date:** 23.04.2026  
**Status:** Draft for Review  
**Protection Level:** УЗ-3 (ИСПДн)  

## Executive Summary

This document provides a comprehensive analysis of quantum computing threats to CatVRF's cryptographic infrastructure, aligned with FSTEC BDU threat methodology and international research (NIST, Google, MIPT). It establishes the foundation for Post-Quantum Cryptography (PQC) migration and addresses "Harvest Now, Decrypt Later" (HNDL) attacks.

**Key Findings:**
- CRQC (Cryptographically Relevant Quantum Computer) expected by 2030 per MIPT/FSTEC estimates
- HNDL attacks already active against financial/healthcare data (Google Security Alert, Feb 2026)
- CatVRF components at risk: Passkeys/WebAuthn, Sanctum tokens, encrypted PII, behavioral vectors, KYB documents
- Immediate action required: Crypto-agility implementation, hybrid encryption for long-term data

---

## 1. Quantum Threat Landscape (April 2026)

### 1.1 Current State Assessment

**FSTEC Position (April 2026):**
- FSTEC has not yet dedicated a separate BDU section for quantum threats (unlike AI threats added Dec 2025)
- Experts from MIPT and FSTEC recognize quantum threats as emerging priority
- Expected CRQC timeline: 2028-2032 (conservative estimate)

**International Consensus:**
- **NIST:** PQC standards finalized (ML-KEM, ML-DSA, SLH-DSA) - migration recommended by 2024-2030
- **Google:** Active HNDL campaigns detected against healthcare/financial sectors (Feb 2026 security alert)
- **NSA/CISA:** Commercial National Security Algorithm Suite 2.0 (CNSA 2.0) mandates PQC transition by 2030

### 1.2 Quantum Algorithms Impact

| Algorithm | Target Cryptography | Impact | Timeline |
|-----------|-------------------|--------|----------|
| **Shor's Algorithm** | RSA, ECC, DSA, ECDSA | **Catastrophic** - Polynomial-time factorization | CRQC available (2028-2032) |
| **Grover's Algorithm** | AES, SHA-2, SHA-3 | **Moderate** - Quadratic speedup (AES-256 → AES-128 equivalent) | CRQC available (2028-2032) |
| **HNDL (Harvest Now, Decrypt Later)** | All encrypted data | **Critical** - Active threat today | **NOW** |

---

## 2. Quantum Threats to CatVRF Components

### 2.1 Threat Matrix

| Threat ID | Name | Description | CatVRF Components Affected | Probability | Impact | Risk Level |
|-----------|------|-------------|---------------------------|-------------|--------|------------|
| **QUANTUM-001** | **Shor's Algorithm - ECDSA Break** | Quantum factorization of elliptic curve private keys from public keys | Passkeys/WebAuthn (ECDSA), Sanctum token signing, TLS certificates | Medium (2028-2032) | **Critical** | **Critical** |
| **QUANTUM-002** | **Shor's Algorithm - RSA Break** | Quantum factorization of RSA private keys | Legacy RSA keys, JWT signing (if RSA-based), S3 encryption keys | Medium (2028-2032) | **Critical** | **Critical** |
| **QUANTUM-003** | **HNDL on Encrypted PII** | Collection of encrypted personal data for future decryption | EncryptedCast columns (email, phone, passport), behavioral vectors | **High (Active)** | **Critical** | **Critical** |
| **QUANTUM-004** | **HNDL on Biometric Data** | Collection of biometric vectors for future decryption | EncryptedBiometricVector, liveness data, face embeddings | **High (Active)** | **Critical** | **Critical** |
| **QUANTUM-005** | **HNDL on KYB Documents** | Collection of encrypted KYB documents for future decryption | Encrypted document storage, director passports, INN/OGRN | **High (Active)** | **High** | **High** |
| **QUANTUM-006** | **Grover's Algorithm - AES Weakening** | Quadratic speedup reduces AES-256 effective strength to AES-128 | All at-rest encryption (AES-256-GCM), EncryptedCast | Medium (2028-2032) | **High** | **High** |
| **QUANTUM-007** | **Grover's Algorithm - Hash Weakening** | Quadratic speedup reduces SHA-256 collision resistance | Password hashing (if SHA-256), data integrity checks | Medium (2028-2032) | **Medium** | **Medium** |
| **QUANTUM-008** | **Quantum-Side Channel Attacks** | Physical attacks on quantum sensors (future QKD deployments) | Future quantum key distribution systems | Low (Future) | **Medium** | **Low** |
| **QUANTUM-009** | **PQC Migration Vulnerabilities** | Exploitation during transition period (hybrid crypto misconfig) | PQC migration process, hybrid crypto wrappers | Medium (2026-2030) | **High** | **High** |
| **QUANTUM-010** | **AI Adversarial + Quantum** | Combined adversarial ML + quantum cryptanalysis | Behavioral biometrics, fraud detection ML models | Low (Future) | **Critical** | **Medium** |

### 2.2 Detailed Threat Analysis

#### QUANTUM-001: Shor's Algorithm - ECDSA Break

**Description:** Shor's algorithm can factor elliptic curve discrete logarithms in polynomial time, breaking ECDSA signatures used in Passkeys/WebAuthn.

**CatVRF Impact:**
- **Passkeys/WebAuthn:** ECDSA P-256/P-384 signatures become forgeable
- **Sanctum Tokens:** If ECDSA-based signing, tokens can be forged
- **TLS Certificates:** ECDSA certificates can be impersonated

**Scenario:**
1. Attacker collects public keys from Passkey registration responses
2. CRQC derives private keys using Shor's algorithm
3. Attacker forges WebAuthn assertions → ATO (Account Takeover)
4. Mass impersonation of tenants, doctors, patients

**Consequences:**
- Mass ATO across all user types
- Unauthorized access to medical records
- Financial losses from wallet compromises
- Regulatory violations (152-FZ, GDPR)

**Mitigation:**
- Migrate to PQC signatures (ML-DSA/Dilithium)
- Implement hybrid signing (ECDSA + PQC) during transition
- Add behavioral biometrics as quantum-resistant factor

---

#### QUANTUM-003/004: HNDL on Encrypted PII/Biometrics

**Description:** Attackers collect encrypted data today, store it, and decrypt later when CRQC becomes available.

**CatVRF Impact:**
- **EncryptedCast:** Email, phone, passport data stored with AES-256-GCM
- **EncryptedBiometricVector:** Face embeddings, behavioral patterns
- **Behavioral Vectors:** Keystroke/mouse patterns for continuous auth

**Scenario:**
1. Attacker compromises database or intercepts backups
2. Collects all encrypted columns (AES-256-GCM)
3. Stores encrypted data for 5-10 years
4. When CRQC available (2030+), decrypts using Grover + classical attacks
5. Exposes biometric data, contact lists, medical histories

**Consequences:**
- Irreversible biometric data exposure (can't change face/fingerprint)
- Targeted phishing with PII
- Deepfake generation with stolen biometrics
- Massive regulatory fines (up to 5% revenue under 152-FZ)

**Mitigation:**
- **Immediate:** Implement hybrid encryption (AES-256 + PQC) for long-term data
- Use PQC KEM (ML-KEM) for key exchange
- Encrypt behavioral vectors with quantum-resistant algorithms
- Reduce data retention periods for sensitive biometrics

---

#### QUANTUM-009: PQC Migration Vulnerabilities

**Description:** During transition period, misconfigured hybrid crypto or incomplete migration creates attack surface.

**CatVRF Impact:**
- Hybrid crypto wrapper implementation bugs
- Key management complexity (dual key pairs)
- Inconsistent algorithm selection across components

**Scenario:**
1. Hybrid crypto implemented with fallback to classical only
2. Attacker forces fallback path
3. Bypasses PQC protection
4. Exploits classical vulnerabilities

**Consequences:**
- False sense of security during migration
- Data leaks despite "PQC-ready" claims
- Audit failures

**Mitigation:**
- Strict crypto-agility with algorithm versioning
- No fallback to classical-only for critical data
- Comprehensive testing of hybrid implementations
- Gradual rollout with monitoring

---

## 3. Applied Measures in CatVRF

### 3.1 Current State (April 2026)

**Implemented:**
- ✅ AES-256-GCM for at-rest encryption (EncryptedCast, EncryptedBiometricVector)
- ✅ TLS 1.3 for in-transit encryption
- ✅ Laravel's built-in encryption (AES-256-CBC with APP_KEY)
- ✅ Behavioral biometrics as additional auth factor
- ✅ Cooldown system for anomaly detection

**Missing (Critical Gaps):**
- ❌ No PQC algorithms implemented
- ❌ No hybrid crypto wrappers
- ❌ No quantum threat detection in ThreatModelService
- ❌ No HNDL monitoring
- ❌ No crypto-agility framework
- ❌ No PQC migration plan

### 3.2 Recommended Measures

#### 3.2.1 Crypto-Agility Framework

**Implementation:**
- Abstract encryption interfaces (CryptoServiceInterface)
- Algorithm versioning in encrypted data (e.g., `v2:hybrid:aes256+mlkem`)
- Key rotation support without downtime
- Configuration-driven algorithm selection

**Components:**
```php
interface CryptoServiceInterface {
    public function encrypt(string $data, array $options = []): string;
    public function decrypt(string $data): ?string;
    public function getCurrentAlgorithm(): string;
    public function rotateKeys(string $oldVersion, string $newVersion): void;
}
```

#### 3.2.2 Hybrid Cryptography

**For Long-Term Data (PII, Biometrics, KYB):**
- Encrypt with AES-256-GCM + ML-KEM (Kyber) encapsulated key
- Format: `v2:hybrid:aes256+mlkem:<ciphertext>`
- Both algorithms must be broken to compromise data

**For Tokens/Session Data:**
- Continue with AES-256-CBC (short-lived, acceptable risk)
- Add PQC signature verification for critical operations

#### 3.2.3 PQC Migration Roadmap

**Phase 1: Foundation (Q2 2026)**
- Implement crypto-agility framework
- Add quantum threat detection to ThreatModelService
- Create hybrid crypto wrapper service
- Update FstecBduService to monitor quantum threats

**Phase 2: Long-Term Data Protection (Q3-Q4 2026)**
- Migrate EncryptedCast to hybrid encryption (AES-256 + ML-KEM)
- Migrate EncryptedBiometricVector to hybrid encryption
- Implement PQC key encapsulation for new KYB documents
- Background re-encryption job for existing data

**Phase 3: Authentication Transition (Q1-Q2 2027)**
- Evaluate Passkey/WebAuthn PQC options
- Implement hybrid signing for Sanctum tokens (ECDSA + ML-DSA)
- Add behavioral biometrics as quantum-resistant fallback
- Test PQC authentication in staging

**Phase 4: Full PQC Migration (2028-2030)**
- Replace ECDSA with ML-DSA (Dilithium) for new registrations
- Phase out classical-only encryption
- Full audit and compliance verification

#### 3.2.4 HNDL Detection & Response

**Monitoring:**
- Detect bulk data exfiltration attempts
- Monitor backup access patterns
- Alert on unusual database export activity
- Track quantum threat indicators from threat intelligence

**Response:**
- Automatic key rotation on detected exfiltration
- Re-encrypt affected data with stronger algorithms
- Notify users of potential exposure
- Audit trail for compliance

---

## 4. Integration with Threat Model Service

### 4.1 Quantum Risk Level Calculation

```php
quantum_risk_level = base_threat_level + hndl_risk_adjustment + component_criticality

Where:
- base_threat_level: 0.3 (CRQC expected by 2030)
- hndl_risk_adjustment: 0.4 (active HNDL campaigns detected)
- component_criticality: 
  - Biometrics: 0.3
  - PII: 0.2
  - KYB documents: 0.15
  - Tokens: 0.1
```

### 4.2 System Response to Quantum Risk

**When quantum_risk_level >= 0.7 (High):**
- Trigger Cooldown for suspicious authentication attempts
- Require fresh Passkey + liveness for sensitive operations
- Increase behavioral biometrics sampling rate
- Alert security team for review

**When quantum_risk_level >= 0.9 (Critical):**
- Force re-encryption of critical data with hybrid crypto
- Disable classical-only encryption for new data
- Require MFA for all operations
- Executive notification

---

## 5. Compliance & Audit Readiness

### 5.1 FSTEC Приказ №21 Alignment

| Measure | Status | Quantum Enhancement |
|---------|--------|---------------------|
| М.2.1 Идентификация и аутентификация | ✅ Implemented | Add PQC signatures, hybrid auth |
| М.2.4 Защита от подмены | ✅ Implemented | Quantum-resistant token validation |
| М.2.7 Биометрическая аутентификация | ✅ Implemented | Quantum-resistant behavioral biometrics |
| М.11 Шифрование | ✅ Implemented | Hybrid encryption (AES-256 + PQC) |
| М.12 Защита каналов связи | ✅ Implemented | PQC-enhanced TLS (post-quantum cipher suites) |

### 5.2 152-FZ Compliance

**Biometric Data Protection:**
- Enhanced encryption with PQC for long-term storage
- Reduced retention periods for raw biometrics
- Consent updates for quantum-resilient processing

**Data Localization:**
- PQC keys stored in Russian Federation
- No dependency on foreign quantum services

### 5.3 Audit Checklist Template

See `QUANTUM_AUDIT_CHECKLIST.md` for detailed audit preparation guide.

---

## 6. References

1. **FSTEC BDU:** https://bdu.fstec.ru/
2. **NIST PQC Standards:** FIPS 203 (ML-KEM), FIPS 204 (ML-DSA), FIPS 205 (SLH-DSA)
3. **Google Security Alert:** "Harvest Now, Decrypt Later Campaigns" (Feb 2026)
4. **NSA/CNSA 2.0:** Commercial National Security Algorithm Suite 2.0
5. **MIPT Research:** "Quantum Computing Timeline for Cryptography" (2025)
6. **Приказ ФСТЭК №21:** Меры защиты информации
7. **152-ФЗ:** О персональных данных

---

## Appendix A: Quantum Threat Detection Rules

```php
// Example detection rules for ThreatModelService
$quantumThreats = [
    [
        'id' => 'QUANTUM-HNDL-001',
        'name' => 'Bulk encrypted data exfiltration',
        'pattern' => 'large SELECT queries on encrypted columns',
        'risk_level' => 'critical',
        'response' => 'trigger_key_rotation',
    ],
    [
        'id' => 'QUANTUM-HNDL-002',
        'name' => 'Backup access anomaly',
        'pattern' => 'unusual backup download patterns',
        'risk_level' => 'high',
        'response' => 'audit_backup_access',
    ],
];
```

---

**Document Owner:** Security Team  
**Review Cycle:** Quarterly  
**Next Review:** July 2026
