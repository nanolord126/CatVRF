# Technical Specification: Continuous Authentication Implementation
## Gap #2 - CRITICAL for Behavioral Biometrics

**Document Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** P0 (CRITICAL)  
**Estimated Effort:** 4-5 weeks  
**Complexity:** HIGH

---

## 1. Executive Summary

This specification details the implementation of BehavioralBiometricsService, which is currently missing but called by ContinuousAuthenticationMiddleware. This service enables continuous authentication by analyzing user behavioral patterns (typing, mouse, touch, session) to detect anomalies and trigger step-up authentication.

**Critical Issue:** ContinuousAuthenticationMiddleware will FAIL at runtime - BehavioralBiometricsService is not found.

---

## 2. Current State Analysis

### Existing Components
- ✅ `ContinuousAuthenticationMiddleware.php` - Comprehensive middleware logic
- ✅ Middleware calls BehavioralBiometricsService (line 41)
- ✅ Middleware has anomaly detection, step-up triggers, session risk scoring
- ✅ Middleware is registered in HTTP kernel

### Missing Components (To Be Implemented)
- ❌ `BehavioralBiometricsService` - Core behavioral analysis service
- ❌ `TypingPatternService` - Typing dynamics analysis
- ❌ `MouseDynamicsService` - Mouse movement analysis
- ❌ `TouchGestureService` - Touch gesture analysis (mobile)
- ❌ `PassiveLivenessService` - Continuous face verification
- ❌ `VoiceBiometricsService` - Voice authentication
- ❌ `MultiModalFusionService` - Combine multiple biometric signals

---

## 3. Service Specifications

### 3.1 BehavioralBiometricsService (Core)

**Purpose:** Orchestrate behavioral biometrics analysis and anomaly detection.

**Methods:**

```php
final readonly class BehavioralBiometricsService
{
    public function __construct(
        private readonly TypingPatternService $typing,
        private readonly MouseDynamicsService $mouse,
        private readonly TouchGestureService $touch,
        private readonly PassiveLivenessService $liveness,
        private readonly VoiceBiometricsService $voice,
        private readonly MultiModalFusionService $fusion,
    ) {}
    
    /**
     * Analyze behavioral signals from request
     * 
     * @param User $user User to analyze
     * @param array $signals Behavioral signals (typing, mouse, touch, session)
     * @param string $sessionId Session ID
     * @return array Analysis result with anomaly detection
     */
    public function analyzeSignals(
        User $user,
        array $signals,
        string $sessionId
    ): array;
    
    /**
     * Build baseline for user (enrollment)
     * 
     * @param User $user User to build baseline for
     * @param array $samples Behavioral samples for enrollment
     * @return bool Success
     */
    public function buildBaseline(User $user, array $samples): bool;
    
    /**
     * Get baseline for user
     * 
     * @param User $user User to get baseline for
     * @return array Baseline data
     */
    public function getBaseline(User $user): array;
    
    /**
     * Update baseline with new samples
     * 
     * @param User $user User to update baseline for
     * @param array $newSamples New behavioral samples
     * @return bool Success
     */
    public function updateBaseline(User $user, array $newSamples): bool;
}
```

**Data Structure:**

```php
[
    'overall_score' => 0.85, // 0.0 to 1.0 (similarity to baseline)
    'is_anomalous' => false,
    'anomaly_severity' => 'none', // none, low, medium, high, critical
    'requires_step_up' => false,
    'correlation_id' => 'uuid',
    'component_scores' => [
        'typing' => [
            'score' => 0.90,
            'confidence' => 0.95,
            'baseline_available' => true,
        ],
        'mouse' => [
            'score' => 0.80,
            'confidence' => 0.85,
            'baseline_available' => true,
        ],
        'touch' => [
            'score' => null, // Not available on desktop
            'confidence' => null,
            'baseline_available' => false,
        ],
        'session' => [
            'score' => 0.85,
            'confidence' => 0.90,
            'baseline_available' => true,
        ],
    ],
    'analysis_timestamp' => '2026-04-19T10:00:00Z',
]
```

---

### 3.2 TypingPatternService

**Purpose:** Analyze typing dynamics (keystroke timing, pressure, rhythm).

**Methods:**

