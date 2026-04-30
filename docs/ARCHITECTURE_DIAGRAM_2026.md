# CatVRF Security Architecture Diagram 2026
## Text-Based Architecture with New Services

**Date:** April 19, 2026  
**Current Architecture Score:** 7.2/10 → **Target:** 9.5/10

---

## Overview

This diagram shows the current CatVRF security architecture with **NEW SERVICES** highlighted that need to be implemented to achieve enterprise marketplace compliance.

**Legend:**
- ✅ = Already Implemented
- 🔴 = NEW (To Be Implemented - P0 Critical)
- 🟡 = NEW (To Be Implemented - P1 High)
- 🟢 = NEW (To Be Implemented - P2 Medium)

---

## Layer 1: Client Layer

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Web App    │  │  Mobile App  │  │  Call Center │          │
│  │  (Vue/React) │  │ (React Native)│  │   (Phone)    │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                  │
│         └──────────────────┴──────────────────┘                  │
│                            │                                     │
│                    ┌───────▼────────┐                            │
│                    │  Behavioral SDK │ 🔴 NEW                   │
│                    │  (Typing/Mouse/ │                           │
│                    │   Touch/Voice)  │                           │
│                    └───────┬────────┘                            │
└────────────────────────────────┼─────────────────────────────────┘
                                 │
```

---

## Layer 2: API Gateway & Middleware

```
┌─────────────────────────────────────────────────────────────────┐
│                    API GATEWAY & MIDDLEWARE                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │              Rate Limiting & DDoS Protection          │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         ContinuousAuthenticationMiddleware            │ ⚠️    │
│  │         (Exists but calls missing service)           │       │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         BehavioralBiometricsService                   │ 🔴 NEW │
│  │  (TypingPattern, MouseDynamics, TouchGesture,        │       │
│  │   PassiveLiveness, VoiceBiometrics, MultiModalFusion)│       │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         RequirePasskey Middleware                    │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
└───────────────────────┼──────────────────────────────────────────┘
                        │
```

---

## Layer 3: Authentication & Identity Layer

```
┌─────────────────────────────────────────────────────────────────┐
│              AUTHENTICATION & IDENTITY LAYER                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         WebAuthnRegistrationService                   │ ✅   │
│  │         WebAuthnAuthenticationService                 │ ✅   │
│  │         WebAuthnCredentialService                     │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         AuthService                                  │ ✅   │
│  │         AdaptiveAuthService                           │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         DIDService (Decentralized Identity)           │ 🟡 NEW │
│  │         VerifiableCredentialsService                  │ 🟡 NEW │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         OAuth2 + mTLS (M2M Auth)                      │ 🟡 NEW │
│  │         ScopedTokenService (AI Agents)                │ 🟡 NEW │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
└───────────────────────┼──────────────────────────────────────────┘
                        │
```

---

## Layer 4: Authorization & Access Control

```
┌─────────────────────────────────────────────────────────────────┐
│              AUTHORIZATION & ACCESS CONTROL LAYER                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         FraudControlService                           │ ✅   │
│  │         FraudMLService                                │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         ConsentManagementService                      │ ⚠️    │
│  │         (Exists - needs enhancements)                  │       │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         DataMinimizationService                       │ 🔴 NEW │
│  │         RightToBeForgottenWorkflow                    │ 🔴 NEW │
│  │         DataRetentionPolicyService                     │ 🔴 NEW │
│  │         AIProviderConsentService                      │ 🔴 NEW │
│  │         ConsentVersioningService                      │ 🔴 NEW │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         Role-based Access Control                     │ ✅   │
│  │         Tenant Isolation                              │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
└───────────────────────┼──────────────────────────────────────────┘
                        │
