# Voice Biometrics Technical Specification
## Multi-Modal Authentication Enhancement

**Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** 🔴 CRITICAL (Call center + support security)  
**Estimated Effort:** 4-5 weeks  
**Complexity:** High

---

## 1. Overview

This specification details the implementation of Voice Biometrics for CatVRF to enable multi-modal authentication combining face, voice, and behavioral biometrics. This is critical for call center authentication, support ticket verification, and high-value transaction confirmation.

### Current State
- ✅ BehavioralBiometricsService (typing, mouse, touch, session patterns)
- ✅ Face ID / fingerprint via Passkeys (FIDO2 Level 3)
- ✅ AdaptiveAuthService with step-up challenges
- ❌ No voice biometrics
- ❌ No multi-modal fusion engine

### Target State
- Voiceprint enrollment during registration
- Voice verification for call center authentication
- Anti-spoofing (replay attack detection, synthetic voice detection)
- Multi-modal fusion engine combining all biometric modalities
- Integration with support ticketing system

---

## 2. Requirements

### 2.1. Functional Requirements

**Enrollment:**
1. Voiceprint enrollment during user registration (optional)
2. Minimum 3 voice samples required for enrollment
3. Support for multiple enrollment phrases (randomized)
4. Voice quality validation (SNR, duration, clarity)
5. Re-enrollment support for voiceprint updates

**Verification:**
1. Real-time voice verification (<2s latency)
2. Text-dependent verification (specific phrase)
3. Text-independent verification (free speech)
4. Adaptive threshold based on confidence
5. Fallback to alternative authentication on failure

**Anti-Spoofing:**
1. Replay attack detection (pre-recorded audio)
2. Synthetic voice detection (AI-generated voices)
3. Liveness detection (challenge-response)
4. Voice conversion detection
5. Multiple microphone detection

**Integration:**
1. Call center authentication workflow
2. Support ticket verification
3. High-value transaction confirmation
4. Emergency medical consultation authentication
5. Voice-based account recovery

### 2.2. Non-Functional Requirements

- **Verification Latency:** <2s (P95), <1s (P50)
- **Enrollment Latency:** <5s per sample
- **Accuracy:** FAR (False Acceptance Rate) <0.1%, FRR (False Rejection Rate) <1%
- **API Availability:** 99.9%
- **Voiceprint Storage:** Encrypted at rest
- **Audit Trail:** All verification attempts logged

---

## 3. Technology Stack

### 3.1. Voice Biometrics Providers

**Primary Options:**
1. **Microsoft Azure Voice ID** - Industry leader, good Russian support
   - API: Azure Speech Service / Voice ID
   - Features: Text-dependent + independent, anti-spoofing
   - Cost: ~$1 per 1K verifications
   - Latency: 500-1000ms

2. **Amazon Voice ID** (AWS Connect) - Alternative provider
   - API: Amazon Connect Voice ID
   - Features: Streaming authentication, real-time
   - Cost: ~$0.8 per 1K verifications
   - Latency: 300-800ms

3. **Nuance VocalPassword** - Enterprise-grade
   - API: Nuance Security Suite
   - Features: Advanced anti-spoofing, multi-language
   - Cost: ~$2 per 1K verifications
   - Latency: 400-900ms

4. **Paddle / Pindrop** - Specialized anti-spoofing
   - API: Paddle Voice Authentication
   - Features: Behavioral voice analysis, device fingerprinting
   - Cost: ~$3 per 1K verifications
   - Latency: 600-1200ms

**Recommendation:** Microsoft Azure Voice ID (primary) + Amazon Voice ID (fallback)

### 3.2. Anti-Spoofing Technologies

1. **ASVspoof Challenge Models** - Open-source anti-spoofing
2. **Custom ML Models** - Train on synthetic voice datasets
3. **Liveness Detection** - Challenge-response with random phrases

---

## 4. Service Architecture

### 4.1. Voice Biometrics Service