```php
final readonly class TypingPatternService
{
    /**
     * Analyze typing pattern
     * 
     * @param User $user User to analyze
     * @param array $typingData Typing data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $typingData): array;
    
    /**
     * Extract features from typing data
     * 
     * @param array $typingData Raw typing data
     * @return array Extracted features
     */
    public function extractFeatures(array $typingData): array;
    
    /**
     * Compare typing pattern to baseline
     * 
     * @param array $features Current features
     * @param array $baseline Baseline features
     * @return float Similarity score
     */
    public function compare(array $features, array $baseline): float;
}
```

**Frontend Data Collection (JavaScript):**

```javascript
// Collect typing data
const typingData = {
    events: [
        {
            key: 'a',
            timestamp: 1713512000000,
            keyDown: true,
            keyUp: false,
        },
        {
            key: 'a',
            timestamp: 1713512000050,
            keyDown: false,
            keyUp: true,
        },
        // ... more events
    ],
    metadata: {
        deviceType: 'desktop',
        browser: 'Chrome',
        os: 'Windows',
    },
};

// Send to backend
fetch('/api/behavioral/typing', {
    method: 'POST',
    body: JSON.stringify(typingData),
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    },
});
```

**Features Extracted:**

```php
[
    'avg_key_hold_time' => 85.5, // milliseconds
    'avg_flight_time' => 150.2, // milliseconds between keys
    'typing_speed' => 45.2, // words per minute
    'error_rate' => 0.02, // 2% backspace/correction
    'rhythm_variance' => 0.15, // variability in timing
    'digraph_patterns' => [
        'th' => 120.5, // avg time for 'th' digraph
        'he' => 110.2,
        // ... more digraphs
    ],
    'pressure_variance' => 0.08, // if device supports pressure
]
```

---

### 3.3 MouseDynamicsService

**Purpose:** Analyze mouse movement patterns (velocity, acceleration, curvature).

**Methods:**

```php
final readonly class MouseDynamicsService
{
    /**
     * Analyze mouse dynamics
     * 
     * @param User $user User to analyze
     * @param array $mouseData Mouse data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $mouseData): array;
    
    /**
     * Extract features from mouse data
     * 
     * @param array $mouseData Raw mouse data
     * @return array Extracted features
     */
    public function extractFeatures(array $mouseData): array;
    
    /**
     * Compare mouse pattern to baseline
     * 
     * @param array $features Current features
     * @param array $baseline Baseline features
     * @return float Similarity score
     */
    public function compare(array $features, array $baseline): float;
}
```

**Frontend Data Collection (JavaScript):**

```javascript
// Collect mouse data
const mouseData = {
    events: [
        {
            x: 100,
            y: 200,
            timestamp: 1713512000000,
            button: 'left',
            action: 'move',
        },
        {
            x: 105,
            y: 205,
            timestamp: 1713512000016,
            button: 'left',
            action: 'move',
        },
        // ... more events
    ],
    clicks: [
        {
            x: 300,
            y: 400,
            timestamp: 1713512005000,
            button: 'left',
            doubleClick: false,
        },
    ],
    scrolls: [
        {
            deltaX: 0,
            deltaY: 100,
            timestamp: 1713512010000,
        },
    ],
};
```

**Features Extracted:**

```php
[
    'avg_velocity' => 450.5, // pixels per second
    'avg_acceleration' => 120.2, // pixels per second squared
    'movement_smoothness' => 0.85, // 0.0 to 1.0
    'curvature' => 0.12, // path curvature
    'click_frequency' => 2.5, // clicks per second
    'scroll_frequency' => 1.2, // scrolls per second
    'pause_ratio' => 0.35, // time spent not moving
    'direction_changes' => 15, // number of direction changes
    'path_efficiency' => 0.78, // actual path / straight line distance
]
```

---

### 3.4 TouchGestureService

**Purpose:** Analyze touch gestures on mobile devices (swipe, pinch, tap patterns).

**Methods:**

```php
final readonly class TouchGestureService
{
    /**
     * Analyze touch gestures
     * 
     * @param User $user User to analyze
     * @param array $touchData Touch data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $touchData): array;
    
    /**
     * Extract features from touch data
     * 
     * @param array $touchData Raw touch data
     * @return array Extracted features
     */
    public function extractFeatures(array $touchData): array;
    
    /**
     * Compare touch pattern to baseline
     * 
     * @param array $features Current features
     * @param array $baseline Baseline features
     * @return float Similarity score
     */
    public function compare(array $features, array $baseline): float;
}
```

**Frontend Data Collection (JavaScript):**

