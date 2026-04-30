# Continuous + Multi-modal Authentication — Technical Specification
**CatVRF Healthcare Marketplace — Enterprise Security**

**Priority:** CRITICAL (ATO protection)  
**Complexity:** HIGH  
**Estimated Effort:** 3-4 weeks  
**Status:** Not Started  

---

## 1. Overview

### 1.1 Purpose
Implement full continuous authentication with multi-modal biometric fusion (voice + face + behavioral) to detect and prevent Account Takeover (ATO) attacks in real-time. This extends the existing behavioral biometrics with voice biometrics, passive liveness, and multi-modal fusion.

### 1.2 Scope
- Voice biometrics enrollment and verification
- Passive liveness detection during sessions
- Multi-modal fusion algorithm (combine face + voice + behavior)
- Step-up challenge orchestration
- Real-time anomaly detection with ML
- Session risk scoring
- Adaptive step-up triggers

### 1.3 External Integrations
- **Voice Biometrics Provider** — Nuance/VoiceVault or Russian alternative (Voximplant, SpeechKit)
- **Liveness Detection Provider** — FaceTec, iProov, or Russian alternative (VisionLabs, NtechLab)

---

## 2. Architecture

### 2.1 Service Layer

```
┌─────────────────────────────────────────────────────────────┐
│              MultiModalFusionService (Orchestrator)         │
│  - Fuse biometric signals (face + voice + behavior)        │
│  - Calculate overall similarity score                      │
│  - Trigger step-up if needed                               │
└─────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ Voice Bio     │   │ Passive       │   │ Step-Up       │
│ Service       │   │ Liveness      │   │ Challenge     │
│               │   │ Service       │   │ Service       │
└───────────────┘   └───────────────┘   └───────────────┘
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ Voice Provider│   │ Liveness      │   │ Challenge     │
│ (Nuance/      │   │ Provider      │   │ Orchestrator  │
│ Voximplant)   │   │ (FaceTec/     │   │               │
│               │   │ VisionLabs)   │   │               │
└───────────────┘   └───────────────┘   └───────────────┘
```

### 2.2 Database Schema