```php
// app/Services/Biometrics/VoiceBiometricsService.php

final readonly class VoiceBiometricsService
{
    private const MIN_ENROLLMENT_SAMPLES = 3;
    private const VERIFICATION_THRESHOLD = 0.85; // Confidence threshold
    private const ENROLLMENT_PHRASES = [
        'Мой голос — это мой пароль',
        'Я подтверждаю свою личность',
        'Доступ разрешён',
        'Verify my identity',
        'Access granted',
    ];

    public function __construct(
        private readonly AzureVoiceIdClient $azureVoice,
        private readonly AmazonVoiceIdClient $amazonVoice,
        private readonly AntiSpoofingService $antiSpoofing,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Enroll user voiceprint
     */
    public function enrollVoiceprint(
        User $user,
        array $audioSamples, // Array of base64-encoded audio files
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'voice_enrollment',
            amount: 0,
            correlationId: $correlationId,
        );

        if (count($audioSamples) < self::MIN_ENROLLMENT_SAMPLES) {
            throw new \DomainException(
                sprintf('Minimum %d audio samples required for enrollment', self::MIN_ENROLLMENT_SAMPLES)
            );
        }

        // Validate audio quality
        foreach ($audioSamples as $sample) {
            $quality = $this->validateAudioQuality($sample);
            if (!$quality['is_valid']) {
                throw new \DomainException('Audio quality validation failed: ' . $quality['reason']);
            }
        }

        // Anti-spoofing check during enrollment
        foreach ($audioSamples as $sample) {
            $spoofCheck = $this->antiSpoofing->detect($sample);
            if ($spoofCheck['is_spoofed']) {
                $this->audit->logEvent('voice_enrollment_spoof_detected', [
                    'user_id' => $user->id,
                    'reason' => $spoofCheck['reason'],
                ], 'security');
                
                throw new \DomainException('Potential spoofing detected during enrollment');
            }
        }

        // Enroll with Azure Voice ID (primary)
        $azureResult = $this->enrollWithAzure($user, $audioSamples);
        
        // Fallback to Amazon if Azure fails
        if ($azureResult['status'] === 'error') {
            $azureResult = $this->enrollWithAmazon($user, $audioSamples);
        }

        // Store voiceprint profile
        $voiceProfile = VoiceProfile::updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ],
            [
                'profile_id' => $azureResult['profile_id'],
                'provider' => $azureResult['provider'],
                'enrollment_status' => 'enrolled',
                'enrollment_phrases' => $azureResult['phrases'],
                'sample_count' => count($audioSamples),
                'enrolled_at' => now(),
                'last_verified_at' => null,
                'is_active' => true,
            ]
        );

        // Audit log
        $this->audit->logEvent('voice_enrollment_completed', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'profile_id' => $voiceProfile->profile_id,
            'provider' => $voiceProfile->provider,
            'sample_count' => $voiceProfile->sample_count,
        ], 'security');

        return [
            'success' => true,
            'profile_id' => $voiceProfile->profile_id,
            'enrollment_status' => $voiceProfile->enrollment_status,
            'provider' => $voiceProfile->provider,
        ];
    }

    /**
     * Verify user voice
     */
    public function verifyVoice(
        User $user,
        string $audioSample, // base64-encoded audio
        ?string $expectedPhrase = null, // For text-dependent verification
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'voice_verification',
            amount: 0,
            correlationId: $correlationId,
        );

        $startTime = microtime(true);

        // Get user's voice profile
        $voiceProfile = VoiceProfile::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$voiceProfile) {
            return [
                'success' => false,
                'reason' => 'Voice profile not found',
                'requires_enrollment' => true,
            ];
        }

        // Validate audio quality
        $quality = $this->validateAudioQuality($audioSample);
        if (!$quality['is_valid']) {
            return [
                'success' => false,
                'reason' => 'Audio quality validation failed: ' . $quality['reason'],
            ];
        }

        // Anti-spoofing check
        $spoofCheck = $this->antiSpoofing->detect($audioSample);
        if ($spoofCheck['is_spoofed']) {
            $this->audit->logEvent('voice_verification_spoof_detected', [
                'user_id' => $user->id,
                'reason' => $spoofCheck['reason'],
            ], 'security');

            return [
                'success' => false,
                'reason' => 'Potential spoofing detected',
                'spoof_reason' => $spoofCheck['reason'],
            ];
        }

        // Verify with primary provider
        $verificationResult = match ($voiceProfile->provider) {
            'azure' => $this->verifyWithAzure($voiceProfile, $audioSample, $expectedPhrase),
            'amazon' => $this->verifyWithAmazon($voiceProfile, $audioSample, $expectedPhrase),
            default => throw new \DomainException('Unknown voice provider'),
        };

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        // Update voice profile
        $voiceProfile->update([
            'last_verified_at' => now(),
            'verification_count' => $voiceProfile->verification_count + 1,
        ]);

        // Log verification attempt
        VoiceVerificationLog::create([
            'voice_profile_id' => $voiceProfile->id,
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'success' => $verificationResult['success'],
            'confidence_score' => $verificationResult['confidence'] ?? 0,
            'expected_phrase' => $expectedPhrase,
            'anti_spoof_check' => $spoofCheck,
            'latency_ms' => $latencyMs,
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->logEvent('voice_verification_completed', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'success' => $verificationResult['success'],
            'confidence' => $verificationResult['confidence'] ?? 0,
            'latency_ms' => $latencyMs,
        ], 'security');

        return [
            'success' => $verificationResult['success'],
            'confidence' => $verificationResult['confidence'] ?? 0,
            'latency_ms' => $latencyMs,
            'reason' => $verificationResult['reason'] ?? null,
        ];
    }

    /**
     * Verify voice with challenge-response (liveness detection)
     */
    public function verifyWithChallenge(
        User $user,
        string $audioSample,
        string $challengePhrase,
        string $correlationId = ''
    ): array {
        // First, verify the phrase matches the challenge
        $phraseMatch = $this->verifyPhrase($audioSample, $challengePhrase);
        
        if (!$phraseMatch['matches']) {
            return [
                'success' => false,
                'reason' => 'Challenge phrase does not match',
                'expected_phrase' => $challengePhrase,
            ];
        }

        // Then verify voice identity
        return $this->verifyVoice($user, $audioSample, $challengePhrase, $correlationId);
    }

    /**
     * Generate random challenge phrase
     */
    public function generateChallengePhrase(): string
    {
        return self::ENROLLMENT_PHRASES[array_rand(self::ENROLLMENT_PHRASES)];
    }

    /**
     * Delete voice profile (right to be forgotten)
     */
    public function deleteVoiceProfile(User $user, string $correlationId = ''): void
    {
        $voiceProfile = VoiceProfile::where('user_id', $user->id)->first();

        if (!$voiceProfile) {
            return;
        }

        // Delete from provider
        match ($voiceProfile->provider) {
            'azure' => $this->deleteFromAzure($voiceProfile),
            'amazon' => $this->deleteFromAmazon($voiceProfile),
            default => null,
        };

        // Delete from database
        $voiceProfile->delete();

        // Audit log
        $this->audit->logEvent('voice_profile_deleted', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ], 'security');
    }

    private function enrollWithAzure(User $user, array $audioSamples): array
    {
        try {
            $profileId = $this->azureVoice->createProfile($user->id);
            
            foreach ($audioSamples as $sample) {
                $this->azureVoice->enrollSegment($profileId, $sample);
            }

            return [
                'status' => 'success',
                'profile_id' => $profileId,
                'provider' => 'azure',
                'phrases' => self::ENROLLMENT_PHRASES,
            ];
        } catch (\Throwable $e) {
            Log::error('Azure Voice enrollment failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    private function enrollWithAmazon(User $user, array $audioSamples): array
    {
        try {
            $profileId = $this->amazonVoice->createProfile($user->id);
            
            foreach ($audioSamples as $sample) {
                $this->amazonVoice->enrollSegment($profileId, $sample);
            }

            return [
                'status' => 'success',
                'profile_id' => $profileId,
                'provider' => 'amazon',
                'phrases' => self::ENROLLMENT_PHRASES,
            ];
        } catch (\Throwable $e) {
            Log::error('Amazon Voice enrollment failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    private function verifyWithAzure(VoiceProfile $voiceProfile, string $audioSample, ?string $expectedPhrase): array
    {
        try {
            $result = $this->azureVoice->verify(
                $voiceProfile->profile_id,
                $audioSample,
                $expectedPhrase
            );

            $success = $result['confidence'] >= self::VERIFICATION_THRESHOLD;

            return [
                'success' => $success,
                'confidence' => $result['confidence'],
                'reason' => $success ? null : 'Confidence below threshold',
            ];
        } catch (\Throwable $e) {
            Log::error('Azure Voice verification failed', [
                'profile_id' => $voiceProfile->profile_id,
                'error' => $e->getMessage(),
            ]);
            
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    private function verifyWithAmazon(VoiceProfile $voiceProfile, string $audioSample, ?string $expectedPhrase): array
    {
        try {
            $result = $this->amazonVoice->verify(
                $voiceProfile->profile_id,
                $audioSample,
                $expectedPhrase
            );

            $success = $result['confidence'] >= self::VERIFICATION_THRESHOLD;

            return [
                'success' => $success,
                'confidence' => $result['confidence'],
                'reason' => $success ? null : 'Confidence below threshold',
            ];
        } catch (\Throwable $e) {
            Log::error('Amazon Voice verification failed', [
                'profile_id' => $voiceProfile->profile_id,
                'error' => $e->getMessage(),
            ]);
            
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    private function validateAudioQuality(string $audioSample): array
    {
        // Decode base64 audio
        $audioData = base64_decode($audioSample);
        
        // Check minimum duration (2 seconds)
        $duration = $this->getAudioDuration($audioData);
        if ($duration < 2.0) {
            return ['is_valid' => false, 'reason' => 'Audio too short (<2s)'];
        }

        // Check maximum duration (30 seconds)
        if ($duration > 30.0) {
            return ['is_valid' => false, 'reason' => 'Audio too long (>30s)'];
        }

        // Check SNR (Signal-to-Noise Ratio)
        $snr = $this->calculateSNR($audioData);
        if ($snr < 20) {
            return ['is_valid' => false, 'reason' => 'Low SNR (<20dB)'];
        }

        return ['is_valid' => true, 'duration' => $duration, 'snr' => $snr];
    }

    private function getAudioDuration(string $audioData): float
    {
        // Use PHP getID3 or similar library
        // For now, return placeholder
        return 5.0; // 5 seconds
    }

    private function calculateSNR(string $audioData): float
    {
        // Calculate signal-to-noise ratio
        // For now, return placeholder
        return 25.0; // 25dB
    }

    private function verifyPhrase(string $audioSample, string $expectedPhrase): array
    {
        // Use speech-to-text to verify the spoken phrase
        // For now, return placeholder
        return ['matches' => true];
    }

    private function deleteFromAzure(VoiceProfile $voiceProfile): void
    {
        $this->azureVoice->deleteProfile($voiceProfile->profile_id);
    }

    private function deleteFromAmazon(VoiceProfile $voiceProfile): void
    {
        $this->amazonVoice->deleteProfile($voiceProfile->profile_id);
    }
}
```