```javascript
// Collect touch data
const touchData = {
    events: [
        {
            x: 100,
            y: 200,
            timestamp: 1713512000000,
            identifier: 0,
            action: 'touchstart',
        },
        {
            x: 105,
            y: 205,
            timestamp: 1713512000016,
            identifier: 0,
            action: 'touchmove',
        },
        // ... more events
    ],
    gestures: [
        {
            type: 'swipe',
            direction: 'left',
            velocity: 2.5,
            distance: 300,
        },
        {
            type: 'pinch',
            scale: 0.5,
            center: { x: 200, y: 300 },
        },
    ],
};
```

---

### 3.5 PassiveLivenessService

**Purpose:** Continuous face verification during session (optional, for high-security operations).

**Methods:**

```php
final readonly class PassiveLivenessService
{
    /**
     * Verify face passively
     * 
     * @param User $user User to verify
     * @param string $imageBase64 Face image from webcam
     * @return array Verification result
     */
    public function verify(User $user, string $imageBase64): array;
    
    /**
     * Check for liveness (anti-spoofing)
     * 
     * @param string $imageBase64 Face image
     * @return array Liveness check result
     */
    public function checkLiveness(string $imageBase64): array;
    
    /**
     * Compare face to reference
     * 
     * @param string $imageBase64 Current face image
     * @param string $referenceImageBase64 Reference face image
     * @return float Similarity score
     */
    public function compareFaces(string $imageBase64, string $referenceImageBase64): float;
}
```

---

### 3.6 VoiceBiometricsService

**Purpose:** Voice authentication for call center and support verification.

**Methods:**

```php
final readonly class VoiceBiometricsService
{
    /**
     * Enroll user voiceprint
     * 
     * @param User $user User to enroll
     * @param string $audioBase64 Audio sample
     * @return array Enrollment result
     */
    public function enroll(User $user, string $audioBase64): array;
    
    /**
     * Verify user voice
     * 
     * @param User $user User to verify
     * @param string $audioBase64 Audio sample
     * @return array Verification result
     */
    public function verify(User $user, string $audioBase64): array;
    
    /**
     * Check for replay attack
     * 
     * @param string $audioBase64 Audio sample
     * @return array Replay attack detection result
     */
    public function detectReplayAttack(string $audioBase64): array;
}
```

**External API Integration (Azure Voice ID):**

```php
private function verifyWithAzure(User $user, string $audioBase64): array
{
    $response = Http::withToken(config('services.azure_voice.api_key'))
        ->timeout(30)
        ->post(config('services.azure_voice.verify_url'), [
            'profileId' => $user->voice_profile_id,
            'audio' => $audioBase64,
        ]);
    
    if (!$response->successful()) {
        throw new VoiceBiometricsException("Azure Voice API error");
    }
    
    return $response->json();
}
```

---

### 3.7 MultiModalFusionService

**Purpose:** Combine multiple biometric signals for robust authentication.

**Methods:**

```php
final readonly class MultiModalFusionService
{
    /**
     * Fuse multiple biometric scores
     * 
     * @param array $scores Individual component scores
     * @param array $weights Weights for each component
     * @return array Fused result
     */
    public function fuse(array $scores, array $weights): array;
    
    /**
     * Calculate optimal weights based on availability and confidence
     * 
     * @param array $scores Individual component scores
     * @return array Calculated weights
     */
    public function calculateWeights(array $scores): array;
    
    /**
     * Handle missing modalities (graceful degradation)
     * 
     * @param array $scores Individual component scores
     * @param array $availableModalities Available modalities
     * @return array Adjusted scores
     */
    public function handleMissingModalities(
        array $scores,
        array $availableModalities
    ): array;
}
```

**Fusion Algorithm (Weighted Average):**

```php
public function fuse(array $scores, array $weights): array
{
    $availableScores = array_filter($scores, fn($s) => $s['score'] !== null);
    
    if (empty($availableScores)) {
        return [
            'fused_score' => 0.5, // Neutral if no data
            'confidence' => 0.0,
        ];
    }
    
    $weightedSum = 0.0;
    $weightSum = 0.0;
    $confidenceSum = 0.0;
    
    foreach ($availableScores as $modality => $data) {
        $weight = $weights[$modality] ?? 1.0;
        $weightedSum += $data['score'] * $weight;
        $weightSum += $weight;
        $confidenceSum += $data['confidence'] * $weight;
    }
    
    $fusedScore = $weightSum > 0 ? $weightedSum / $weightSum : 0.5;
    $confidence = $weightSum > 0 ? $confidenceSum / $weightSum : 0.0;
    
    return [
        'fused_score' => $fusedScore,
        'confidence' => $confidence,
    ];
}
```