```

---

## Layer 5: Business Verification (KYB)

```
┌─────────────────────────────────────────────────────────────────┐
│              BUSINESS VERIFICATION (KYB) LAYER                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         KYBService                                    │ ⚠️    │
│  │         (Skeleton - calls missing services)            │       │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│         ┌─────────────┼─────────────┐                          │
│         │             │             │                          │
│  ┌──────▼──────┐ ┌───▼────┐ ┌─────▼────────┐                   │
│  │UBOAnalysis  │ │Sanctions│ │ PEP Screening │ 🔴 NEW       │
│  │Service      │ │Service │ │Service       │                   │
│  │🔴 NEW       │ │🔴 NEW  │ │              │                   │
│  └──────┬──────┘ └───┬────┘ └─────┬────────┘                   │
│         │            │             │                           │
│  ┌──────▼────────────▼─────────────▼────────┐                  │
│  │   AdverseMediaScreeningService           │ 🔴 NEW          │
│  └────────────────────┬────────────────────┘                  │
│                       │                                          │
│  ┌────────────────────▼────────────────────┐                  │
│  │   LinkAnalysisService                    │ 🔴 NEW          │
│  │   (Graph DB for ownership patterns)      │                  │
│  └────────────────────┬────────────────────┘                  │
│                       │                                          │
│  ┌────────────────────▼────────────────────┐                  │
│  │   BusinessRiskScoringService             │ ✅               │
│  └────────────────────┬────────────────────┘                  │
│                       │                                          │
│  ┌────────────────────▼────────────────────┐                  │
│  │   KYBVerification Filament Dashboard     │ ✅ (Non-func)    │
│  └─────────────────────────────────────────┘                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Layer 6: Payment & Financial Layer

```
┌─────────────────────────────────────────────────────────────────┐
│              PAYMENT & FINANCIAL LAYER                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         PaymentService                                 │ ✅   │
│  │         WalletService                                   │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         AMLScreeningService                          │ 🔴 NEW │
│  │         TransactionMonitoringService                 │ 🔴 NEW │
│  │         SARReportService                              │ 🔴 NEW │
│  │         WalletFreezeService                           │ 🔴 NEW │
│  │         4EyesApprovalService                          │ 🔴 NEW │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         Payment Gateway Integration                   │ ✅   │
│  │         (YooKassa, SBP, Tinkoff)                     │       │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
└───────────────────────┼──────────────────────────────────────────┘
                        │
```

---

## Layer 7: Monitoring & Security Operations

```
┌─────────────────────────────────────────────────────────────────┐
│           MONITORING & SECURITY OPERATIONS LAYER                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         AuditService (ClickHouse)                     │ ✅   │
│  │         PrometheusMetrics                             │ ✅   │
│  │         Grafana Dashboards                            │ ✅   │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         SIEM Dashboard (Grafana + ClickHouse)         │ 🔴 NEW │
│  │         AutomatedIncidentResponseService              │ 🔴 NEW │
│  │         SOC Integration (Slack/Telegram/PagerDuty)    │ 🔴 NEW │
│  │         Security Alert Correlation                    │ 🔴 NEW │
│  └────────────────────┬─────────────────────────────────┘       │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────┐       │
│  │         Threat Intelligence Service                    │ 🟡 NEW │
│  │         Forensics Automation                         │ 🟡 NEW │
│  └──────────────────────────────────────────────────────┘       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Layer 8: Data & Infrastructure Layer

```
┌─────────────────────────────────────────────────────────────────┐
│              DATA & INFRASTRUCTURE LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         PostgreSQL (Primary DB)                       │ ✅   │
│  │         Redis (Cache + Queues)                        │ ✅   │
│  │         ClickHouse (Audit Logs)                       │ ✅   │
│  │         S3 (Documents, Images, Audio)                 │ ✅   │
│  │         Multi-tenancy                                 │ ✅   │
│  └──────────────────────────────────────────────────────┘       │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         Neo4j (Graph DB for Link Analysis)           │ 🟡 NEW │
│  │         (Optional - can use PostgreSQL)               │       │
│  └──────────────────────────────────────────────────────┘       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## External API Integrations