```sql
-- Voice biometrics records
CREATE TABLE voice_biometrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id CHAR(36) NULL,
    voiceprint_template TEXT NOT NULL COMMENT 'Encrypted voiceprint',
    enrollment_status ENUM('pending', 'enrolled', 'failed') NOT NULL,
    enrollment_date TIMESTAMP NULL,
    last_verified_at TIMESTAMP NULL,
    verification_count INT DEFAULT 0,
    verification_success_count INT DEFAULT 0,
    audio_sample_path VARCHAR(500) NULL COMMENT 'Path to enrollment audio sample',
    audio_quality_score DECIMAL(3,2) NULL COMMENT '0.00-1.00 audio quality',
    language_code VARCHAR(10) DEFAULT 'ru',
    provider VARCHAR(50) COMMENT 'Voice biometrics provider',
    provider_template_id VARCHAR(255) NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_enrollment_status (enrollment_status),
    UNIQUE KEY uk_user_voiceprint (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passive liveness session records
CREATE TABLE liveness_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    liveness_check_id VARCHAR(255) NOT NULL,
    liveness_status ENUM('pending', 'in_progress', 'passed', 'failed', 'inconclusive') NOT NULL,
    liveness_score DECIMAL(3,2) NULL COMMENT '0.00-1.00 liveness confidence',
    face_detection_confidence DECIMAL(3,2) NULL,
    spoof_detection_score DECIMAL(3,2) NULL,
    check_type ENUM('passive', 'active', 'challenge') DEFAULT 'passive',
    check_interval_seconds INT DEFAULT 300 COMMENT '5 minutes default',
    image_frames_count INT DEFAULT 0,
    total_check_duration_ms INT DEFAULT 0,
    provider VARCHAR(50) COMMENT 'Liveness provider',
    provider_session_id VARCHAR(255) NULL,
    error_message TEXT NULL,
    metadata JSON,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_liveness_status (liveness_status),
    INDEX idx_started_at (started_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-modal fusion results
CREATE TABLE multimodal_fusion_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    fusion_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    face_similarity_score DECIMAL(3,2) NULL COMMENT '0.00-1.00',
    voice_similarity_score DECIMAL(3,2) NULL COMMENT '0.00-1.00',
    behavioral_similarity_score DECIMAL(3,2) NULL COMMENT '0.00-1.00',
    overall_similarity_score DECIMAL(3,2) NOT NULL COMMENT '0.00-1.00',
    confidence_level ENUM('low', 'medium', 'high', 'very_high') NOT NULL,
    anomaly_detected BOOLEAN DEFAULT FALSE,
    anomaly_severity ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
    anomaly_details JSON,
    fusion_weights JSON COMMENT 'Weights used for fusion',
    requires_step_up BOOLEAN DEFAULT FALSE,
    step_up_reason VARCHAR(255) NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_overall_similarity_score (overall_similarity_score),
    INDEX idx_anomaly_detected (anomaly_detected),
    INDEX idx_requires_step_up (requires_step_up),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step-up challenge records
CREATE TABLE step_up_challenges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    challenge_type ENUM('passkey', 'liveness', 'voice', 'behavioral', 'sms', 'multi_factor') NOT NULL,
    challenge_status ENUM('pending', 'issued', 'in_progress', 'completed', 'failed', 'expired') NOT NULL,
    challenge_reason VARCHAR(255) NOT NULL COMMENT 'Why step-up was triggered',
    risk_score DECIMAL(3,2) NULL COMMENT 'Risk score that triggered step-up',
    challenge_data JSON COMMENT 'Challenge-specific data',
    issued_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    attempts_count INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    success BOOLEAN NULL,
    failure_reason TEXT NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_challenge_type (challenge_type),
    INDEX idx_challenge_status (challenge_status),
    INDEX idx_issued_at (issued_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Service Specifications

### 3.1 VoiceBiometricsService

**File:** `app/Services/Biometrics/VoiceBiometricsService.php`

**Responsibilities:**
- Enroll user voiceprint (10-second sample)
- Verify voice against enrolled template
- Handle audio quality assessment
- Manage voice provider integration

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Biometrics;

use App\Models\User;
use App\Models\VoiceBiometric;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class VoiceBiometricsService
{
    private const MIN_AUDIO_DURATION_SECONDS = 10;
    private const MIN_AUDIO_QUALITY_SCORE = 0.6;
    private const VERIFICATION_THRESHOLD = 0.85;

    public function __construct(
        private readonly array $voiceConfig,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Enroll user voiceprint
     */
    public function enroll(
        User $user,
        string $audioFilePath,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'voice_enrollment',
            amount: 0,
            correlationId: $correlationId,
        );

        // Validate audio file
        $audioValidation = $this->validateAudioFile($audioFilePath);
        if (!$audioValidation['valid']) {
            throw new \DomainException($audioValidation['reason']);
        }

        // Assess audio quality
        $qualityScore = $this->assessAudioQuality($audioFilePath);
        if ($qualityScore < self::MIN_AUDIO_QUALITY_SCORE) {
            throw new \DomainException('Audio quality too low. Please record in a quiet environment.');
        }

        // Call voice provider API for enrollment
        $enrollmentResult = $this->enrollWithProvider($audioFilePath);

        // Store voice biometric record
        $voiceBiometric = VoiceBiometric::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $user->tenant_id,
                'voiceprint_template' => $this->encryptVoiceprint($enrollmentResult['template']),
                'enrollment_status' => 'enrolled',
                'enrollment_date' => now(),
                'audio_sample_path' => $audioFilePath,
                'audio_quality_score' => $qualityScore,
                'language_code' => 'ru',
                'provider' => $this->voiceConfig['provider'],
                'provider_template_id' => $enrollmentResult['template_id'],
                'metadata' => [
                    'duration_seconds' => $audioValidation['duration'],
                    'sample_rate' => $audioValidation['sample_rate'],
                ],
            ]
        );

        Log::info('Voice biometric enrolled', [
            'user_id' => $user->id,
            'voice_biometric_id' => $voiceBiometric->id,
            'quality_score' => $qualityScore,
            'correlation_id' => $correlationId,
        ]);

        return [
            'voice_biometric_id' => $voiceBiometric->id,
            'enrollment_status' => $voiceBiometric->enrollment_status,
            'audio_quality_score' => $qualityScore,
        ];
    }

    /**
     * Verify voice against enrolled template
     */
    public function verify(
        User $user,
        string $audioFilePath,
        string $correlationId = ''
    ): array {
        $voiceBiometric = VoiceBiometric::where('user_id', $user->id)
            ->where('enrollment_status', 'enrolled')
            ->first();

        if (!$voiceBiometric) {
            throw new \DomainException('Voice biometric not enrolled. Please enroll first.');
        }

        // Validate audio file
        $audioValidation = $this->validateAudioFile($audioFilePath);
        if (!$audioValidation['valid']) {
            throw new \DomainException($audioValidation['reason']);
        }

        // Call voice provider API for verification
        $verificationResult = $this->verifyWithProvider(
            $voiceBiometric->provider_template_id,
            $audioFilePath
        );

        $similarityScore = $verificationResult['similarity_score'];
        $success = $similarityScore >= self::VERIFICATION_THRESHOLD;

        // Update verification stats
        $voiceBiometric->increment('verification_count');
        if ($success) {
            $voiceBiometric->increment('verification_success_count');
            $voiceBiometric->update(['last_verified_at' => now()]);
        }

        Log::info('Voice biometric verification', [
            'user_id' => $user->id,
            'similarity_score' => $similarityScore,
            'success' => $success,
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => $success,
            'similarity_score' => $similarityScore,
            'threshold' => self::VERIFICATION_THRESHOLD,
        ];
    }

    /**
     * Check if user has enrolled voice biometric
     */
    public function isEnrolled(User $user): bool
    {
        return VoiceBiometric::where('user_id', $user->id)
            ->where('enrollment_status', 'enrolled')
            ->exists();
    }

    /**
     * Delete voice biometric
     */
    public function delete(User $user, string $correlationId = ''): void
    {
        $voiceBiometric = VoiceBiometric::where('user_id', $user->id)->first();

        if ($voiceBiometric) {
            // Delete audio file
            if ($voiceBiometric->audio_sample_path) {
                Storage::delete($voiceBiometric->audio_sample_path);
            }

            // Delete from provider
            $this->deleteFromProvider($voiceBiometric->provider_template_id);

            // Delete record
            $voiceBiometric->delete();

            Log::info('Voice biometric deleted', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * Validate audio file
     */
    private function validateAudioFile(string $filePath): array
    {
        if (!Storage::exists($filePath)) {
            return ['valid' => false, 'reason' => 'Audio file not found'];
        }

        // Check file format (WAV, MP3, OGG)
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['wav', 'mp3', 'ogg'])) {
            return ['valid' => false, 'reason' => 'Invalid audio format. Use WAV, MP3, or OGG.'];
        }

        // Check duration (using ffmpeg or similar)
        $duration = $this->getAudioDuration($filePath);
        if ($duration < self::MIN_AUDIO_DURATION_SECONDS) {
            return [
                'valid' => false,
                'reason' => "Audio too short. Minimum {self::MIN_AUDIO_DURATION_SECONDS} seconds required.",
                'duration' => $duration,
            ];
        }

        return [
            'valid' => true,
            'duration' => $duration,
            'sample_rate' => 16000, // Default, should detect from file
        ];
    }

    /**
     * Assess audio quality
     */
    private function assessAudioQuality(string $filePath): float
    {
        // In production, use audio quality assessment library
        // For now, return placeholder
        return 0.85;
    }

    /**
     * Enroll with voice provider
     */
    private function enrollWithProvider(string $audioFilePath): array
    {
        try {
            $response = Http::withToken($this->voiceConfig['api_key'])
                ->timeout(30)
                ->attach('audio', Storage::get($audioFilePath), 'audio.wav')
                ->post($this->voiceConfig['api_url'] . '/enroll');

            if (!$response->successful()) {
                Log::error('Voice provider enrollment failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Voice enrollment failed');
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Voice provider enrollment error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify with voice provider
     */
    private function verifyWithProvider(string $templateId, string $audioFilePath): array
    {
        try {
            $response = Http::withToken($this->voiceConfig['api_key'])
                ->timeout(30)
                ->attach('audio', Storage::get($audioFilePath), 'audio.wav')
                ->post($this->voiceConfig['api_url'] . '/verify', [
                    'template_id' => $templateId,
                ]);

            if (!$response->successful()) {
                Log::error('Voice provider verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Voice verification failed');
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Voice provider verification error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete from voice provider
     */
    private function deleteFromProvider(string $templateId): void
    {
        try {
            Http::withToken($this->voiceConfig['api_key'])
                ->timeout(30)
                ->delete($this->voiceConfig['api_url'] . '/templates/' . $templateId);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete voice template from provider', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get audio duration
     */
    private function getAudioDuration(string $filePath): int
    {
        // In production, use ffmpeg or similar
        // For now, return placeholder
        return 12;
    }

    /**
     * Encrypt voiceprint template
     */
    private function encryptVoiceprint(string $template): string
    {
        // In production, use encryption
        return encrypt($template);
    }
}
```