---

## 4. Database Schema

### New Tables

```sql
-- Behavioral baselines
CREATE TABLE behavioral_baselines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    baseline_type VARCHAR(50) NOT NULL, -- typing, mouse, touch, session
    baseline_data JSON NOT NULL,
    sample_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_baseline (user_id, baseline_type)
);

-- Behavioral samples (for enrollment and analysis)
CREATE TABLE behavioral_samples (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    sample_type VARCHAR(50) NOT NULL, -- typing, mouse, touch, session
    sample_data JSON NOT NULL,
    analyzed_at TIMESTAMP NULL,
    anomaly_score DECIMAL(5,4),
    is_anomalous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_sample_type (sample_type)
);

-- Voice profiles
CREATE TABLE voice_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    profile_id VARCHAR(255) NOT NULL, -- External provider ID (Azure)
    provider VARCHAR(50) NOT NULL, -- azure, nuance, etc.
    enrollment_status VARCHAR(20) NOT NULL, -- pending, enrolled, failed
    enrolled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_provider (user_id, provider)
);

-- Face reference images
CREATE TABLE face_references (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    face_vector JSON, -- Face embedding vector
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 5. Configuration

### Environment Variables

```env
# Azure Voice ID
AZURE_VOICE_API_KEY=your_azure_voice_api_key
AZURE_VOICE_API_URL=https://westus.api.cognitive.microsoft.com/speaker/identification/v2.0
AZURE_VOICE_REGION=westus

# Face Recognition (Azure Face API or similar)
AZURE_FACE_API_KEY=your_azure_face_api_key
AZURE_FACE_API_URL=https://westus.api.cognitive.microsoft.com/face/v1.0