### 4.2. Anti-Spoofing Service

```php
// app/Services/Biometrics/AntiSpoofingService.php

final readonly class AntiSpoofingService
{
    private const SPOOF_THRESHOLD = 0.7;

    public function __construct(
        private readonly SpoofDetectionModel $spoofModel,
        private readonly LivenessDetector $livenessDetector,
    ) {}

    /**
     * Detect if audio is spoofed (replay or synthetic)
     */
    public function detect(string $audioSample): array
    {
        // Replay attack detection
        $replayScore = $this->detectReplayAttack($audioSample);
        
        // Synthetic voice detection
        $syntheticScore = $this->detectSyntheticVoice($audioSample);
        
        // Liveness detection
        $livenessScore = $this->livenessDetector->detect($audioSample);

        // Aggregate scores
        $overallScore = max($replayScore, $syntheticScore, 1 - $livenessScore);
        $isSpoofed = $overallScore > self::SPOOF_THRESHOLD;

        $reasons = [];
        if ($replayScore > self::SPOOF_THRESHOLD) {
            $reasons[] = 'replay_attack';
        }
        if ($syntheticScore > self::SPOOF_THRESHOLD) {
            $reasons[] = 'synthetic_voice';
        }
        if ($livenessScore < (1 - self::SPOOF_THRESHOLD)) {
            $reasons[] = 'liveness_failed';
        }

        return [
            'is_spoofed' => $isSpoofed,
            'overall_score' => $overallScore,
            'replay_score' => $replayScore,
            'synthetic_score' => $syntheticScore,
            'liveness_score' => $livenessScore,
            'reason' => implode(', ', $reasons),
        ];
    }

    private function detectReplayAttack(string $audioSample): float
    {
        // Use ML model to detect replay attacks
        // Features: spectral patterns, noise characteristics, device fingerprints
        return $this->spoofModel->predictReplay($audioSample);
    }

    private function detectSyntheticVoice(string $audioSample): float
    {
        // Use ML model to detect AI-generated voices
        // Features: spectral anomalies, unnatural patterns, TTS artifacts
        return $this->spoofModel->predictSynthetic($audioSample);
    }
}
```