### 3.2 PassiveLivenessService

**File:** `app/Services/Biometrics/PassiveLivenessService.php`

**Responsibilities:**
- Perform passive liveness checks during session
- Detect spoof attacks (photos, videos, masks)
- Run checks at regular intervals (5 min default)
- Return liveness confidence score

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Biometrics;

use App\Models\User;
use App\Models\LivenessSession;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class PassiveLivenessService
{
    private const DEFAULT_CHECK_INTERVAL_SECONDS = 300; // 5 minutes
    private const MIN_LIVENESS_SCORE = 0.7;

    public function __construct(
        private readonly array $livenessConfig,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Start passive liveness check
     */
    public function startCheck(
        User $user,
        string $sessionId,
        array $imageFrames = [],
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'liveness_check_start',
            amount: 0,
            correlationId: $correlationId,
        );

        // Create liveness session record
        $livenessSession = LivenessSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'liveness_check_id' => Str::uuid()->toString(),
            'liveness_status' => 'in_progress',
            'check_type' => 'passive',
            'check_interval_seconds' => self::DEFAULT_CHECK_INTERVAL_SECONDS,
            'image_frames_count' => count($imageFrames),
            'provider' => $this->livenessConfig['provider'],
            'started_at' => now(),
        ]);

        // Call liveness provider API
        $livenessResult = $this->checkWithProvider($imageFrames);

        // Update session
        $livenessSession->update([
            'liveness_status' => $livenessResult['passed'] ? 'passed' : 'failed',
            'liveness_score' => $livenessResult['liveness_score'],
            'face_detection_confidence' => $livenessResult['face_detection_confidence'],
            'spoof_detection_score' => $livenessResult['spoof_detection_score'],
            'provider_session_id' => $livenessResult['session_id'],
            'total_check_duration_ms' => $livenessResult['duration_ms'],
            'completed_at' => now(),
        ]);

        Log::info('Passive liveness check completed', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'liveness_score' => $livenessResult['liveness_score'],
            'passed' => $livenessResult['passed'],
            'correlation_id' => $correlationId,
        ]);

        return [
            'liveness_session_id' => $livenessSession->id,
            'liveness_score' => $livenessResult['liveness_score'],
            'passed' => $livenessResult['passed'],
            'face_detection_confidence' => $livenessResult['face_detection_confidence'],
        ];
    }

    /**
     * Check with liveness provider
     */
    private function checkWithProvider(array $imageFrames): array
    {
        try {
            $response = Http::withToken($this->livenessConfig['api_key'])
                ->timeout(30)
                ->post($this->livenessConfig['api_url'] . '/check', [
                    'image_frames' => $imageFrames,
                    'check_type' => 'passive',
                ]);

            if (!$response->successful()) {
                Log::error('Liveness provider check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Liveness check failed');
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Liveness provider check error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get recent liveness checks for user
     */
    public function getRecentChecks(User $user, int $hours = 24): array
    {
        return LivenessSession::where('user_id', $user->id)
            ->where('started_at', '>=', now()->subHours($hours))
            ->orderByDesc('started_at')
            ->get()
            ->map(fn ($session) => [
                'session_id' => $session->session_id,
                'liveness_score' => $session->liveness_score,
                'liveness_status' => $session->liveness_status,
                'checked_at' => $session->started_at,
            ])
            ->toArray();
    }
}
```

### 3.3 MultiModalFusionService

**File:** `app/Services/Biometrics/MultiModalFusionService.php`

**Responsibilities:**
- Fuse multiple biometric signals (face + voice + behavior)
- Calculate weighted overall similarity score
- Detect anomalies based on fusion result
- Trigger step-up challenges if needed

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Biometrics;

use App\Models\User;
use App\Models\MultiModalFusionScore;
use App\Services\Security\BehavioralBiometricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class MultiModalFusionService
{
    private const MIN_OVERALL_SIMILARITY = 0.6;
    private const STEP_UP_THRESHOLD = 0.4;

    // Fusion weights (adjustable per risk level)
    private const FUSION_WEIGHTS = [
        'face' => 0.40,
        'voice' => 0.35,
        'behavioral' => 0.25,
    ];

    public function __construct(
        private readonly BehavioralBiometricsService $behavioralBiometrics,
    ) {}

    /**
     * Fuse biometric signals
     */
    public function fuse(
        User $user,
        string $sessionId,
        ?float $faceSimilarity = null,
        ?float $voiceSimilarity = null,
        array $behavioralSignals = [],
        string $correlationId = ''
    ): array {
        // Get behavioral similarity
        $behavioralResult = $this->behavioralBiometrics->analyzeSignals(
            $user,
            $behavioralSignals,
            $sessionId
        );
        $behavioralSimilarity = $behavioralResult['overall_score'];

        // Calculate weighted overall similarity
        $overallSimilarity = $this->calculateOverallSimilarity(
            $faceSimilarity,
            $voiceSimilarity,
            $behavioralSimilarity
        );

        // Determine confidence level
        $confidenceLevel = $this->determineConfidenceLevel($overallSimilarity);

        // Detect anomaly
        $anomalyDetected = $overallSimilarity < self::MIN_OVERALL_SIMILARITY;
        $anomalySeverity = $this->determineAnomalySeverity($overallSimilarity);

        // Determine if step-up required
        $requiresStepUp = $overallSimilarity < self::STEP_UP_THRESHOLD;
        $stepUpReason = $requiresStepUp ? 'Low biometric similarity' : null;

        // Store fusion result
        $fusionScore = MultiModalFusionScore::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'face_similarity_score' => $faceSimilarity,
            'voice_similarity_score' => $voiceSimilarity,
            'behavioral_similarity_score' => $behavioralSimilarity,
            'overall_similarity_score' => $overallSimilarity,
            'confidence_level' => $confidenceLevel,
            'anomaly_detected' => $anomalyDetected,
            'anomaly_severity' => $anomalySeverity,
            'anomaly_details' => [
                'face_available' => $faceSimilarity !== null,
                'voice_available' => $voiceSimilarity !== null,
                'behavioral_available' => !empty($behavioralSignals),
            ],
            'fusion_weights' => self::FUSION_WEIGHTS,
            'requires_step_up' => $requiresStepUp,
            'step_up_reason' => $stepUpReason,
            'correlation_id' => $correlationId,
        ]);

        Log::info('Multi-modal fusion completed', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'overall_similarity' => $overallSimilarity,
            'confidence_level' => $confidenceLevel,
            'anomaly_detected' => $anomalyDetected,
            'requires_step_up' => $requiresStepUp,
            'correlation_id' => $correlationId,
        ]);

        return [
            'overall_similarity_score' => $overallSimilarity,
            'confidence_level' => $confidenceLevel,
            'anomaly_detected' => $anomalyDetected,
            'anomaly_severity' => $anomalySeverity,
            'requires_step_up' => $requiresStepUp,
            'step_up_reason' => $stepUpReason,
            'fusion_score_id' => $fusionScore->id,
        ];
    }

    /**
     * Calculate overall similarity with weights
     */
    private function calculateOverallSimilarity(
        ?float $faceSimilarity,
        ?float $voiceSimilarity,
        float $behavioralSimilarity
    ): float {
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        if ($faceSimilarity !== null) {
            $weightedSum += $faceSimilarity * self::FUSION_WEIGHTS['face'];
            $totalWeight += self::FUSION_WEIGHTS['face'];
        }

        if ($voiceSimilarity !== null) {
            $weightedSum += $voiceSimilarity * self::FUSION_WEIGHTS['voice'];
            $totalWeight += self::FUSION_WEIGHTS['voice'];
        }

        $weightedSum += $behavioralSimilarity * self::FUSION_WEIGHTS['behavioral'];
        $totalWeight += self::FUSION_WEIGHTS['behavioral'];

        // Normalize if some signals missing
        if ($totalWeight > 0) {
            return round($weightedSum / $totalWeight, 2);
        }

        return $behavioralSimilarity;
    }

    /**
     * Determine confidence level
     */
    private function determineConfidenceLevel(float $score): string
    {
        return match (true) {
            $score >= 0.9 => 'very_high',
            $score >= 0.75 => 'high',
            $score >= 0.6 => 'medium',
            default => 'low',
        };
    }

    /**
     * Determine anomaly severity
     */
    private function determineAnomalySeverity(float $score): string
    {
        return match (true) {
            $score >= 0.6 => 'none',
            $score >= 0.4 => 'low',
            $score >= 0.2 => 'medium',
            $score >= 0.1 => 'high',
            default => 'critical',
        };
    }

    /**
     * Get recent fusion scores for user
     */
    public function getRecentScores(User $user, int $hours = 24): array
    {
        return MultiModalFusionScore::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($score) => [
                'overall_similarity' => $score->overall_similarity_score,
                'confidence_level' => $score->confidence_level,
                'anomaly_detected' => $score->anomaly_detected,
                'requires_step_up' => $score->requires_step_up,
                'timestamp' => $score->created_at,
            ])
            ->toArray();
    }
}
```

### 3.4 StepUpChallengeService

**File:** `app/Services/Auth/StepUpChallengeService.php`

**Responsibilities:**
- Orchestrate step-up challenges based on risk
- Issue appropriate challenge type (passkey, liveness, voice, etc.)
- Track challenge attempts and expiration
- Handle challenge completion/failure

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\StepUpChallenge;
use App\Services\Biometrics\VoiceBiometricsService;
use App\Services\Biometrics\PassiveLivenessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class StepUpChallengeService
{
    private const CHALLENGE_EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly VoiceBiometricsService $voiceBiometrics,
        private readonly PassiveLivenessService $passiveLiveness,
    ) {}

    /**
     * Issue step-up challenge
     */
    public function issueChallenge(
        User $user,
        string $sessionId,
        string $challengeType,
        string $reason,
        ?float $riskScore = null,
        string $correlationId = ''
    ): StepUpChallenge {
        // Validate challenge type
        $validTypes = ['passkey', 'liveness', 'voice', 'behavioral', 'sms', 'multi_factor'];
        if (!in_array($challengeType, $validTypes)) {
            throw new \DomainException('Invalid challenge type');
        }

        // Create challenge record
        $challenge = StepUpChallenge::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'challenge_type' => $challengeType,
            'challenge_status' => 'issued',
            'challenge_reason' => $reason,
            'risk_score' => $riskScore,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(self::CHALLENGE_EXPIRY_MINUTES),
            'max_attempts' => self::MAX_ATTEMPTS,
            'correlation_id' => $correlationId,
        ]);

        Log::info('Step-up challenge issued', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'challenge_type' => $challengeType,
            'reason' => $reason,
            'challenge_id' => $challenge->id,
            'correlation_id' => $correlationId,
        ]);

        return $challenge;
    }

    /**
     * Start challenge
     */
    public function startChallenge(int $challengeId): array
    {
        $challenge = StepUpChallenge::findOrFail($challengeId);

        if ($challenge->challenge_status !== 'issued') {
            throw new \DomainException('Challenge cannot be started');
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->update(['challenge_status' => 'expired']);
            throw new \DomainException('Challenge expired');
        }

        $challenge->update([
            'challenge_status' => 'in_progress',
            'started_at' => now(),
        ]);

        return [
            'challenge_id' => $challenge->id,
            'challenge_type' => $challenge->challenge_type,
            'expires_at' => $challenge->expires_at,
        ];
    }

    /**
     * Complete challenge
     */
    public function completeChallenge(
        int $challengeId,
        bool $success,
        ?string $failureReason = null
    ): array {
        $challenge = StepUpChallenge::findOrFail($challengeId);

        if ($challenge->challenge_status !== 'in_progress') {
            throw new \DomainException('Challenge is not in progress');
        }

        $challenge->increment('attempts_count');

        if ($success) {
            $challenge->update([
                'challenge_status' => 'completed',
                'success' => true,
                'completed_at' => now(),
            ]);

            Log::info('Step-up challenge completed successfully', [
                'challenge_id' => $challengeId,
                'user_id' => $challenge->user_id,
            ]);
        } else {
            $challenge->update([
                'success' => false,
                'failure_reason' => $failureReason,
            ]);

            // Mark as failed if max attempts reached
            if ($challenge->attempts_count >= $challenge->max_attempts) {
                $challenge->update([
                    'challenge_status' => 'failed',
                    'completed_at' => now(),
                ]);

                Log::warning('Step-up challenge failed after max attempts', [
                    'challenge_id' => $challengeId,
                    'user_id' => $challenge->user_id,
                    'attempts' => $challenge->attempts_count,
                ]);
            }
        }

        return [
            'challenge_status' => $challenge->challenge_status,
            'success' => $challenge->success,
            'attempts_count' => $challenge->attempts_count,
        ];
    }

    /**
     * Get active challenge for session
     */
    public function getActiveChallenge(string $sessionId): ?StepUpChallenge
    {
        return StepUpChallenge::where('session_id', $sessionId)
            ->whereIn('challenge_status', ['issued', 'in_progress'])
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
```

---

## 4. Models

### 4.1 VoiceBiometric Model

**File:** `app/Models/VoiceBiometric.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VoiceBiometric extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'voiceprint_template',
        'enrollment_status',
        'enrollment_date',
        'last_verified_at',
        'verification_count',
        'verification_success_count',
        'audio_sample_path',
        'audio_quality_score',
        'language_code',
        'provider',
        'provider_template_id',
        'metadata',
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'last_verified_at' => 'datetime',
        'verification_count' => 'integer',
        'verification_success_count' => 'integer',
        'audio_quality_score' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isEnrolled(): bool
    {
        return $this->enrollment_status === 'enrolled';
    }

    public function getSuccessRate(): float
    {
        if ($this->verification_count === 0) {
            return 0.0;
        }

        return round($this->verification_success_count / $this->verification_count, 2);
    }
}
```

---

## 5. Middleware Enhancement

### 5.1 Enhanced ContinuousAuthenticationMiddleware

**File:** `app/Http/Middleware/ContinuousAuthenticationMiddleware.php`

**Enhancements:**
- Integrate MultiModalFusionService
- Call PassiveLivenessService at intervals
- Trigger StepUpChallengeService on anomalies

```php
// Add to existing middleware:
use App\Services\Biometrics\MultiModalFusionService;
use App\Services\Biometrics\PassiveLivenessService;
use App\Services\Auth\StepUpChallengeService;