# Behavioral Biometrics Settings
BEHAVIORAL_BASELINE_MIN_SAMPLES=20
BEHAVIORAL_BASELINE_UPDATE_FREQUENCY=daily
BEHAVIORAL_ANOMALY_THRESHOLD=0.3
BEHAVIORAL_STEP_UP_THRESHOLD=0.5
```

### Config File

```php
// config/behavioral.php
return [
    'baseline' => [
        'min_samples' => env('BEHAVIORAL_BASELINE_MIN_SAMPLES', 20),
        'update_frequency' => env('BEHAVIORAL_BASELINE_UPDATE_FREQUENCY', 'daily'),
        'retention_days' => 90,
    ],
    
    'anomaly_detection' => [
        'threshold' => env('BEHAVIORAL_ANOMALY_THRESHOLD', 0.3),
        'step_up_threshold' => env('BEHAVIORAL_STEP_UP_THRESHOLD', 0.5),
        'critical_threshold' => 0.7,
    ],
    
    'fusion' => [
        'weights' => [
            'typing' => 0.3,
            'mouse' => 0.3,
            'touch' => 0.2,
            'session' => 0.2,
        ],
    ],
    
    'azure_voice' => [
        'api_key' => env('AZURE_VOICE_API_KEY'),
        'api_url' => env('AZURE_VOICE_API_URL'),
        'region' => env('AZURE_VOICE_REGION', 'westus'),
        'timeout' => 30,
    ],
    
    'azure_face' => [
        'api_key' => env('AZURE_FACE_API_KEY'),
        'api_url' => env('AZURE_FACE_API_URL'),
        'timeout' => 15,
    ],
    
    'caching' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
];
```

---

## 6. Implementation Plan

### Week 1-2: BehavioralBiometricsService + TypingPatternService
- Implement BehavioralBiometricsService core orchestration
- Implement TypingPatternService with feature extraction
- Implement baseline building and comparison logic
- Create frontend JavaScript SDK for typing data collection
- Write unit tests
- Update ContinuousAuthenticationMiddleware to use new service

### Week 3: MouseDynamicsService + TouchGestureService
- Implement MouseDynamicsService with feature extraction
- Implement TouchGestureService for mobile devices
- Create frontend JavaScript SDK for mouse/touch data collection
- Write unit tests
- Update ContinuousAuthenticationMiddleware

### Week 4: PassiveLivenessService + VoiceBiometricsService
- Implement PassiveLivenessService with Azure Face API
- Implement VoiceBiometricsService with Azure Voice ID
- Create enrollment workflows
- Write unit tests
- Update ContinuousAuthenticationMiddleware

### Week 5: MultiModalFusionService + Testing
- Implement MultiModalFusionService with weighted fusion
- Implement graceful degradation for missing modalities
- Write integration tests
- End-to-end testing with real users
- Performance optimization
- Documentation

---

## 7. Testing Strategy

### Unit Tests
- `BehavioralBiometricsServiceTest` - Test orchestration, baseline management
- `TypingPatternServiceTest` - Test feature extraction, comparison
- `MouseDynamicsServiceTest` - Test feature extraction, comparison
- `TouchGestureServiceTest` - Test feature extraction, comparison
- `PassiveLivenessServiceTest` - Test face verification, liveness check
- `VoiceBiometricsServiceTest` - Test enrollment, verification, replay detection
- `MultiModalFusionServiceTest` - Test fusion, weight calculation

### Integration Tests
- `ContinuousAuthenticationIntegrationTest` - Test full middleware flow
- Test with real frontend data collection
- Test anomaly detection and step-up triggers

### Performance Tests
- Test latency of behavioral analysis (< 100ms)
- Test concurrent analysis (1000 requests/second)

---

## 8. Security Considerations

- **PII Protection:** Anonymize behavioral data before storage (hash user IDs)
- **Encryption:** Encrypt voice samples and face images at rest
- **API Keys:** Store in environment variables
- **Rate Limiting:** Implement rate limiting for analysis requests
- **Audit Logging:** Log all behavioral analysis actions
- **Fraud Control:** Integrate with FraudControlService
- **Privacy:** Obtain explicit consent for behavioral data collection

---

## 9. Performance Considerations

- **Async Processing:** Use queues for heavy operations (voice enrollment, face verification)
- **Caching:** Cache baselines in Redis
- **Sampling:** Sample behavioral data (don't collect every event)
- **Batch Processing:** Process behavioral samples in batches
- **Lazy Loading:** Only analyze when necessary (e.g., every 5 minutes per session)
- **Fallback:** Graceful degradation if service unavailable

---

## 10. Frontend SDK

### JavaScript SDK Structure

```javascript
// resources/js/behavioral-sdk.js
class BehavioralSDK {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || '/api/behavioral';
        this.sessionId = options.sessionId;
        this.typingCollector = new TypingCollector();
        this.mouseCollector = new MouseCollector();
        this.touchCollector = new TouchCollector();
    }
    
    start() {
        this.typingCollector.start();
        this.mouseCollector.start();
        this.touchCollector.start();
    }
    
    stop() {
        this.typingCollector.stop();
        this.mouseCollector.stop();
        this.touchCollector.stop();
    }
    
    async sendSamples() {
        const samples = {
            typing: this.typingCollector.getSamples(),
            mouse: this.mouseCollector.getSamples(),
            touch: this.touchCollector.getSamples(),
            sessionId: this.sessionId,
        };
        
        await fetch(`${this.apiEndpoint}/collect`, {
            method: 'POST',
            body: JSON.stringify(samples),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
            },
        });
    }
}

// Usage
const sdk = new BehavioralSDK({
    sessionId: 'session-123',
    apiEndpoint: '/api/behavioral',
});

sdk.start();

// Send samples every 30 seconds
setInterval(() => sdk.sendSamples(), 30000);
```

---

## 11. Success Criteria

- [ ] BehavioralBiometricsService implemented and tested
- [ ] All sub-services implemented (typing, mouse, touch, liveness, voice)
- [ ] ContinuousAuthenticationMiddleware working without errors
- [ ] Frontend SDK created and integrated
- [ ] Baseline enrollment workflow working
- [ ] Anomaly detection working with appropriate thresholds
- [ ] Step-up authentication triggering correctly
- [ ] Unit test coverage > 80%
- [ ] Integration tests passing
- [ ] Performance: Analysis completes < 100ms
- [ ] No runtime errors in ContinuousAuthenticationMiddleware

---

## 12. Rollback Plan

If critical issues arise:
1. Disable continuous authentication via feature flag
2. Revert to traditional session-based authentication
3. Clear caches
4. Monitor logs
5. Hotfix within 4 hours

---

**Document Status:** Ready for Implementation  
**Next Steps:** Begin Week 1-2 implementation (BehavioralBiometricsService + TypingPatternService)