```
┌─────────────────────────────────────────────────────────────────┐
│              EXTERNAL API INTEGRATIONS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  KYB/Verification:                                               │
│  ┌────────────────┐  ┌────────────┐  ┌──────────────┐          │
│  │ Kontur.Focus   │  │ Spark      │  │ DaData       │          │
│  │ (ЕГРЮЛ)        │  │ Interfax   │  │ (Validation) │          │
│  └────────────────┘  └────────────┘  └──────────────┘          │
│                                                                  │
│  Sanctions/PEP:                                                  │
│  ┌────────────────┐  ┌────────────┐  ┌──────────────┐          │
│  │ Росфинмониторинг│ │ OFAC (US)  │  │ EU Sanctions │          │
│  └────────────────┘  └────────────┘  └──────────────┘          │
│  ┌────────────────┐  ┌────────────┐                           │
│  │ World-Check    │  │ Dow Jones  │                           │
│  │ (PEP)          │  │ (Adverse)  │                           │
│  └────────────────┘  └────────────┘                           │
│                                                                  │
│  Biometrics:                                                     │
│  ┌────────────────┐  ┌────────────┐                           │
│  │ Azure Voice ID │  │ Azure Face │                           │
│  │ (Voice)        │  │ API        │                           │
│  └────────────────┘  └────────────┘                           │
│                                                                  │
│  AI Providers:                                                   │
│  ┌────────────────┐  ┌────────────┐  ┌──────────────┐          │
│  │ OpenAI         │  │ Grok (X)   │  │ Anthropic    │          │
│  └────────────────┘  └────────────┘  └──────────────┘          │
│                                                                  │
│  Payment Gateways:                                               │
│  ┌────────────────┐  ┌────────────┐  ┌──────────────┐          │
│  │ YooKassa       │  │ SBP        │  │ Tinkoff      │          │
│  └────────────────┘  └────────────┘  └──────────────┘          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Service Dependencies

### KYB Service Dependencies
```
KYBService (Skeleton ✅)
├── UBOAnalysisService 🔴 NEW
│   └── Kontur.Focus API
├── SanctionsScreeningService 🔴 NEW
│   ├── Росфинмониторинг API
│   ├── OFAC API
│   ├── EU Sanctions API
│   └── UN Sanctions API
├── PEPScreeningService 🔴 NEW
│   └── World-Check API
├── AdverseMediaScreeningService 🔴 NEW
│   ├── News API (Google/Bing)
│   └── OpenAI (Sentiment)
└── LinkAnalysisService 🔴 NEW
    └── Neo4j (Optional)
```

### Continuous Auth Dependencies
```
ContinuousAuthenticationMiddleware ⚠️ (Broken)
└── BehavioralBiometricsService 🔴 NEW
    ├── TypingPatternService 🔴 NEW
    ├── MouseDynamicsService 🔴 NEW
    ├── TouchGestureService 🔴 NEW
    ├── PassiveLivenessService 🔴 NEW
    │   └── Azure Face API
    ├── VoiceBiometricsService 🔴 NEW
    │   └── Azure Voice ID API
    └── MultiModalFusionService 🔴 NEW
```

### Consent Engine Dependencies
```
ConsentManagementService ⚠️ (Partial)
├── DataMinimizationService 🔴 NEW
├── RightToBeForgottenWorkflow 🔴 NEW
├── DataRetentionPolicyService 🔴 NEW
├── AIProviderConsentService 🔴 NEW
└── ConsentVersioningService 🔴 NEW
```

### Payment Security Dependencies
```
PaymentService ✅
├── AMLScreeningService 🔴 NEW
│   ├── Росфинмониторинг API
│   ├── OFAC API
│   └── EU Sanctions API
├── TransactionMonitoringService 🔴 NEW
├── SARReportService 🔴 NEW
├── WalletFreezeService 🔴 NEW
└── 4EyesApprovalService 🔴 NEW
```

---

## Data Flow Examples

### KYB Verification Flow
```
1. B2B Seller Registration
   ↓
2. KYBService.startVerification()
   ↓
3. UBOAnalysisService.extractChain() → Kontur.Focus API
   ↓
4. SanctionsScreeningService.screenAllEntities()
   → Росфинмониторинг, OFAC, EU, UN APIs
   ↓
5. PEPScreeningService.screenAllEntities() → World-Check API
   ↓