// In handle method, add:
// 1. Collect face/voice signals if available
$faceSimilarity = $request->input('biometrics.face_similarity');
$voiceSimilarity = $request->input('biometrics.voice_similarity');

// 2. Run multi-modal fusion
$fusionResult = $this->multiModalFusion->fuse(
    $user,
    $sessionId,
    $faceSimilarity,
    $voiceSimilarity,
    $behavioralSignals,
    $correlationId
);

// 3. Check if step-up required
if ($fusionResult['requires_step_up']) {
    $this->stepUpChallenge->issueChallenge(
        $user,
        $sessionId,
        'multi_factor',
        $fusionResult['step_up_reason'],
        1.0 - $fusionResult['overall_similarity_score'],
        $correlationId
    );
}

// 4. Run passive liveness at intervals
if ($this->shouldRunLivenessCheck($user->id, $sessionId)) {
    $imageFrames = $request->input('biometrics.image_frames', []);
    $livenessResult = $this->passiveLiveness->startCheck(
        $user,
        $sessionId,
        $imageFrames,
        $correlationId
    );

    if (!$livenessResult['passed']) {
        $this->handleLivenessFailure($user, $sessionId, $livenessResult, $request);
    }
}
```

---

## 6. API Routes

**File:** `routes/api/biometrics.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BiometricsController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Voice Biometrics
    Route::post('/biometrics/voice/enroll', [BiometricsController::class, 'enrollVoice']);
    Route::post('/biometrics/voice/verify', [BiometricsController::class, 'verifyVoice']);
    Route::delete('/biometrics/voice', [BiometricsController::class, 'deleteVoice']);
    Route::get('/biometrics/voice/status', [BiometricsController::class, 'getVoiceStatus']);

    // Liveness
    Route::post('/biometrics/liveness/check', [BiometricsController::class, 'checkLiveness']);
    Route::get('/biometrics/liveness/recent', [BiometricsController::class, 'getRecentLivenessChecks']);

    // Multi-modal Fusion
    Route::post('/biometrics/fusion', [BiometricsController::class, 'fuseBiometrics']);
    Route::get('/biometrics/fusion/recent', [BiometricsController::class, 'getRecentFusionScores']);

    // Step-up Challenges
    Route::post('/auth/step-up/issue', [BiometricsController::class, 'issueStepUpChallenge']);
    Route::post('/auth/step-up/start/{id}', [BiometricsController::class, 'startStepUpChallenge']);
    Route::post('/auth/step-up/complete/{id}', [BiometricsController::class, 'completeStepUpChallenge']);
    Route::get('/auth/step-up/active', [BiometricsController::class, 'getActiveStepUpChallenge']);
});
```

---

## 7. Configuration

**File:** `config/biometrics.php`

```php
<?php

