# Technical Specification: Continuous Authentication Service
**CatVRF Healthcare Marketplace**
**Priority:** CRITICAL
**Complexity:** High
**Estimated Effort:** 3-4 weeks
**Date:** April 19, 2026

---

## 1. Overview

### 1.1 Problem Statement

Current authentication in CatVRF is event-based (login only). Once a user is authenticated, the session remains trusted until logout, regardless of user behavior during the session. This creates a significant security vulnerability:

- Compromised sessions remain active until logout
- Account takeover attacks can persist for hours/days
- No detection of behavioral anomalies during session
- No progressive authentication (trust decay over time)

### 1.2 Solution

Implement **Continuous Authentication** - passive monitoring of user behavior throughout the session with silent risk scoring and step-up re-challenge when anomalies are detected.

### 1.3 Key Features

1. **Passive Liveness Monitoring:** Continuous verification that the authenticated user is still the same person
2. **Silent Risk Scoring:** Background risk assessment every 5-10 minutes
3. **Anomaly Detection:** Behavioral biometrics comparison against baseline
4. **Step-up Re-challenge:** Trigger Passkey + liveness check on high risk
5. **Trust Decay:** Progressive authentication requirements over time
6. **Context-Aware Termination:** Auto-terminate sessions on critical anomalies

### 1.4 Success Criteria

- False positive rate < 1% (users not incorrectly challenged)
- Detection rate > 95% for session hijacking
- Performance impact < 50ms per scoring event
- Zero user friction for normal behavior

---

## 2. Architecture

### 2.1 Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     Continuous Authentication Layer              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │  SessionMonitor  │───▶│ RiskScoreEngine  │                   │
│  │  (Middleware)    │    │ (Background Job) │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ BehavioralData   │───▶│ AnomalyDetector  │                   │
│  │ Collector        │    │ (ML Model)       │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ TrustDecayEngine │───▶│ ChallengeManager │                   │
│  │ (Time-based)     │    │ (Step-up Auth)   │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ SessionTerminator│    │  AuditLogger     │                   │
│  │ (Auto-terminate) │    │ (ClickHouse)     │                   │
│  └──────────────────┘    └──────────────────┘                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow

```
User Action → SessionMonitor → BehavioralDataCollector → AnomalyDetector
                                                          │
                                                          ▼
                                                    RiskScoreEngine
                                                          │
                                                          ▼
                                                    TrustDecayEngine
                                                          │
                                                          ▼
                                                    ChallengeManager
                                                          │
                                    ┌─────────────────────┼─────────────────────┐
                                    ▼                     ▼                     ▼
                              Low Risk            Medium Risk           High Risk
                                    │                     │                     │
                                    ▼                     ▼                     ▼
                              Continue Session    Silent Log         Step-up Challenge
                                                     (Alert)           (Passkey+Liveness)
                                                                          │
                                                                          ▼
                                                                    Failed → Terminate
                                                                    Success → Continue
```

### 2.3 Integration Points

- **Existing:** BehavioralBiometricsService, VoiceBiometricsService, DeepfakeDetectionService
- **New:** ContinuousAuthService, RiskScoreEngine, ChallengeManager, TrustDecayEngine
- **Middleware:** ContinuousAuthMiddleware (session monitoring)
- **Jobs:** ContinuousScoringJob (background risk scoring)
- **Storage:** Session risk scores in Redis, audit logs in ClickHouse

---

## 3. Detailed Specifications

### 3.1 ContinuousAuthService

**Location:** `app/Services/Security/ContinuousAuthService.php`

**Responsibilities:**
- Orchestrate continuous authentication flow
- Manage session risk scores
- Coordinate with behavioral biometrics services
- Trigger step-up challenges

**Key Methods:**

```php
final readonly class ContinuousAuthService
{
    public function __construct(
        private readonly BehavioralBiometricsService $behavioral,
        private readonly VoiceBiometricsService $voice,
        private readonly RiskScoreEngine $riskEngine,
        private readonly TrustDecayEngine $trustDecay,
        private readonly ChallengeManager $challengeManager,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Start continuous monitoring for a session
     */
    public function startMonitoring(string $sessionId, int $userId): void;

    /**
     * Collect behavioral data point
     */
    public function collectBehavioralData(string $sessionId, array $data): void;

    /**
     * Perform risk scoring (called by background job)
     */
    public function scoreSession(string $sessionId): array;

    /**
     * Check if session requires step-up challenge
     */
    public function requiresChallenge(string $sessionId): bool;

    /**
     * Execute step-up challenge
     */
    public function executeChallenge(string $sessionId): array;

    /**
     * Stop monitoring for a session
     */
    public function stopMonitoring(string $sessionId): void;

    /**
     * Terminate session due to critical risk
     */
    public function terminateSession(string $sessionId, string $reason): void;
}
```