### 4.3. Multi-Modal Fusion Engine

```php
// app/Services/Biometrics/MultiModalFusionService.php

final readonly class MultiModalFusionService
{
    private const FUSION_THRESHOLD = 0.80;

    public function __construct(
        private readonly VoiceBiometricsService $voiceBiometrics,
        private readonly BehavioralBiometricsService $behavioralBiometrics,
    ) {}

    /**
     * Fuse multiple biometric modalities for authentication
     */
    public function authenticate(
        User $user,
        array $modalities,
        string $correlationId = ''
    ): array {
        $scores = [];
        $weights = [];

        // Voice biometrics
        if (isset($modalities['voice'])) {
            $voiceResult = $this->voiceBiometrics->verifyVoice(
                $user,
                $modalities['voice']['audio'],
                $modalities['voice']['phrase'] ?? null,
                $correlationId
            );
            $scores['voice'] = $voiceResult['success'] ? ($voiceResult['confidence'] ?? 0) : 0;
            $weights['voice'] = 0.35; // 35% weight
        }

        // Behavioral biometrics
        if (isset($modalities['behavioral'])) {
            $behavioralResult = $this->behavioralBiometrics->analyzeSignals(
                $user,
                $modalities['behavioral'],
                $modalities['session_id'] ?? ''
            );
            $scores['behavioral'] = $behavioralResult['overall_score'];
            $weights['behavioral'] = 0.35; // 35% weight
        }

        // Face biometrics (via Passkeys)
        if (isset($modalities['face'])) {
            $scores['face'] = $modalities['face']['success'] ? 1.0 : 0;
            $weights['face'] = 0.30; // 30% weight
        }

        // Calculate weighted average
        if (empty($scores)) {
            return [
                'success' => false,
                'reason' => 'No biometric modalities provided',
            ];
        }

        // Normalize weights
        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn($w) => $w / $totalWeight, $weights);

        // Calculate fused score
        $fusedScore = 0;
        foreach ($scores as $modality => $score) {
            $fusedScore += $score * $normalizedWeights[$modality];
        }

        $success = $fusedScore >= self::FUSION_THRESHOLD;

        return [
            'success' => $success,
            'fused_score' => $fusedScore,
            'threshold' => self::FUSION_THRESHOLD,
            'modalities' => $scores,
            'weights' => $normalizedWeights,
        ];
    }

    /**
     * Adaptive fusion based on available modalities
     */
    public function adaptiveAuthenticate(
        User $user,
        array $availableModalities,
        string $correlationId = ''
    ): array {
        // If all modalities available, use standard fusion
        if (count($availableModalities) >= 2) {
            return $this->authenticate($user, $availableModalities, $correlationId);
        }

        // If only one modality, use single-modality authentication
        if (isset($availableModalities['voice'])) {
            return $this->voiceBiometrics->verifyVoice(
                $user,
                $availableModalities['voice']['audio'],
                $availableModalities['voice']['phrase'] ?? null,
                $correlationId
            );
        }

        if (isset($availableModalities['behavioral'])) {
            $result = $this->behavioralBiometrics->analyzeSignals(
                $user,
                $availableModalities['behavioral'],
                $availableModalities['session_id'] ?? ''
            );
            
            return [
                'success' => !$result['is_anomalous'],
                'confidence' => $result['overall_score'],
            ];
        }

        return [
            'success' => false,
            'reason' => 'No valid biometric modalities available',
        ];
    }
}
```

