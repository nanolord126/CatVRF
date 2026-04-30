# CatVRF Enterprise Authentication Architecture 2026
**Updated Architecture with New Security Services**

**Date:** 19 April 2026  
**Architecture Score:** 7.2/10 → 9.5/10 (after implementation)

---

## 1. Overall Architecture

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           CATVRF AUTHENTICATION ARCHITECTURE 2026                   │
│                    (Ozon + Wildberries + Avito Enterprise Level)                    │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT LAYER                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │   Web App    │  │  Mobile App  │  │  API Clients │  │  AI Agents   │           │
│  │  (Vue 3)     │  │ (React Nat.) │  │  (External)  │  │  (M2M Auth)  │           │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘           │
│                                                                                     │
│  Components: PasskeyLogin.vue, VoiceEnrollment.vue, ConsentManager.vue,            │
│              ContinuousAuthMonitor, BiometricFusion, StepUpChallengeUI             │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              GATEWAY LAYER                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │ API Gateway  │  │ Rate Limiting│  │ Load Balancer│  │  WAF (DDoS)  │           │
│  │  (Traefik)   │  │  (Redis)     │  │   (Nginx)    │  │   (Cloudflare)│          │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘           │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION & AUTHORIZATION LAYER                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                        PASSKEYS & BIOMETRICS                                 │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │   Passkeys   │  │Voice Bio-    │  │Passive      │                     │   │
│  │  │ (WebAuthn)   │  │metrics (NEW) │  │Liveness (NEW)│                     │   │
│  │  │              │  │              │  │              │                     │   │
│  │  │• Registration│  │• Enrollment  │  │• Check every │                     │   │
│  │  │• Authentication│ │• Verification│  │  5 min      │                     │   │
│  │  │• Replay prev.│  │• Quality assess│ │• Spoof detect│                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  │  ┌─────────────────────────────────────────────────────────────────────┐   │   │
│  │  │              Multi-Modal Fusion Service (NEW)                       │   │   │
│  │  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  →  Overall Similarity     │   │   │
│  │  │  │ Face    │  │ Voice   │  │Behavior │      Score (0.00-1.00)      │   │   │
│  │  │  │ (40%)   │  │ (35%)   │  │ (25%)   │                             │   │   │
│  │  │  └─────────┘  └─────────┘  └─────────┘                             │   │   │
│  │  └─────────────────────────────────────────────────────────────────────┘   │   │
│  │                                                                             │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                              │                                      │
│                                              ▼                                      │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                    ADAPTIVE & CONTINUOUS AUTH                                │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  Adaptive    │  │  Continuous  │  │  Step-Up     │                     │   │
│  │  │  Auth        │  │  Auth        │  │  Challenge   │                     │   │
│  │  │  (Enhanced)  │  │  (Enhanced)  │  │  (NEW)       │                     │   │
│  │  │              │  │              │  │              │                     │   │
│  │  │• Risk scoring│  │• Passive     │  │• Orchestrate │                     │   │
│  │  │• Device rep  │  │  monitoring  │  │• Multi-factor│                     │   │
│  │  │• Geo-velocity│  │• Anomaly det.│  │• Passkey     │                     │   │
│  │  │• Time pattern│  │• Session risk│  │• Liveness    │                     │   │
│  │  │• ML scoring  │  │• Auto logout │  │• Voice       │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         SECURITY SERVICES LAYER (NEW)                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                    KYB + UBO + SANCTIONS (NEW - PRIORITY #1)                │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  KYB Service │  │  UBO Analysis│  │  Sanctions   │                     │   │
│  │  │  (Orchestr.) │  │  Service     │  │  Screening   │                     │   │
│  │  │              │  │              │  │  Service     │                     │   │
│  │  │• Verify biz  │  │• Extract     │  │• Росфин-     │                     │   │
│  │  │  registration│  │  chain (5lvl)│  │  мониторing  │                     │   │
│  │  │• Coordinate  │  │• Identify UBO│  │• OFAC        │                     │   │
│  │  │  workflow    │  │  (>25%)      │  │• EU          │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  PEP Screen  │  │  Adverse     │  │  Business    │                     │   │
│  │  │  Service     │  │  Media       │  │  Risk Score  │                     │   │
│  │  │              │  │  Monitoring  │  │  Service     │                     │   │
│  │  │• PEP lists   │  │  Service     │  │              │                     │   │
│  │  │• Politically │  │• News alerts │  │• Score 0-100 │                     │   │
│  │  │  exposed     │  │• Court rec.  │  │• Risk levels │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  │  External: Kontur.Focus, Spark Interfax, DaData, Росфинмониторинг, OFAC   │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                  CONSENT & PRIVACY ENGINE (NEW - PRIORITY #3)               │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  Consent     │  │  Privacy     │  │  Data        │                     │   │
│  │  │  Management  │  │  Engine      │  │  Deletion    │                     │   │
│  │  │  Service     │  │  Service     │  │  Service     │                     │   │
│  │  │              │  │              │  │              │                     │   │
│  │  │• Grant/revoke│  │• Minimization│  │• 72h deletion│                     │   │
│  │  │• 6+ purposes │  │• Retention   │  │• Full/partial│                     │   │
│  │  │• Versioning  │  │• Enforcement │  │• Anonymize   │                     │   │
│  │  │• Audit trail │  │• 152-ФZ check│  │• GDPR right  │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  │  Purposes: biometric_processing, behavioral_tracking, location_tracking,   │   │
│  │            ai_processing, marketing_communications, analytics               │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                    PAYMENT + AML/CTF (NEW - PRIORITY #4)                    │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  AML Screen  │  │  Transaction │  │  Wallet      │                     │   │
│  │  │  Service     │  │  Monitoring  │  │  Freeze      │                     │   │
│  │  │              │  │  Service     │  │  Service     │                     │   │
│  │  │• 115-ФZ check│  │• Real-time   │  │• Auto-freeze │                     │   │
│  │  │• Screen >10k │  │• Pattern det.│  │• Manual      │                     │   │
│  │  │• Report to   │  │• Alert rules │  │  review      │                     │   │
│  │  │  Росфинмон.  │  │• Thresholds  │  │• 4-eyes appr │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  │                                                                             │   │
│  │  External: YooKassa AML, Tinkoff Acquiring, СБП, Росфинмониторинг        │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────┐   │
│  │                    FUTURE SERVICES (Phase 3)                                │   │
│  ├─────────────────────────────────────────────────────────────────────────────┤   │
│  │                                                                             │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                     │   │
│  │  │  DID/VC      │  │  AI Agent    │  │  SIEM/ SOC   │                     │   │
│  │  │  Service     │  │  Auth        │  │  Integration │                     │   │
│  │  │              │  │              │  │              │                     │   │
│  │  │• Portable ID │  │• M2M tokens  │  │• Grafana     │                     │   │
│  │  │• W3C VC     │  │• Scoped perm │  │• Auto-IR     │                     │   │
│  │  │• Cross-tenant│  │• OAuth2      │  │• PagerDuty   │                     │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                     │   │
│  └─────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          BUSINESS LOGIC LAYER                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │ User Service │  │Business      │  │Tenant        │  │Wallet        │           │
│  │              │  │Service       │  │Service       │  │Service       │           │
│  │• Profile     │  │• Registration│  │• Multi-tenant│  │• Balance     │           │
│  │• Settings    │  │• KYB verify  │  │• Quota       │  │• Transactions│           │
│  │• Roles       │  │• UBO chain   │  │• Isolation   │  │• Freeze      │           │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘           │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              DATA LAYER                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │ PostgreSQL   │  │  Redis       │  │  ClickHouse  │  │  S3 Storage  │           │
│  │              │  │              │  │              │  │              │           │
│  │• Users       │  │• Cache       │  │• Audit logs  │  │• Documents   │           │
│  │• Tenants     │  │• Sessions    │  │• Security    │  │• Audio       │           │
│  │• Consents    │  │• Rate limits │  │  events      │  │• Images      │           │
│  │• KYB records │  │• Fraud cache │  │• Metrics     │  │• Videos      │           │
│  │• Voice bio   │  │• Locks       │  │• Anomaly det │  │              │           │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘           │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        EXTERNAL INTEGRATIONS                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│  KYB/Data:          Sanctions:         Voice/Liveness:     Payments:               │
│  • Kontur.Focus     • Росфинмониторинг • Voximplant        • YooKassa             │
│  • Spark Interfax   • OFAC             • VisionLabs        • Tinkoff Acquiring    │
│  • DaData           • EU Sanctions     • FaceTec           • СБП                  │
│                                                                                     │
│  Monitoring:        Compliance:        Infrastructure:                             │
│  • Grafana          • 152-ФZ           • AWS (ECS, RDS)                           │
│  • Prometheus       • 115-ФZ           • Terraform                                 │
│  • PagerDuty        • GDPR             • Blue-Green Deploy                         │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘

---

## 2. Service Dependencies

### 2.1 KYB + UBO + Sanctions (Priority #1)

```
BusinessRegistrationService
        │
        ▼
    KYBService (Orchestrator)
        │
        ├──► UBOAnalysisService
        │       │
        │       ├──► Kontur.Focus API
        │       └──► Spark Interfax API (fallback)
        │
        ├──► SanctionsScreeningService
        │       │
        │       ├──► Росфинмониторинг API
        │       ├──► OFAC API
        │       └──► EU Sanctions API
        │
        ├──► PEP Screening Service
        │       │
        │       └──► Dow Jones Risk & Compliance
        │
        ├──► Adverse Media Service
        │       │
        │       └──► News APIs
        │
        └──► BusinessRiskScoringService
                │
                └──► ML Model (LightGBM)
```

### 2.2 Continuous + Multi-modal Auth (Priority #2)

```
ContinuousAuthenticationMiddleware
        │
        ├──► BehavioralBiometricsService (Existing)
        │
        ├──► VoiceBiometricsService (NEW)
        │       │
        │       └──► Voximplant / Nuance API
        │
        ├──► PassiveLivenessService (NEW)
        │       │
        │       └──► VisionLabs / FaceTec API
        │
        ├──► MultiModalFusionService (NEW)
        │       │
        │       ├──► Face similarity
        │       ├──► Voice similarity
        │       └──► Behavioral similarity
        │
        └──► StepUpChallengeService (NEW)
                │
                ├──► Passkey challenge
                ├──► Liveness challenge
                └──► Voice challenge
```

### 2.3 Consent + Privacy Engine (Priority #3)

```
ConsentManagementService
        │
        ├──► ConsentAuditService
        │       │
        │       └──► ClickHouse (audit log)
        │
        ├──► DataDeletionService
        │       │
        │       ├──► Delete voice biometrics
        │       ├──► Delete behavioral data
        │       ├──► Delete liveness data
        │       └──► Anonymize user PII
        │
        └──► PrivacyEngineService
                │
                ├──► DataMinimizationService
                │       │
                │       ├──► Mask data
                │       ├──► Truncate data
                │       ├──► Hash data
                │       └──► Anonymize data
                │
                └──► Retention Policy Service
                        │
                        └──► Schedule cleanup jobs
```

---

## 3. Data Flow Diagrams

### 3.1 Business Registration with KYB

```
User (Business Owner)
        │
        │ 1. Submit business registration (INN, documents)
        ▼
BusinessRegistrationService
        │
        │ 2. Validate INN via DaData
        ▼
DaData API
        │
        │ 3. Return party data
        ▼
BusinessRegistrationService
        │
        │ 4. Create Tenant + BusinessGroup (status: pending)
        ▼
Database (PostgreSQL)
        │
        │ 5. Trigger KYB verification
        ▼
KYBService
        │
        │ 6. Extract UBO chain
        ▼
UBOAnalysisService
        │
        │ 7. Call Kontur.Focus API
        ▼
Kontur.Focus API
        │
        │ 8. Return ownership structure (up to 5 levels)
        ▼
UBOAnalysisService
        │
        │ 9. Store UBO chain in database
        ▼
Database (PostgreSQL)
        │
        │ 10. Screen all entities for sanctions
        ▼
SanctionsScreeningService
        │
        │ 11. Call Росфинмониторing, OFAC, EU APIs
        ▼
Sanctions APIs
        │
        │ 12. Return screening results
        ▼
SanctionsScreeningService
        │
        │ 13. Store screening results
        ▼
Database (PostgreSQL)
        │
        │ 14. Calculate risk score
        ▼
BusinessRiskScoringService
        │
        │ 15. Return risk score (0-100)
        ▼
KYBService
        │
        │ 16. Update KYB verification record
        ▼
Database (PostgreSQL)
        │
        │ 17. If critical risk → auto-reject
        │    If high/medium risk → manual review
        │    If low risk → auto-approve
        ▼
Filament Dashboard (Manual Review Queue)
        │
        │ 18. Compliance officer reviews
        ▼
BusinessRegistrationService
        │
        │ 19. Approve/Reject business
        ▼
Database (PostgreSQL)
        │
        │ 20. Notify user
        ▼
User (Business Owner)
```

### 3.2 Continuous Authentication Flow

```
User (Authenticated)
        │
        │ 1. Make API request with biometric signals
        ▼
ContinuousAuthenticationMiddleware
        │
        │ 2. Collect behavioral signals (typing, mouse, touch)
        ▼
BehavioralBiometricsService
        │
        │ 3. Analyze signals
        ▼
BehavioralBiometricsService
        │
        │ 4. Return behavioral similarity score
        ▼
ContinuousAuthenticationMiddleware
        │
        │ 5. Collect face/voice signals (if available)
        ▼
MultiModalFusionService
        │
        │ 6. Fuse signals (face + voice + behavior)
        ▼
MultiModalFusionService
        │
        │ 7. Return overall similarity score
        ▼
ContinuousAuthenticationMiddleware
        │
        │ 8. If similarity < threshold → trigger step-up
        ▼
StepUpChallengeService
        │
        │ 9. Issue step-up challenge (passkey/liveness/voice)
        ▼
User
        │
        │ 10. Complete challenge
        ▼
StepUpChallengeService
        │
        │ 11. Verify challenge
        ▼
ContinuousAuthenticationMiddleware
        │
        │ 12. If success → allow request
        │    If fail → logout user
        ▼
Response
```

### 3.3 Consent Management Flow

```
User
        │
        │ 1. Grant consent for purpose (e.g., biometric_processing)
        ▼
ConsentManagementService
        │
        │ 2. Create consent record
        ▼
Database (PostgreSQL)
        │
        │ 3. Audit log consent change
        ▼
ConsentAuditService
        │
        │ 4. Store audit log in ClickHouse
        ▼
ClickHouse
        │
        │ 5. Notify user
        ▼
User
        │
        │ 6. Later, revoke consent
        ▼
ConsentManagementService
        │
        │ 7. Update consent record (revoked_at)
        ▼
Database (PostgreSQL)
        │
        │ 8. Audit log consent revocation
        ▼
ConsentAuditService
        │
        │ 9. Store audit log in ClickHouse
        ▼
ClickHouse
        │
        │ 10. Trigger data deletion
        ▼
DataDeletionService
        │
        │ 11. Create deletion request (72-hour SLA)
        ▼
Database (PostgreSQL)
        │
        │ 12. Process deletion (async job)
        ▼
DataDeletionService
        │
        │ 13. Delete data based on purpose
        ▼
Database (PostgreSQL)
        │
        │ 14. Update deletion request status
        ▼
Database (PostgreSQL)
        │
        │ 15. Notify user
        ▼
User
```

---

## 4. Security Layers

### 4.1 Defense in Depth

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Network Security                                   │
│ • WAF (DDoS protection, SQL injection, XSS)                 │
│ • Rate limiting (per IP, per user)                          │
│ • IP reputation check                                       │
│ • Geo-blocking (if needed)                                  │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Authentication                                     │
│ • Passkeys (WebAuthn/FIDO2 Level 3)                         │
│ • Voice biometrics                                          │
│ • Behavioral biometrics                                     │
│ • Multi-modal fusion                                        │
│ • Step-up challenges                                        │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Authorization                                      │
│ • Role-based access control (RBAC)                          │
│ • Attribute-based access control (ABAC)                     │
│ • Tenant isolation                                          │
│ • Resource-level permissions                                │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: Fraud Detection                                    │
│ • FraudControlService (rate limits, device fingerprinting)   │
│ • AdaptiveAuthService (risk-based step-up)                   │
│ • ContinuousAuthenticationMiddleware (passive monitoring)    │
│ • ML-based anomaly detection                                │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 5: Business Compliance                                │
│ • KYB + UBO + Sanctions screening                           │
│ • PEP screening                                             │
│ • Adverse media monitoring                                  │
│ • AML/CTF screening                                         │
│ • Consent management (152-ФZ, GDPR)                         │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 6: Data Protection                                    │
│ • Encryption at rest (AES-256)                              │
│ • Encryption in transit (TLS 1.3)                           │
│ • Data minimization                                         │
│ • Retention policies                                        │
│ • Right-to-be-forgotten                                     │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│ Layer 7: Audit & Monitoring                                 │
│ • Audit logging (ClickHouse)                                │
│ • Security event logging                                    │
│ • Real-time monitoring (Grafana)                            │
│ • SIEM integration                                          │
│ • Automated incident response                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Technology Stack

### 5.1 Backend

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.x |
| PHP | PHP | 8.3+ |
| Database | PostgreSQL | 15+ |
| Cache | Redis | 7.x |
| Queue | Redis + Horizon | - |
| Audit Log | ClickHouse | 23.x |
| Storage | S3/MinIO | - |
| Search | Meilisearch (optional) | 1.x |

### 5.2 Frontend

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Vue 3 | 3.x |
| Language | TypeScript | 5.x |
| UI Library | shadcn/ui | Latest |
| Styling | TailwindCSS | 3.x |
| State | Pinia | 2.x |

### 5.3 Infrastructure

| Component | Technology | Version |
|-----------|-----------|---------|
| Container | Docker | 24.x |
| Orchestration | AWS ECS | - |
| Load Balancer | AWS ALB | - |
| Database | AWS RDS | 15.x |
| Cache | AWS ElastiCache | 7.x |
| Monitoring | Prometheus + Grafana | Latest |
| Logging | Loki | Latest |
| IaC | Terraform | 1.x |
| CI/CD | GitHub Actions | - |

### 5.4 External Services

| Service | Provider | Purpose |
|---------|----------|---------|
| Kontur.Focus | Kontur | KYB data |
| Spark Interfax | Spark | KYB data (fallback) |
| DaData | DaData | INN validation |
| Росфинмониторинг | Russian Gov | Sanctions screening |
| OFAC | US Treasury | Sanctions screening |
| Voximplant | Voximplant | Voice biometrics |
| VisionLabs | VisionLabs | Liveness detection |
| YooKassa | YooMoney | Payment processing |
| Tinkoff Acquiring | Tinkoff | Payment processing |

---

## 6. Performance Considerations

### 6.1 Latency Targets

| Operation | Target (p95) | Target (p99) |
|-----------|-------------|-------------|
| Passkey authentication | 200ms | 500ms |
| Voice verification | 500ms | 1000ms |
| Liveness check | 300ms | 800ms |
| KYB verification | 5s | 10s |
| Sanctions screening | 2s | 5s |
| Multi-modal fusion | 100ms | 200ms |
| Consent check | 50ms | 100ms |

### 6.2 Throughput Targets

| Operation | Target RPS |
|-----------|------------|
| Authentication | 10,000 |
| KYB verification | 100 |
| Sanctions screening | 500 |
| Continuous auth checks | 5,000 |
| Consent operations | 1,000 |

### 6.3 Caching Strategy

| Data Type | Cache TTL | Cache Backend |
|-----------|-----------|---------------|
| Consent records | 1 hour | Redis |
| KYB verification results | 24 hours | Redis |
| Sanctions screening results | 7 days | Redis |
| Voice biometric templates | 30 days | Redis |
| User risk scores | 15 minutes | Redis |

---

## 7. Scalability

### 7.1 Horizontal Scaling

- **Stateless services**: All auth services are stateless, can scale horizontally
- **Database**: Read replicas for read-heavy operations
- **Cache**: Redis Cluster for high availability
- **Queue**: Horizon with multiple supervisors

### 7.2 Vertical Scaling

- **KYB verification**: Dedicated queue with more workers
- **Sanctions screening**: Dedicated queue with rate limiting
- **ML inference**: GPU instances for model scoring

---

## 8. High Availability

### 8.1 Redundancy

- **Multi-AZ deployment**: Services deployed across multiple availability zones
- **Database failover**: Automatic failover to standby replica
- **Cache failover**: Redis Cluster with automatic failover
- **Load balancer**: Multiple instances with health checks

### 8.2 Disaster Recovery

- **Backups**: Daily database backups with 30-day retention
- **Point-in-time recovery**: PostgreSQL PITR
- **Geo-replication**: Cross-region replication for critical data
- **RTO**: 1 hour
- **RPO**: 15 minutes

---

**Document Version:** 1.0  
**Last Updated:** 19 April 2026  
**Next Review:** After implementation completion