### 3.2 RiskScoreEngine

**Location:** `app/Services/Security/RiskScoreEngine.php`

**Responsibilities:**
- Calculate composite risk score from multiple signals
- Weighted scoring (behavioral, contextual, temporal)
- Real-time risk aggregation

**Risk Factors:**

| Factor | Weight | Description |
|--------|--------|-------------|
| Behavioral Anomaly | 40% | Deviation from baseline (typing, mouse, touch) |
| Geographic Anomaly | 20% | IP/location change |
| Device Anomaly | 15% | Device fingerprint change |
| Temporal Anomaly | 10% | Unusual time of activity |
| Velocity Anomaly | 10% | Unusual action speed |
| Trust Decay | 5% | Time-based trust reduction |

**Risk Levels:**

- **Low (0-30):** Continue session normally
- **Medium (30-60):** Silent log + increased monitoring
- **High (60-80):** Step-up challenge required
- **Critical (80-100):** Immediate session termination

**Key Methods:**

```php
final readonly class RiskScoreEngine
{
    public function __construct(
        private readonly BehavioralAnomalyDetector $behavioralDetector,
        private readonly GeographicAnomalyDetector $geoDetector,
        private readonly DeviceAnomalyDetector $deviceDetector,
    ) {}

    /**
     * Calculate composite risk score
     */
    public function calculateRisk(string $sessionId): array;

    /**
     * Get individual risk factor scores
     */
    public function getFactorScores(string $sessionId): array;

    /**
     * Update risk score with new data point
     */
    public function updateRisk(string $sessionId, array $dataPoint): array;
}
```

### 3.3 TrustDecayEngine

**Location:** `app/Services/Security/TrustDecayEngine.php`

**Responsibilities:**
- Implement progressive authentication (trust decay over time)
- Calculate trust score based on session age and activity
- Determine when re-authentication is required

**Trust Decay Formula:**

```
Trust Score = Initial Trust × e^(-λ × Time)

Where:
- Initial Trust = 100 (at login)
- λ = decay rate (configurable, default 0.1 per hour)
- Time = hours since last strong auth
```

**Re-authentication Thresholds:**

- **Trust > 70:** No re-auth required
- **Trust 50-70:** Consider re-auth for sensitive operations
- **Trust < 50:** Require re-auth before any operation

**Key Methods:**

```php
final readonly class TrustDecayEngine
{
    public function __construct() {}

    /**
     * Calculate current trust score
     */
    public function calculateTrust(string $sessionId): float;

    /**
     * Check if re-authentication is required
     */
    public function requiresReAuth(string $sessionId): bool;

    /**
     * Reset trust score (after successful re-auth)
     */
    public function resetTrust(string $sessionId): void;

    /**
     * Get time until next re-auth required
     */
    public function getTimeUntilReAuth(string $sessionId): int; // minutes
}
```

### 3.4 ChallengeManager

**Location:** `app/Services/Security/ChallengeManager.php`

**Responsibilities:**
- Execute step-up challenges
- Coordinate with Passkey and liveness services
- Manage challenge state and results

**Challenge Types:**

1. **Passkey Only:** Medium risk
2. **Passkey + Liveness:** High risk
3. **Passkey + Liveness + Voice:** Critical risk

**Key Methods:**

```php
final readonly class ChallengeManager
{
    public function __construct(
        private readonly WebAuthnAuthenticationService $passkey,
        private readonly DeepfakeDetectionService $liveness,
        private readonly VoiceBiometricsService $voice,
    ) {}

    /**
     * Execute appropriate challenge based on risk level
     */
    public function executeChallenge(string $sessionId, int $riskLevel): array;

    /**
     * Execute passkey-only challenge
     */
    public function executePasskeyChallenge(string $sessionId): array;

    /**
     * Execute passkey + liveness challenge
     */
    public function executeBiometricChallenge(string $sessionId): array;

    /**
     * Execute full challenge (passkey + liveness + voice)
     */
    public function executeFullChallenge(string $sessionId): array;

    /**
     * Verify challenge response
     */
    public function verifyChallenge(string $sessionId, array $response): bool;
}
```