---

## 5. Database Schema

```php
// database/migrations/2026_04_19_000006_create_voice_profiles_table.php

public function up(): void
{
    Schema::create('voice_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->string('profile_id')->unique(); // Provider's profile ID
        $table->enum('provider', ['azure', 'amazon'])->default('azure');
        $table->enum('enrollment_status', ['pending', 'enrolled', 'failed'])->default('pending');
        $table->json('enrollment_phrases')->nullable();
        $table->integer('sample_count')->default(0);
        $table->timestamp('enrolled_at')->nullable();
        $table->timestamp('last_verified_at')->nullable();
        $table->integer('verification_count')->default(0);
        $table->integer('success_count')->default(0);
        $table->integer('failure_count')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        
        $table->index(['user_id', 'is_active']);
        $table->index('provider');
        $table->index('enrollment_status');
    });
}

// database/migrations/2026_04_19_000007_create_voice_verification_logs_table.php

public function up(): void
{
    Schema::create('voice_verification_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('voice_profile_id')->constrained('voice_profiles')->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->boolean('success')->default(false);
        $table->decimal('confidence_score', 5, 4)->default(0);
        $table->string('expected_phrase')->nullable();
        $table->json('anti_spoof_check')->nullable();
        $table->integer('latency_ms')->default(0);
        $table->string('correlation_id')->nullable();
        $table->string('failure_reason')->nullable();
        $table->timestamps();
        
        $table->index(['voice_profile_id', 'created_at']);
        $table->index(['user_id', 'success']);
        $table->index('created_at');
    });
}
```

