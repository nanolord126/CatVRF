<?php

declare(strict_types=1);

namespace App\Services\Security\DeviceBinding;

use Illuminate\Config\Repository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Software Fallback Attestation Verifier
 *
 * Fallback device binding for devices without hardware attestation support.
 * Combines device fingerprinting with behavioral biometrics for device confidence.
 *
 * WARNING: This is a fallback method with lower security than hardware attestation.
 * Should only be used when hardware attestation is unavailable.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class SoftwareFallbackVerifier implements AttestationVerifierInterface
{
    public function __construct(
        private readonly Repository $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function verify(array $attestation, string $expectedChallenge, ?string $expectedOrigin = null): array
    {
        if (! $this->isEnabled()) {
            return [
                'valid' => false,
                'trust_level' => 'none',
                'device_info' => [],
                'error' => 'Software fallback attestation is disabled',
            ];
        }

        try {
            // Validate attestation structure
            if (! isset($attestation['deviceFingerprint'], $attestation['timestamp'])) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Invalid software attestation structure',
                ];
            }

            // Verify timestamp (prevent replay attacks)
            $timestamp = $attestation['timestamp'];
            $now = time();
            $maxAge = 300; // 5 minutes for software fallback
            
            if (abs($now - $timestamp) > $maxAge) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Attestation timestamp is too old or in the future',
                ];
            }

            // Verify device fingerprint
            $fingerprintResult = $this->verifyDeviceFingerprint($attestation['deviceFingerprint']);
            if (! $fingerprintResult['valid']) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Device fingerprint verification failed: '.$fingerprintResult['error'],
                ];
            }

            // Verify behavioral biometrics if required
            $requireBehavioral = $this->config->get('device-binding.software.require_behavioral_biometrics', true);
            $behavioralScore = null;
            
            if ($requireBehavioral) {
                if (! isset($attestation['behavioralScore'])) {
                    return [
                        'valid' => false,
                        'trust_level' => 'none',
                        'device_info' => [],
                        'error' => 'Behavioral biometrics score required but not provided',
                    ];
                }

                $behavioralScore = (float) $attestation['behavioralScore'];
                $minConfidence = $this->config->get('device-binding.software.min_confidence_score', 0.7);

                if ($behavioralScore < $minConfidence) {
                    return [
                        'valid' => false,
                        'trust_level' => 'none',
                        'device_info' => [],
                        'error' => "Behavioral score {$behavioralScore} below minimum {$minConfidence}",
                    ];
                }
            }

            // Calculate overall device confidence
            $deviceConfidence = $this->calculateDeviceConfidence($fingerprintResult, $behavioralScore);

            $deviceInfo = array_merge(
                $fingerprintResult['device_info'] ?? [],
                [
                    'attestation_type' => 'software',
                    'behavioral_score' => $behavioralScore,
                    'device_confidence' => $deviceConfidence,
                    'platform' => $attestation['platform'] ?? $attestation['os'] ?? 'Unknown',
                    'user_agent' => $attestation['userAgent'] ?? null,
                ]
            );

            $this->logger->warning('Software fallback attestation used (lower security)', $deviceInfo);

            return [
                'valid' => true,
                'trust_level' => $this->getTrustLevel(),
                'device_info' => $deviceInfo,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Software fallback attestation verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valid' => false,
                'trust_level' => 'none',
                'device_info' => [],
                'error' => 'Verification error: '.$e->getMessage(),
            ];
        }
    }

    public function getType(): string
    {
        return 'software';
    }

    public function isEnabled(): bool
    {
        return $this->config->get('device-binding.software.enabled', true);
    }

    public function getTrustLevel(): string
    {
        return 'software';
    }

    /**
     * Verify device fingerprint
     *
     * @param  array  $fingerprint
     * @return array{valid: bool, device_info: array, error?: string}
     */
    private function verifyDeviceFingerprint(array $fingerprint): array
    {
        $components = $this->config->get('device-binding.software.device_fingerprint_components', []);
        $salt = $this->config->get('device-binding.software.fingerprint_salt');

        if (empty($salt)) {
            return [
                'valid' => false,
                'device_info' => [],
                'error' => 'Fingerprint salt not configured',
            ];
        }

        // Verify required components are present
        foreach ($components as $component) {
            if (! isset($fingerprint[$component])) {
                return [
                    'valid' => false,
                    'device_info' => [],
                    'error' => "Missing fingerprint component: {$component}",
                ];
            }
        }

        // Calculate expected fingerprint hash
        $expectedHash = $this->calculateFingerprintHash($fingerprint, $salt);

        // Verify fingerprint hash matches
        if (! isset($fingerprint['hash']) || ! hash_equals($expectedHash, $fingerprint['hash'])) {
            return [
                'valid' => false,
                'device_info' => [],
                'error' => 'Fingerprint hash mismatch',
            ];
        }

        return [
            'valid' => true,
            'device_info' => [
                'fingerprint_verified' => true,
                'fingerprint_components' => array_keys($fingerprint),
                'canvas_hash' => $fingerprint['canvas'] ?? null,
                'webgl_hash' => $fingerprint['webgl'] ?? null,
                'screen_resolution' => $fingerprint['screen'] ?? null,
            ],
        ];
    }

    /**
     * Calculate fingerprint hash
     *
     * @param  array  $fingerprint
     * @param  string  $salt
     * @return string
     */
    private function calculateFingerprintHash(array $fingerprint, string $salt): string
    {
        // Extract fingerprint components in a consistent order
        $components = $this->config->get('device-binding.software.device_fingerprint_components', []);
        $data = '';

        foreach ($components as $component) {
            if (isset($fingerprint[$component])) {
                $data .= $component.':'.$fingerprint[$component].'|';
            }
        }

        // Add salt and hash
        return hash('sha256', $salt.$data);
    }

    /**
     * Calculate overall device confidence score
     *
     * @param  array  $fingerprintResult
     * @param  float|null  $behavioralScore
     * @return float
     */
    private function calculateDeviceConfidence(array $fingerprintResult, ?float $behavioralScore): float
    {
        $fingerprintWeight = 0.7; // 70% weight for fingerprint
        $behavioralWeight = 0.3; // 30% weight for behavioral

        $fingerprintScore = $fingerprintResult['valid'] ? 1.0 : 0.0;
        $behavioralScoreNormalized = $behavioralScore !== null ? $behavioralScore : 0.5;

        return ($fingerprintScore * $fingerprintWeight) + ($behavioralScoreNormalized * $behavioralWeight);
    }
}