### 3.5 ContinuousAuthMiddleware

**Location:** `app/Http/Middleware/ContinuousAuthMiddleware.php`

**Responsibilities:**
- Intercept all authenticated requests
- Collect behavioral data
- Check for required challenges
- Enforce session termination

**Key Methods:**

```php
final class ContinuousAuthMiddleware
{
    public function __construct(
        private readonly ContinuousAuthService $continuousAuth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        // Skip if not monitoring this session
        if (!$this->continuousAuth->isMonitoring($sessionId)) {
            return $next($request);
        }

        // Collect behavioral data
        $this->continuousAuth->collectBehavioralData($sessionId, [
            'timestamp' => now(),
            'action' => $request->route()->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'typing_pattern' => $request->header('X-Typing-Pattern'),
            'mouse_dynamics' => $request->header('X-Mouse-Dynamics'),
        ]);

        // Check if challenge required
        if ($this->continuousAuth->requiresChallenge($sessionId)) {
            return $this->handleChallengeRequired($request, $sessionId);
        }

        // Check if session terminated
        if ($this->continuousAuth->isTerminated($sessionId)) {
            return $this->handleTerminatedSession($request);
        }

        return $next($request);
    }
}
```

### 3.6 ContinuousScoringJob

**Location:** `app/Jobs/ContinuousScoringJob.php`

**Responsibilities:**
- Background job to score active sessions
- Runs every 5-10 minutes
- Updates risk scores in Redis
- Triggers challenges if needed

**Key Methods:**

```php
final class ContinuousScoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
    ) {}

    public function handle(ContinuousAuthService $continuousAuth): void
    {
        $continuousAuth->scoreSession($this->sessionId);
    }
}
```

---

## 4. Database Schema

### 4.1 session_risk_scores Table

**Migration:** `database/migrations/2026_04_19_000003_create_session_risk_scores_table.php`

```php
Schema::create('session_risk_scores', function (Blueprint $table) {
    $table->id();
    $table->string('session_id')->unique();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');
    
    // Risk scores
    $table->integer('overall_risk_score')->default(0); // 0-100
    $table->string('risk_level')->default('low'); // low, medium, high, critical
    $table->json('factor_scores')->nullable(); // individual factor scores
    
    // Trust decay
    $table->float('trust_score')->default(100.0); // 0-100
    $table->timestamp('trust_reset_at')->nullable();
    
    // Anomalies
    $table->json('behavioral_anomalies')->nullable();
    $table->json('geographic_anomalies')->nullable();
    $table->json('device_anomalies')->nullable();
    
    // Challenges
    $table->boolean('challenge_required')->default(false);
    $table->string('challenge_type')->nullable();
    $table->timestamp('challenge_triggered_at')->nullable();
    $table->timestamp('challenge_completed_at')->nullable();
    $table->boolean('challenge_passed')->nullable();
    
    // Session state
    $table->boolean('terminated')->default(false);
    $table->string('termination_reason')->nullable();
    $table->timestamp('terminated_at')->nullable();
    
    // Timestamps
    $table->timestamp('last_scored_at')->nullable();
    $table->timestamp('monitoring_started_at')->nullable();
    $table->timestamp('monitoring_ended_at')->nullable();
    $table->timestamps();
    
    // Indexes
    $table->index('user_id');
    $table->index('tenant_id');
    $table->index('risk_level');
    $table->index('challenge_required');
    $table->index('terminated');
    $table->index('last_scored_at');
});
```

### 4.2 behavioral_data_points Table

**Migration:** `database/migrations/2026_04_19_000004_create_behavioral_data_points_table.php`

```php
Schema::create('behavioral_data_points', function (Blueprint $table) {
    $table->id();
    $table->string('session_id');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Behavioral data
    $table->string('action'); // route name or action type
    $table->json('typing_pattern')->nullable();
    $table->json('mouse_dynamics')->nullable();
    $table->json('touch_gestures')->nullable();
    
    // Contextual data
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->json('device_fingerprint')->nullable();
    $table->point('location')->nullable(); // PostGIS for geo
    
    // Timestamp
    $table->timestamp('collected_at');
    
    // Indexes
    $table->index('session_id');
    $table->index('user_id');
    $table->index('collected_at');
    
    // TTL: 30 days (retention policy)
    $table->timestamp('expires_at')->nullable();
});
```

---

## 5. Configuration

**File:** `config/continuous_auth.php`

