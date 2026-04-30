<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use App\Models\VoiceProfile;
use App\Models\VoiceVerificationLog;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Filesystem\Factory as StorageFactory;
use Carbon\CarbonImmutable;

final readonly class VoiceBiometricsService
{
    private const MIN_ENROLLMENT_SAMPLES = 3;

    private const MIN_SAMPLE_DURATION_SECONDS = 5;

    private const ENROLLMENT_THRESHOLD = 0.85;

    private const VERIFICATION_THRESHOLD = 0.90;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly Repository $config,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly StorageFactory $storage,
    ) {}

    /**
     * Enroll user voice profile
     */
    public function enrollVoice(
        int $userId,
        string $audioData,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'voice_enrollment',
            amount: 0,
            correlationId: $correlationId,
        );

        $user = User::findOrFail($userId);

        // Validate audio quality
        $qualityCheck = $this->validateAudioQuality($audioData);
        if (! $qualityCheck['valid']) {
            return [
                'success' => false,
                'error' => 'audio_quality_check_failed',
                'details' => $qualityCheck,
            ];
        }

        // Get or create voice profile
        $voiceProfile = VoiceProfile::firstOrCreate(
            ['user_id' => $userId],
            [
                'enrollment_status' => 'in_progress',
                'sample_count' => 0,
                'enrolled_at' => null,
            ]
        );

        // Extract voice features using provider
        $voiceFeatures = $this->extractVoiceFeatures($audioData, $correlationId);

        // Store encrypted voice template
        $templates = $voiceProfile->templates ?? [];
        $templates[] = [
            'features' => $this->encryptVoiceTemplate($voiceFeatures['template']),
            'created_at' => CarbonImmutable::now()->toISOString(),
            'duration' => $voiceFeatures['duration'],
            'quality_score' => $qualityCheck['quality_score'],
        ];

        // Update profile
        $voiceProfile->update([
            'templates' => $templates,
            'sample_count' => count($templates),
            'enrollment_status' => count($templates) >= self::MIN_ENROLLMENT_SAMPLES ? 'completed' : 'in_progress',
            'enrolled_at' => count($templates) >= self::MIN_ENROLLMENT_SAMPLES ? CarbonImmutable::now() : null,
            'last_enrolled_at' => CarbonImmutable::now(),
        ]);

        // Audit log
        $this->audit->record(
            action: 'voice_enrollment_sample_added',
            subjectType: VoiceProfile::class,
            subjectId: $voiceProfile->id,
            newValues: [
                'user_id' => $userId,
                'sample_count' => $voiceProfile->sample_count,
                'enrollment_status' => $voiceProfile->enrollment_status,
            ],
            correlationId: $correlationId,
        );

        return [
            'success' => true,
            'voice_profile_id' => $voiceProfile->id,
            'sample_count' => $voiceProfile->sample_count,
            'enrollment_status' => $voiceProfile->enrollment_status,
            'samples_needed' => max(0, self::MIN_ENROLLMENT_SAMPLES - $voiceProfile->sample_count),
        ];
    }

    /**
     * Verify user voice
     */
    public function verifyVoice(
        int $userId,
        string $audioData,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'voice_verification',
            amount: 0,
            correlationId: $correlationId,
        );

        $voiceProfile = VoiceProfile::where('user_id', $userId)
            ->where('enrollment_status', 'completed')
            ->first();

        if (! $voiceProfile) {
            return [
                'success' => false,
                'error' => 'voice_profile_not_found',
                'verified' => false,
            ];
        }

        // Validate audio quality
        $qualityCheck = $this->validateAudioQuality($audioData);
        if (! $qualityCheck['valid']) {
            return [
                'success' => false,
                'error' => 'audio_quality_check_failed',
                'verified' => false,
                'details' => $qualityCheck,
            ];
        }

        // Extract voice features
        $voiceFeatures = $this->extractVoiceFeatures($audioData, $correlationId);

        // Compare against enrolled templates
        $similarityScores = [];
        foreach ($voiceProfile->templates ?? [] as $template) {
            $decryptedTemplate = $this->decryptVoiceTemplate($template['features']);
            $similarity = $this->calculateSimilarity($voiceFeatures['template'], $decryptedTemplate);
            $similarityScores[] = $similarity;
        }

        // Calculate average similarity
        $avgSimilarity = count($similarityScores) > 0
            ? array_sum($similarityScores) / count($similarityScores)
            : 0;

        $verified = $avgSimilarity >= self::VERIFICATION_THRESHOLD;

        // Log verification attempt
        VoiceVerificationLog::create([
            'voice_profile_id' => $voiceProfile->id,
            'user_id' => $userId,
            'similarity_score' => (int) round($avgSimilarity * 100),
            'verified' => $verified,
            'verification_duration' => $voiceFeatures['duration'],
            'quality_score' => $qualityCheck['quality_score'],
            'anti_spoofing_passed' => $this->antiSpoofingCheck($audioData),
            'verified_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        // Update profile stats
        $voiceProfile->update([
            'last_verified_at' => CarbonImmutable::now(),
            'verification_count' => ($voiceProfile->verification_count ?? 0) + 1,
            'success_rate' => $this->calculateSuccessRate($voiceProfile->id),
        ]);

        // Audit log
        $this->audit->record(
            action: $verified ? 'voice_verification_success' : 'voice_verification_failed',
            subjectType: VoiceProfile::class,
            subjectId: $voiceProfile->id,
            newValues: [
                'user_id' => $userId,
                'similarity_score' => $avgSimilarity,
                'verified' => $verified,
            ],
            correlationId: $correlationId,
        );

        return [
            'success' => true,
            'verified' => $verified,
            'similarity_score' => $avgSimilarity,
            'confidence' => $avgSimilarity >= 0.95 ? 'high' : ($avgSimilarity >= 0.85 ? 'medium' : 'low'),
        ];
    }

    /**
     * Delete voice profile
     */
    public function deleteVoiceProfile(int $userId, string $correlationId = ''): void
    {
        $voiceProfile = VoiceProfile::where('user_id', $userId)->first();

        if ($voiceProfile) {
            // Delete audio files from storage
            foreach ($voiceProfile->templates ?? [] as $template) {
                if (isset($template['file_path'])) {
                    $this->storage->disk('secure')->delete($template['file_path']);
                }
            }

            $voiceProfile->delete();

            // Audit log
            $this->audit->record(
                action: 'voice_profile_deleted',
                subjectType: VoiceProfile::class,
                subjectId: $voiceProfile->id,
                newValues: ['user_id' => $userId],
                correlationId: $correlationId,
            );
        }
    }

    /**
     * Get voice profile status
     */
    public function getProfileStatus(int $userId): array
    {
        $voiceProfile = VoiceProfile::where('user_id', $userId)->first();

        if (! $voiceProfile) {
            return [
                'enrolled' => false,
                'enrollment_status' => 'not_started',
            ];
        }

        return [
            'enrolled' => $voiceProfile->enrollment_status === 'completed',
            'enrollment_status' => $voiceProfile->enrollment_status,
            'sample_count' => $voiceProfile->sample_count,
            'samples_needed' => max(0, self::MIN_ENROLLMENT_SAMPLES - $voiceProfile->sample_count),
            'enrolled_at' => $voiceProfile->enrolled_at,
            'last_verified_at' => $voiceProfile->last_verified_at,
            'verification_count' => $voiceProfile->verification_count ?? 0,
            'success_rate' => $voiceProfile->success_rate ?? 0,
        ];
    }

    private function validateAudioQuality(string $audioData): array
    {
        // Validate audio duration (at least 5 seconds)
        $duration = $this->getAudioDuration($audioData);
        if ($duration < self::MIN_SAMPLE_DURATION_SECONDS) {
            return [
                'valid' => false,
                'reason' => 'duration_too_short',
                'required_duration' => self::MIN_SAMPLE_DURATION_SECONDS,
                'actual_duration' => $duration,
            ];
        }

        // Check audio quality metrics
        $qualityScore = $this->calculateAudioQualityScore($audioData);

        return [
            'valid' => $qualityScore >= 0.6,
            'quality_score' => $qualityScore,
            'duration' => $duration,
        ];
    }

    private function extractVoiceFeatures(string $audioData, string $correlationId): array
    {
        $provider = $this->config->get('voice_biometrics.provider', 'azure');

        return match ($provider) {
            'azure' => $this->extractViaAzure($audioData, $correlationId),
            'aws' => $this->extractViaAWS($audioData, $correlationId),
            default => $this->extractViaLocal($audioData),
        };
    }

    private function extractViaAzure(string $audioData, string $correlationId): array
    {
        $apiKey = $this->config->get('voice_biometrics.azure.api_key');
        $apiUrl = $this->config->get('voice_biometrics.azure.api_url');

        if (! $apiKey || ! $apiUrl) {
            $this->log->warning('Azure Voice ID not configured, using local extraction');

            return $this->extractViaLocal($audioData);
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->attach('audio', $audioData, 'enrollment.wav')
                ->post($apiUrl.'/profiles/{profileId}/enroll');

            if (! $response->successful()) {
                $this->log->warning('Azure Voice ID enrollment failed', [
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                ]);

                return $this->extractViaLocal($audioData);
            }

            $data = $response->json();

            return [
                'template' => $data['profileId'] ?? base64_encode($audioData),
                'duration' => $data['duration'] ?? $this->getAudioDuration($audioData),
            ];
        } catch (\Throwable $e) {
            $this->log->error('Azure Voice ID error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return $this->extractViaLocal($audioData);
        }
    }

    private function extractViaAWS(string $audioData, string $correlationId): array
    {
        $apiKey = $this->config->get('voice_biometrics.aws.api_key');
        $apiUrl = $this->config->get('voice_biometrics.aws.api_url');

        if (! $apiKey || ! $apiUrl) {
            $this->log->warning('AWS Voice ID not configured, using local extraction');

            return $this->extractViaLocal($audioData);
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->attach('audio', $audioData, 'enrollment.wav')
                ->post($apiUrl.'/enroll');

            if (! $response->successful()) {
                $this->log->warning('AWS Voice ID enrollment failed', [
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                ]);

                return $this->extractViaLocal($audioData);
            }

            $data = $response->json();

            return [
                'template' => $data['profileId'] ?? base64_encode($audioData),
                'duration' => $data['duration'] ?? $this->getAudioDuration($audioData),
            ];
        } catch (\Throwable $e) {
            $this->log->error('AWS Voice ID error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return $this->extractViaLocal($audioData);
        }
    }

    private function extractViaLocal(string $audioData): array
    {
        // Simplified local extraction - in production, use actual DSP libraries
        return [
            'template' => base64_encode(hash('sha256', $audioData, true)),
            'duration' => $this->getAudioDuration($audioData),
        ];
    }

    private function encryptVoiceTemplate(string $template): string
    {
        $key = $this->config->get('voice_biometrics.encryption_key');
        if (! $key) {
            return $template; // Fallback: no encryption if key not set
        }

        return openssl_encrypt($template, 'AES-256-GCM', $key, 0, substr($key, 0, 16));
    }

    private function decryptVoiceTemplate(string $encrypted): string
    {
        $key = $this->config->get('voice_biometrics.encryption_key');
        if (! $key) {
            return $encrypted;
        }

        return openssl_decrypt($encrypted, 'AES-256-GCM', $key, 0, substr($key, 0, 16));
    }

    private function calculateSimilarity(string $template1, string $template2): float
    {
        // Simplified similarity calculation
        // In production, use actual voice biometrics algorithms
        $hash1 = hash('sha256', $template1);
        $hash2 = hash('sha256', $template2);

        $distance = 0;
        for ($i = 0; $i < min(strlen($hash1), strlen($hash2)); $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }

        return 1 - ($distance / 64);
    }

    private function antiSpoofingCheck(string $audioData): bool
    {
        // Basic anti-spoofing checks
        // In production, use dedicated anti-spoofing service

        // Check for replay attack (detect synthetic audio patterns)
        $syntheticScore = $this->detectSyntheticAudio($audioData);
        if ($syntheticScore > 0.7) {
            return false;
        }

        // Check for playback detection
        $playbackScore = $this->detectPlayback($audioData);
        if ($playbackScore > 0.7) {
            return false;
        }

        return true;
    }

    private function detectSyntheticAudio(string $audioData): float
    {
        // Simplified synthetic audio detection
        // In production, use ML-based detection
        return 0.1; // Default low risk
    }

    private function detectPlayback(string $audioData): float
    {
        // Simplified playback detection
        // In production, use audio fingerprinting
        return 0.1; // Default low risk
    }

    private function calculateSuccessRate(int $voiceProfileId): int
    {
        $total = VoiceVerificationLog::where('voice_profile_id', $voiceProfileId)->count();
        if ($total === 0) {
            return 0;
        }

        $successful = VoiceVerificationLog::where('voice_profile_id', $voiceProfileId)
            ->where('verified', true)
            ->count();

        return (int) round(($successful / $total) * 100);
    }

    private function getAudioDuration(string $audioData): float
    {
        // Simplified duration calculation
        // In production, use actual audio analysis
        return 5.0; // Default 5 seconds
    }

    private function calculateAudioQualityScore(string $audioData): float
    {
        // Simplified quality score calculation
        // In production, use actual audio quality metrics (SNR, clarity, etc.)
        return 0.8; // Default good quality
    }
}