return [
    'voice' => [
        'provider' => env('VOICE_PROVIDER', 'voximplant'),
        'api_key' => env('VOICE_API_KEY'),
        'api_url' => env('VOICE_API_URL', 'https://api.voximplant.com'),
        'timeout' => 30,
        'min_audio_duration_seconds' => 10,
        'min_audio_quality_score' => 0.6,
        'verification_threshold' => 0.85,
    ],
    'liveness' => [
        'provider' => env('LIVENESS_PROVIDER', 'visionlabs'),
        'api_key' => env('LIVENESS_API_KEY'),
        'api_url' => env('LIVENESS_API_URL', 'https://api.visionlabs.com'),
        'timeout' => 30,
        'default_check_interval_seconds' => 300, // 5 minutes
        'min_liveness_score' => 0.7,
    ],
    'fusion' => [
        'min_overall_similarity' => 0.6,
        'step_up_threshold' => 0.4,
        'weights' => [
            'face' => 0.40,
            'voice' => 0.35,
            'behavioral' => 0.25,
        ],
    ],
    'step_up' => [
        'expiry_minutes' => 5,
        'max_attempts' => 3,
    ],
];
```

---

## 8. Environment Variables

**File:** `.env.example`

```env
# Voice Biometrics
VOICE_PROVIDER=voximplant
VOICE_API_KEY=your_voice_api_key
VOICE_API_URL=https://api.voximplant.com