```php
return [
    // Enable/disable continuous authentication
    'enabled' => env('CONTINUOUS_AUTH_ENABLED', true),
    
    // Scoring interval (seconds)
    'scoring_interval' => env('CONTINUOUS_AUTH_SCORING_INTERVAL', 300), // 5 minutes
    
    // Risk thresholds
    'risk_thresholds' => [
        'medium' => 30,
        'high' => 60,
        'critical' => 80,
    ],
    
    // Trust decay
    'trust_decay' => [
        'enabled' => true,
        'decay_rate' => 0.1, // per hour
        'reset_on_reauth' => true,
        'reauth_threshold' => 50, // trust score below this requires re-auth
    ],
    
    // Behavioral data collection
    'behavioral_collection' => [
        'typing_pattern' => true,
        'mouse_dynamics' => true,
        'touch_gestures' => true,
        'device_fingerprint' => true,
    ],
    
    // Data retention
    'retention' => [
        'behavioral_data_days' => 30,
        'risk_scores_days' => 90,
    ],
    
    // Challenge types
    'challenges' => [
        'medium_risk' => 'passkey_only',
        'high_risk' => 'passkey_liveness',
        'critical_risk' => 'passkey_liveness_voice',
    ],
    
    // Shadow mode (collect data without enforcement)
    'shadow_mode' => env('CONTINUOUS_AUTH_SHADOW_MODE', false),
];
```

---

## 6. API Endpoints

**File:** `routes/api/continuous-auth.php`

```php
Route::middleware(['auth:sanctum'])->prefix('v1/continuous-auth')->group(function () {
    // Get current session risk score
    Route::get('/session/risk', [ContinuousAuthController::class, 'getSessionRisk']);
    
    // Get behavioral data points (for debugging)
    Route::get('/session/behavioral-data', [ContinuousAuthController::class, 'getBehavioralData']);
    
    // Trigger manual challenge (for testing)
    Route::post('/session/challenge', [ContinuousAuthController::class, 'triggerChallenge']);
    
    // Submit challenge response
    Route::post('/session/challenge/verify', [ContinuousAuthController::class, 'verifyChallenge']);
    
    // Get trust score
    Route::get('/session/trust', [ContinuousAuthController::class, 'getTrustScore']);
});
```

---

## 7. Testing

### 7.1 Unit Tests

**File:** `tests/Unit/Services/Security/ContinuousAuthServiceTest.php`

```php
class ContinuousAuthServiceTest extends TestCase
{
    public function test_start_monitoring_creates_session_record()
    {
        // Test that monitoring creates session risk score record
    }

    public function test_collect_behavioral_data_stores_data_point()
    {
        // Test behavioral data collection
    }

    public function test_score_session_calculates_correct_risk()
    {
        // Test risk scoring logic
    }

    public function test_requires_challenge_returns_true_for_high_risk()
    {
        // Test challenge triggering
    }

    public function test_terminate_session_marks_session_terminated()
    {
        // Test session termination
    }
}
```

### 7.2 Feature Tests

**File:** `tests/Feature/ContinuousAuthenticationTest.php`

```php
class ContinuousAuthenticationTest extends TestCase
{
    public function test_middleware_collects_behavioral_data()
    {
        // Test middleware data collection
    }

    public function test_high_risk_triggers_step_up_challenge()
    {
        // Test step-up challenge flow
    }

    public function test_failed_challenge_terminates_session()
    {
        // Test session termination on failed challenge
    }

    public function test_successful_challenge_continues_session()
    {
        // Test successful challenge flow
    }

    public function test_trust_decay_requires_re_auth()
    {
        // Test trust decay and re-auth
    }
}
```

### 7.3 Chaos Tests

**File:** `tests/Chaos/ContinuousAuthChaosTest.php`

```php
class ContinuousAuthChaosTest extends TestCase
{
    public function test_behavioral_data_collection_with_redis_failure()
    {
        // Test graceful degradation when Redis is down
    }

    public function test_risk_scoring_with_ml_model_failure()
    {
        // Test fallback when ML model is unavailable
    }

    public function test_challenge_with_passkey_service_failure()
    {
        // Test fallback when Passkey service is down
    }
}
```

---

## 8. Monitoring & Observability

### 8.1 Prometheus Metrics

```php
// Metrics to export
- continuous_auth_sessions_active
- continuous_auth_risk_score_distribution
- continuous_auth_challenges_triggered_total
- continuous_auth_challenges_passed_total
- continuous_auth_challenges_failed_total
- continuous_auth_sessions_terminated_total
- continuous_auth_scoring_duration_seconds
- continuous_auth_behavioral_data_points_collected_total
```