---

## 6. API Endpoints

```php
// app/Http/Controllers/Api/VoiceBiometricsController.php

final class VoiceBiometricsController extends Controller
{
    public function __construct(
        private readonly VoiceBiometricsService $voiceBiometrics,
        private readonly MultiModalFusionService $fusion,
    ) {}

    /**
     * Enroll voiceprint
     * POST /api/v1/biometrics/voice/enroll
     */
    public function enroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio_samples' => 'required|array|min:3',
            'audio_samples.*' => 'required|string', // base64
        ]);

        $result = $this->voiceBiometrics->enrollVoiceprint(
            $request->user(),
            $validated['audio_samples'],
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Verify voice
     * POST /api/v1/biometrics/voice/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio_sample' => 'required|string', // base64
            'expected_phrase' => 'nullable|string',
        ]);

        $result = $this->voiceBiometrics->verifyVoice(
            $request->user(),
            $validated['audio_sample'],
            $validated['expected_phrase'] ?? null,
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Verify with challenge (liveness detection)
     * POST /api/v1/biometrics/voice/challenge
     */
    public function verifyWithChallenge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio_sample' => 'required|string',
            'challenge_phrase' => 'required|string',
        ]);

        $result = $this->voiceBiometrics->verifyWithChallenge(
            $request->user(),
            $validated['audio_sample'],
            $validated['challenge_phrase'],
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Generate challenge phrase
     * GET /api/v1/biometrics/voice/challenge
     */
    public function generateChallenge(): JsonResponse
    {
        $phrase = $this->voiceBiometrics->generateChallengePhrase();

        return response()->json([
            'challenge_phrase' => $phrase,
        ]);
    }

    /**
     * Multi-modal fusion authentication
     * POST /api/v1/biometrics/fusion/authenticate
     */
    public function fusionAuthenticate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'modalities' => 'required|array',
            'modalities.voice.audio' => 'nullable|string',
            'modalities.voice.phrase' => 'nullable|string',
            'modalities.behavioral' => 'nullable|array',
            'modalities.face.success' => 'nullable|boolean',
            'session_id' => 'nullable|string',
        ]);

        $result = $this->fusion->authenticate(
            $request->user(),
            $validated['modalities'],
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Delete voice profile
     * DELETE /api/v1/biometrics/voice/profile
     */
    public function deleteProfile(Request $request): JsonResponse
    {
        $this->voiceBiometrics->deleteVoiceProfile(
            $request->user(),
            $request->header('X-Correlation-ID'),
        );

        return response()->json(['success' => true]);
    }
}
```

---

## 7. Frontend Components