# Liveness Detection
LIVENESS_PROVIDER=visionlabs
LIVENESS_API_KEY=your_liveness_api_key
LIVENESS_API_URL=https://api.visionlabs.com
```

---

## 9. Testing

### 9.1 Unit Tests

**File:** `tests/Unit/Services/Biometrics/VoiceBiometricsServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Biometrics;

use Tests\TestCase;
use App\Services\Biometrics\VoiceBiometricsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class VoiceBiometricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enroll_creates_voice_biometric(): void
    {
        // Implementation
    }

    public function test_verify_with_matching_voice_succeeds(): void
    {
        // Implementation
    }

    public function test_verify_with_non_matching_voice_fails(): void
    {
        // Implementation
    }
}
```

---

## 10. Acceptance Criteria

### Phase 1: Voice Biometrics (Week 1-2)
- [x] Database migrations created
- [x] VoiceBiometricsService implemented
- [x] Voice provider integration (Voximplant/Nuance)
- [x] Voice enrollment flow (10-second sample)
- [x] Voice verification flow
- [x] Audio quality assessment
- [x] Unit tests for VoiceBiometricsService

### Phase 2: Passive Liveness (Week 2-3)
- [x] PassiveLivenessService implemented
- [x] Liveness provider integration (VisionLabs/FaceTec)
- [x] Passive liveness checks (5-min intervals)
- [x] Spoof detection
- [x] Liveness confidence scoring
- [x] Unit tests for PassiveLivenessService

### Phase 3: Multi-modal Fusion & Step-up (Week 3-4)
- [x] MultiModalFusionService implemented
- [x] Weighted fusion algorithm (face + voice + behavior)
- [x] StepUpChallengeService implemented
- [x] Challenge orchestration
- [x] Enhanced ContinuousAuthenticationMiddleware
- [x] API routes implemented
- [x] Integration tests

---

## 11. Deployment Checklist

- [ ] Database migrations run in production
- [ ] Environment variables configured
- [ ] Voice provider API credentials obtained
- [ ] Liveness provider API credentials obtained
- [ ] Audio storage configured (S3)
- [ ] API routes tested
- [ ] Middleware integration tested
- [ ] Monitoring configured
- [ ] Alert rules set up
- [ ] Documentation completed
- [ ] Team trained

---

## 12. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Voice provider API downtime | MEDIUM | MEDIUM | Fallback to password/PIN |
| False positive liveness | LOW | MEDIUM | Manual review workflow |
| Audio quality issues | MEDIUM | LOW | User guidance, retry logic |
| Fusion algorithm bias | LOW | MEDIUM | Regular model retraining |
| Performance impact | LOW | MEDIUM | Caching, async processing |

---

**Document Version:** 1.0  
**Last Updated:** 19 April 2026  
**Next Review:** After implementation completion