### 8.2 Grafana Dashboard

Create dashboard with panels:
- Active sessions by risk level
- Challenge success rate
- Session termination rate
- Risk score distribution over time
- Behavioral anomaly detection rate
- Trust decay distribution

### 8.3 Alerts

- Alert if challenge failure rate > 5%
- Alert if session termination rate > 1%
- Alert if risk scoring job fails
- Alert if behavioral data collection stops

---

## 9. Security Considerations

### 9.1 Fraud Control

- All operations must pass through FraudControlService
- Rate limiting on challenge attempts
- IP-based challenge throttling

### 9.2 Audit Logging

- All challenge events logged to ClickHouse
- Session termination events logged
- Risk score changes logged
- Correlation ID for all operations

### 9.3 PII Compliance

- Behavioral data anonymized before storage
- IP addresses truncated for privacy
- Data retention policy enforced
- Right-to-be-forgotten support

### 9.4 Performance

- Behavioral data collection < 10ms overhead
- Risk scoring < 50ms per session
- Redis caching for risk scores
- Background job for scoring to avoid blocking

---

## 10. Rollout Plan

### Phase 1: Shadow Mode (Week 1)
- Deploy in shadow mode (no enforcement)
- Collect baseline behavioral data
- Tune ML models
- Monitor false positive rate

### Phase 2: Gradual Rollout (Week 2)
- Enable for 10% of users
- Monitor challenge rate
- Adjust thresholds
- Collect user feedback

### Phase 3: Full Rollout (Week 3-4)
- Enable for 100% of users
- Continuous monitoring
- Performance optimization
- Documentation and training

---

## 11. Success Metrics

- False positive rate < 1%
- Detection rate > 95%
- Challenge rate < 5% of sessions
- Challenge success rate > 90%
- Performance overhead < 50ms
- User satisfaction > 4.5/5

---

## 12. Dependencies

**Existing Services:**
- BehavioralBiometricsService
- VoiceBiometricsService
- DeepfakeDetectionService
- WebAuthnAuthenticationService
- FraudControlService
- AuditService

**Infrastructure:**
- Redis (session risk scores)
- ClickHouse (audit logs)
- PostGIS (geographic data)
- ML model hosting (behavioral anomaly detection)

**External APIs:**
- None (self-contained)

---

## 13. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| High false positive rate | Medium | High | Shadow mode tuning, gradual rollout |
| Performance degradation | Low | High | Background scoring, Redis caching |
| User friction | Medium | Medium | Graceful degradation, clear UX |
| ML model drift | Medium | Medium | Regular retraining, monitoring |
| Redis failure | Low | High | Fallback to database, graceful degradation |

---

## 14. Open Questions

1. **ML Model Training:** Do we have enough historical behavioral data for training?
2. **Device Fingerprinting:** Should we use a commercial service or build in-house?
3. **Geographic Data:** Should we use IP geolocation or require user permission?
4. **Challenge Frequency:** What is the optimal scoring interval (5 vs 10 minutes)?
5. **Shadow Mode Duration:** How long should we run in shadow mode before enforcement?

---

## 15. Appendix: Risk Calculation Algorithm

```php
/**
 * Composite Risk Score Calculation
 * 
 * Formula: Risk = Σ(FactorScore × Weight)
 * 
 * Factor Scores (0-100):
 * - Behavioral Anomaly: 0 (normal) → 100 (extreme anomaly)
 * - Geographic Anomaly: 0 (same location) → 100 (different continent)
 * - Device Anomaly: 0 (same device) → 100 (completely different)
 * - Temporal Anomaly: 0 (normal hours) → 100 (unusual time)
 * - Velocity Anomaly: 0 (normal speed) → 100 (impossibly fast)
 * - Trust Decay: 0 (fresh session) → 100 (old session)
 * 
 * Example:
 * Behavioral: 40 × 0.40 = 16
 * Geographic: 20 × 0.20 = 4
 * Device: 10 × 0.15 = 1.5
 * Temporal: 0 × 0.10 = 0
 * Velocity: 0 × 0.10 = 0
 * Trust Decay: 5 × 0.05 = 0.25
 * 
 * Total Risk: 21.75 (Low)
 */
```

---

**Document Status:** Draft
**Next Review:** April 26, 2026
**Approved By:** [Pending]