```typescript
// resources/js/Components/Biometrics/VoiceEnrollment.vue

<script setup lang="ts">
import { ref } from 'vue';

const audioSamples = ref<string[]>([]);
const isRecording = ref(false);
const enrollmentStatus = ref<'idle' | 'recording' | 'processing' | 'success' | 'error'>('idle');
const errorMessage = ref('');

const startRecording = async () => {
  isRecording.value = true;
  enrollmentStatus.value = 'recording';
  
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const mediaRecorder = new MediaRecorder(stream);
    const chunks: Blob[] = [];

    mediaRecorder.ondataavailable = (e) => chunks.push(e.data);
    mediaRecorder.onstop = async () => {
      const blob = new Blob(chunks, { type: 'audio/webm' });
      const base64 = await blobToBase64(blob);
      audioSamples.value.push(base64);
      isRecording.value = false;
      
      if (audioSamples.value.length >= 3) {
        enrollmentStatus.value = 'processing';
        await enrollVoiceprint();
      }
    };

    mediaRecorder.start();
    
    // Stop after 5 seconds
    setTimeout(() => mediaRecorder.stop(), 5000);
  } catch (error) {
    enrollmentStatus.value = 'error';
    errorMessage.value = 'Microphone access denied';
  }
};

const enrollVoiceprint = async () => {
  try {
    const response = await fetch('/api/v1/biometrics/voice/enroll', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
      },
      body: JSON.stringify({
        audio_samples: audioSamples.value,
      }),
    });

    const result = await response.json();
    
    if (result.success) {
      enrollmentStatus.value = 'success';
    } else {
      enrollmentStatus.value = 'error';
      errorMessage.value = result.message || 'Enrollment failed';
    }
  } catch (error) {
    enrollmentStatus.value = 'error';
    errorMessage.value = 'Network error';
  }
};

const blobToBase64 = (blob: Blob): Promise<string> => {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result as string);
    reader.readAsDataURL(blob);
  });
};
</script>

<template>
  <div class="voice-enrollment">
    <h2>Voice Biometrics Enrollment</h2>
    
    <div v-if="enrollmentStatus === 'idle'">
      <p>Please record 3 voice samples (5 seconds each)</p>
      <p>Say: "Мой голос — это мой пароль"</p>
      <button @click="startRecording" :disabled="isRecording">
        {{ isRecording ? 'Recording...' : 'Start Recording' }}
      </button>
      <p>Samples recorded: {{ audioSamples.length }}/3</p>
    </div>

    <div v-if="enrollmentStatus === 'recording'">
      <p>Recording... Please speak clearly</p>
      <div class="recording-indicator"></div>
    </div>

    <div v-if="enrollmentStatus === 'processing'">
      <p>Processing voice samples...</p>
    </div>

    <div v-if="enrollmentStatus === 'success'">
      <p class="success">Voice enrollment successful!</p>
    </div>

    <div v-if="enrollmentStatus === 'error'">
      <p class="error">{{ errorMessage }}</p>
      <button @click="enrollmentStatus = 'idle'">Try Again</button>
    </div>
  </div>
</template>

<style scoped>
.recording-indicator {
  width: 50px;
  height: 50px;
  background: red;
  border-radius: 50%;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.success { color: green; }
.error { color: red; }
</style>
```

---

## 8. Configuration

```php
// config/voice_biometrics.php

return [
    'azure' => [
        'api_key' => env('AZURE_VOICE_API_KEY'),
        'region' => env('AZURE_VOICE_REGION', 'westeurope'),
        'endpoint' => env('AZURE_VOICE_ENDPOINT'),
    ],
    'amazon' => [
        'access_key' => env('AWS_ACCESS_KEY_ID'),
        'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'enrollment' => [
        'min_samples' => 3,
        'max_samples' => 5,
        'min_duration_seconds' => 2,
        'max_duration_seconds' => 30,
        'min_snr_db' => 20,
    ],
    'verification' => [
        'threshold' => 0.85,
        'timeout_seconds' => 10,
    ],
    'anti_spoofing' => [
        'threshold' => 0.7,
        'enabled' => true,
    ],
    'fusion' => [
        'threshold' => 0.80,
        'weights' => [
            'voice' => 0.35,
            'behavioral' => 0.35,
            'face' => 0.30,
        ],
    ],
];
```

---

## 9. Implementation Timeline

**Week 1-2: Voice Biometrics Service**
- Day 1-3: Azure Voice ID integration
- Day 4-5: Amazon Voice ID integration (fallback)
- Day 6-7: VoiceBiometricsService implementation
- Day 8-10: Anti-spoofing service + ML model

**Week 3: Multi-Modal Fusion Engine**
- Day 1-3: MultiModalFusionService implementation
- Day 4-5: Integration with BehavioralBiometricsService
- Day 6-7: Unit tests

**Week 4: Call Center Integration**
- Day 1-3: API endpoints + controllers
- Day 4-5: Frontend components (Vue)
- Day 6-7: Integration with support ticketing system

**Week 5: Testing + Documentation**
- Day 1-3: End-to-end testing
- Day 4-5: Performance testing
- Day 6-7: Documentation + deployment

---

## 10. Success Criteria

- [ ] Voice enrollment accuracy >95%
- [ ] Verification FAR <0.1%, FRR <1%
- [ ] Verification latency <2s (P95)
- [ ] Anti-spoofing detection rate >90%
- [ ] Multi-modal fusion improves accuracy by >10%
- [ ] Call center integration operational
- [ ] All unit tests passing (>90% coverage)
- [ ] Documentation complete

---

**Document Version:** 1.0  
**Last Updated:** April 19, 2026  
**Next Review:** April 26, 2026