6. AdverseMediaScreeningService.screenAllEntities()
   → News API + OpenAI Sentiment
   ↓
7. LinkAnalysisService.buildOwnershipGraph() → Neo4j
   ↓
8. BusinessRiskScoringService.calculateRisk()
   ↓
9. KYBVerification record updated
   ↓
10. Manual review in Filament (if required)
```

### Continuous Authentication Flow
```
1. User logs in with Passkey
   ↓
2. ContinuousAuthenticationMiddleware activated
   ↓
3. BehavioralSDK collects typing/mouse/touch data
   ↓
4. BehavioralBiometricsService.analyzeSignals()
   → TypingPatternService
   → MouseDynamicsService
   → TouchGestureService
   ↓
5. MultiModalFusionService.fuse()
   ↓
6. Anomaly detection
   ↓
7. If anomalous → step-up authentication
   → PassiveLivenessService (face verification)
   → VoiceBiometricsService (voice verification)
   ↓
8. Update session risk score
   ↓
9. If high risk → logout user
```

### Right-to-Be-Forgotten Flow
```
1. User requests data deletion
   ↓
2. RightToBeForgottenWorkflow.execute()
   ↓
3. Schedule deletion job (72-hour SLA)
   ↓
4. Send confirmation notification
   ↓
5. After 72 hours → DeleteUserDataJob
   ↓
6. Delete behavioral_baselines
   ↓
7. Delete behavioral_samples
   ↓
8. Delete voice_profiles
   ↓
9. Delete face_references (from S3)
   ↓
10. Delete consent_records
   ↓
11. Delete/anonymize KYB verifications
   ↓
12. Anonymize audit logs
   ↓
13. Anonymize payment transactions
   ↓
14. Delete medical records
   ↓
15. Delete user account
   ↓
16. VerifyDeletion()
   ↓
17. Audit log record
```

---

## Implementation Priority

### Phase 1: P0 Critical (Weeks 1-8)
```
Week 1-4: KYB Dependencies
├── UBOAnalysisService
├── SanctionsScreeningService
├── PEPScreeningService
├── AdverseMediaScreeningService
└── LinkAnalysisService

Week 5-8: Continuous Auth + Consent Engine
├── BehavioralBiometricsService
├── TypingPatternService
├── MouseDynamicsService
├── TouchGestureService
├── DataMinimizationService
└── RightToBeForgottenWorkflow
```

### Phase 2: P1 High (Weeks 9-14)
```
Week 9-11: AML Integration
├── AMLScreeningService
├── TransactionMonitoringService
├── SARReportService
├── WalletFreezeService
└── 4EyesApprovalService

Week 12-14: Privacy Engine Complete
├── DataRetentionPolicyService
├── AIProviderConsentService
└── ConsentVersioningService
```

### Phase 3: P2 Medium (Weeks 15-24)
```
Week 15-20: DID/VC Support
├── DIDService
├── VerifiableCredentialsService
└── Russian Digital Profile Integration

Week 21-24: Monitoring & SOC
├── SIEM Dashboard
├── AutomatedIncidentResponseService
└── SOC Integration
```

---

## Architecture Score Impact

| Layer | Current | Target | Gap | Priority |
|-------|---------|--------|-----|----------|
| Client (Behavioral SDK) | 0/10 | 9.0/10 | -9.0 | P0 |
| API Gateway (Continuous Auth) | 3/10 | 9.0/10 | -6.0 | P0 |
| Authentication (DID/VC) | 0/10 | 8.0/10 | -8.0 | P2 |
| Authorization (Privacy Engine) | 5/10 | 9.5/10 | -4.5 | P0 |
| KYB (Dependencies) | 2/10 | 9.5/10 | -7.5 | P0 |
| Payment (AML/CTF) | 0/10 | 9.0/10 | -9.0 | P1 |
| Monitoring (SIEM/SOC) | 5/10 | 9.0/10 | -4.0 | P2 |
| **OVERALL** | **7.2/10** | **9.5/10** | **-2.3** | - |

---

**Document Status:** Complete  
**Next Step:** Implement KYB/UBO service (UBOAnalysisService - first service)
