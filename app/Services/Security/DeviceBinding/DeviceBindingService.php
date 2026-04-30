<?php

declare(strict_types=1);

namespace App\Services\Security\DeviceBinding;

use App\Services\Security\BehavioralBiometricsService;
use App\Services\Security\FraudControlService;
use App\Services\Security\InsiderThreatService;
use Illuminate\Config\Repository;
use Psr\Log\LoggerInterface;

/**
 * Device Binding Service
 *
 * Orchestrates device attestation verification across multiple platforms.
 * Provides unified interface for device binding with fallback hierarchy.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class DeviceBindingService
{
    public function __construct(
        private readonly Repository $config,
        private readonly LoggerInterface $logger,
        private readonly TPMAttestationVerifier $tpmVerifier,
        private readonly AppleSecureEnclaveVerifier $appleVerifier,
        private readonly AndroidStrongBoxVerifier $androidVerifier,
        private readonly WebAuthnAttestationVerifier $webauthnVerifier,
        private readonly SoftwareFallbackVerifier $softwareVerifier,
        private readonly BehavioralBiometricsService $behavioralService,
        private readonly FraudControlService $fraudService,
        private readonly InsiderThreatService $insiderService,
    ) {}

    /**
     * Verify device attestation with fallback hierarchy
     *
     * @param  array  $attestation  Attestation data from client
     * @param  string  $expectedChallenge  Challenge that was sent to client
     * @param  string|null  $expectedOrigin  Expected origin for WebAuthn
     * @param  int|null  $userId  User ID for behavioral integration
     * @return array{valid: bool, trust_level: string, device_info: array, error?: string}
     */
    public function verifyAttestation(
        array $attestation,
        string $expectedChallenge,
        ?string $expectedOrigin = null,
        ?int $userId = null
    ): array {
        if (! $this->config->get('device-binding.enabled', true)) {
            return [
                'valid' => false,
                'trust_level' => 'none',
                'device_info' => [],
                'error' => 'Device binding is disabled',
            ];
        }

        // Determine attestation type from request
        $attestationType = $attestation['type'] ?? null;

        // If type is specified, use specific verifier
        if ($attestationType !== null) {
            $result = $this->verifyWithSpecificType($attestationType, $attestation, $expectedChallenge, $expectedOrigin);
            
            if ($result['valid']) {
                // Perform additional risk checks if user ID provided
                if ($userId !== null) {
                    $riskCheck = $this->performRiskChecks($result['device_info'], $userId);
                    if (! $riskCheck['passed']) {
                        return [
                            'valid' => false,
                            'trust_level' => 'none',
                            'device_info' => $result['device_info'],
                            'error' => 'Risk check failed: '.$riskCheck['reason'],
                        ];
                    }
                    $result['device_info']['risk_check'] = $riskCheck;
                }
            }
            
            return $result;
        }

        // Try attestation methods in priority order
        $priority = $this->config->get('device-binding.attestation_priority', [
            'tpm',
            'secure_enclave',
            'strongbox',
            'webauthn',
            'software',
        ]);

        $lastError = null;

        foreach ($priority as $type) {
            $verifier = $this->getVerifierForType($type);
            if ($verifier === null || ! $verifier->isEnabled()) {
                continue;
            }

            $this->logger->info("Trying attestation verifier: {$type}");

            $result = $verifier->verify($attestation, $expectedChallenge, $expectedOrigin);

            if ($result['valid']) {
                $this->logger->info("Attestation verified with: {$type}", [
                    'trust_level' => $result['trust_level'],
                    'device_info' => $result['device_info'],
                ]);

                // Perform additional risk checks if user ID provided
                if ($userId !== null) {
                    $riskCheck = $this->performRiskChecks($result['device_info'], $userId);
                    if (! $riskCheck['passed']) {
                        $lastError = 'Risk check failed: '.$riskCheck['reason'];
                        continue;
                    }
                    $result['device_info']['risk_check'] = $riskCheck;
                }

                return $result;
            }

            $lastError = $result['error'] ?? 'Unknown error';
            $this->logger->warning("Attestation failed with {$type}: {$lastError}");
        }

        return [
            'valid' => false,
            'trust_level' => 'none',
            'device_info' => [],
            'error' => $lastError ?? 'All attestation methods failed',
        ];
    }

    /**
     * Verify with specific attestation type
     *
     * @param  string  $type
     * @param  array  $attestation
     * @param  string  $expectedChallenge
     * @param  string|null  $expectedOrigin
     * @return array{valid: bool, trust_level: string, device_info: array, error?: string}
     */
    private function verifyWithSpecificType(
        string $type,
        array $attestation,
        string $expectedChallenge,
        ?string $expectedOrigin
    ): array {
        $verifier = $this->getVerifierForType($type);

        if ($verifier === null) {
            return [
                'valid' => false,
                'trust_level' => 'none',
                'device_info' => [],
                'error' => "Unknown attestation type: {$type}",
            ];
        }

        if (! $verifier->isEnabled()) {
            return [
                'valid' => false,
                'trust_level' => 'none',
                'device_info' => [],
                'error' => "Attestation type {$type} is disabled",
            ];
        }

        return $verifier->verify($attestation, $expectedChallenge, $expectedOrigin);
    }

    /**
     * Get verifier for attestation type
     *
     * @param  string  $type
     * @return AttestationVerifierInterface|null
     */
    private function getVerifierForType(string $type): ?AttestationVerifierInterface
    {
        return match ($type) {
            'tpm' => $this->tpmVerifier,
            'secure_enclave' => $this->appleVerifier,
            'strongbox' => $this->androidVerifier,
            'webauthn' => $this->webauthnVerifier,
            'software' => $this->softwareVerifier,
            default => null,
        };
    }

    /**
     * Perform risk checks based on device info and user behavior
     *
     * @param  array  $deviceInfo
     * @param  int  $userId
     * @return array{passed: bool, reason?: string, risk_score: float}
     */
    private function performRiskChecks(array $deviceInfo, int $userId): array
    {
        $integration = $this->config->get('device-binding.integration', []);

        // Behavioral biometrics check
        if ($integration['behavioral_biometrics']['enabled'] ?? false) {
            $behavioralResult = $this->checkBehavioralBiometrics($userId);
            if (! $behavioralResult['passed']) {
                return [
                    'passed' => false,
                    'reason' => 'Behavioral biometrics check failed',
                    'risk_score' => $behavioralResult['risk_score'],
                ];
            }
        }

        // Fraud control check
        if ($integration['fraud_control']['enabled'] ?? false) {
            $fraudResult = $this->checkFraud($userId);
            if (! $fraudResult['passed']) {
                return [
                    'passed' => false,
                    'reason' => 'Fraud control check failed',
                    'risk_score' => $fraudResult['risk_score'],
                ];
            }
        }

        // Insider threat check (for staff)
        if ($integration['insider_threat']['enabled'] ?? false) {
            $insiderResult = $this->checkInsiderThreat($userId);
            if (! $insiderResult['passed']) {
                return [
                    'passed' => false,
                    'reason' => 'Insider threat check failed',
                    'risk_score' => $insiderResult['risk_score'],
                ];
            }
        }

        return [
            'passed' => true,
            'risk_score' => 0.0,
        ];
    }

    /**
     * Check behavioral biometrics
     *
     * @param  int  $userId
     * @return array{passed: bool, risk_score: float}
     */
    private function checkBehavioralBiometrics(int $userId): array
    {
        // In production, call BehavioralBiometricsService to get current risk score
        // For now, return passed
        return [
            'passed' => true,
            'risk_score' => 0.0,
        ];
    }

    /**
     * Check fraud risk
     *
     * @param  int  $userId
     * @return array{passed: bool, risk_score: float}
     */
    private function checkFraud(int $userId): array
    {
        // In production, call FraudControlService to get current risk score
        // For now, return passed
        return [
            'passed' => true,
            'risk_score' => 0.0,
        ];
    }

    /**
     * Check insider threat
     *
     * @param  int  $userId
     * @return array{passed: bool, risk_score: float}
     */
    private function checkInsiderThreat(int $userId): array
    {
        // In production, call InsiderThreatService to get current risk score
        // For now, return passed
        return [
            'passed' => true,
            'risk_score' => 0.0,
        ];
    }

    /**
     * Check if device binding should be invalidated based on risk
     *
     * @param  int  $userId
     * @param  float  $fraudScore
     * @param  float  $behavioralScore
     * @param  float  $insiderScore
     * @return bool
     */
    public function shouldInvalidateOnRisk(
        int $userId,
        float $fraudScore = 0.0,
        float $behavioralScore = 0.0,
        float $insiderScore = 0.0
    ): bool {
        $riskInvalidation = $this->config->get('device-binding.risk_invalidation', []);

        if (! $riskInvalidation['enabled'] ?? false) {
            return false;
        }

        $fraudThreshold = $riskInvalidation['fraud_threshold'] ?? 0.8;
        $behavioralThreshold = $riskInvalidation['behavioral_threshold'] ?? 0.85;
        $insiderThreshold = $riskInvalidation['insider_threshold'] ?? 0.7;

        if ($fraudScore >= $fraudThreshold) {
            $this->logger->warning('Device binding invalidation: fraud score exceeded', [
                'user_id' => $userId,
                'fraud_score' => $fraudScore,
                'threshold' => $fraudThreshold,
            ]);

            return true;
        }

        if ($behavioralScore >= $behavioralThreshold) {
            $this->logger->warning('Device binding invalidation: behavioral score exceeded', [
                'user_id' => $userId,
                'behavioral_score' => $behavioralScore,
                'threshold' => $behavioralThreshold,
            ]);

            return true;
        }

        if ($insiderScore >= $insiderThreshold) {
            $this->logger->warning('Device binding invalidation: insider score exceeded', [
                'user_id' => $userId,
                'insider_score' => $insiderScore,
                'threshold' => $insiderThreshold,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get trust level priority for comparison
     *
     * @param  string  $trustLevel
     * @return int Higher number = higher trust
     */
    public function getTrustLevelPriority(string $trustLevel): int
    {
        return match ($trustLevel) {
            'tpm' => 100,
            'secure_enclave' => 90,
            'strongbox' => 85,
            'tee' => 80,
            'webauthn' => 70,
            'play_integrity' => 65,
            'software' => 30,
            'none' => 0,
            default => 0,
        };
    }
}
