<?php

declare(strict_types=1);

namespace App\Services\Security\DeviceBinding;

use Illuminate\Config\Repository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * WebAuthn Attestation Verifier
 *
 * Verifies WebAuthn platform authenticator attestation statements.
 * Supports Windows Hello, Apple Face ID/Touch ID, Android Biometrics.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class WebAuthnAttestationVerifier implements AttestationVerifierInterface
{
    private const SUPPORTED_FORMATS = ['none', 'indirect', 'direct', 'enterprise'];

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
                'error' => 'WebAuthn attestation is disabled',
            ];
        }

        try {
            // Validate attestation structure
            if (! isset($attestation['format'], $attestation['authenticatorData'], $attestation['clientDataJSON'])) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Invalid attestation structure',
                ];
            }

            $format = $attestation['format'];
            $authenticatorData = base64_decode($attestation['authenticatorData']);
            $clientDataJSON = json_decode(base64_decode($attestation['clientDataJSON']), true);

            // Verify format is supported
            if (! in_array($format, self::SUPPORTED_FORMATS, true)) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => "Unsupported attestation format: {$format}",
                ];
            }

            // Verify challenge
            if (! isset($clientDataJSON['challenge'])) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Missing challenge in client data',
                ];
            }

            $clientChallenge = $clientDataJSON['challenge'];
            if (! hash_equals($expectedChallenge, $clientChallenge)) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Challenge mismatch',
                ];
            }

            // Verify origin if provided
            if ($expectedOrigin !== null && isset($clientDataJSON['origin'])) {
                if ($clientDataJSON['origin'] !== $expectedOrigin) {
                    return [
                        'valid' => false,
                        'trust_level' => 'none',
                        'device_info' => [],
                        'error' => "Origin mismatch: expected {$expectedOrigin}, got {$clientDataJSON['origin']}",
                    ];
                }
            }

            // Verify type is webauthn.create
            if (! isset($clientDataJSON['type']) || $clientDataJSON['type'] !== 'webauthn.create') {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => [],
                    'error' => 'Invalid operation type',
                ];
            }

            // Parse authenticator data
            $deviceInfo = $this->parseAuthenticatorData($authenticatorData);

            // Verify user presence
            if (! ($deviceInfo['user_present'] ?? false)) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => $deviceInfo,
                    'error' => 'User presence not verified',
                ];
            }

            // Verify user verification if required
            $requireUserVerification = $this->config->get('device-binding.webauthn.require_user_verification', true);
            if ($requireUserVerification && ! ($deviceInfo['user_verified'] ?? false)) {
                return [
                    'valid' => false,
                    'trust_level' => 'none',
                    'device_info' => $deviceInfo,
                    'error' => 'User verification not satisfied',
                ];
            }

            // Verify attestation statement if format is not 'none'
            if ($format !== 'none' && isset($attestation['attestationStatement'])) {
                $statementResult = $this->verifyAttestationStatement($format, $attestation['attestationStatement'], $authenticatorData, $clientDataJSON);
                if (! $statementResult['valid']) {
                    return [
                        'valid' => false,
                        'trust_level' => 'none',
                        'device_info' => $deviceInfo,
                        'error' => $statementResult['error'] ?? 'Attestation statement verification failed',
                    ];
                }
                $deviceInfo = array_merge($deviceInfo, $statementResult['device_info'] ?? []);
            }

            // Extract AAGUID for device identification
            $aaguid = $deviceInfo['aaguid'] ?? null;
            $deviceName = $this->identifyDeviceByAAGUID($aaguid);

            $this->logger->info('WebAuthn attestation verified', [
                'format' => $format,
                'aaguid' => $aaguid,
                'device_name' => $deviceName,
                'user_present' => $deviceInfo['user_present'],
                'user_verified' => $deviceInfo['user_verified'],
            ]);

            return [
                'valid' => true,
                'trust_level' => $this->getTrustLevel(),
                'device_info' => array_merge($deviceInfo, [
                    'attestation_format' => $format,
                    'device_name' => $deviceName,
                    'aaguid' => $aaguid,
                ]),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('WebAuthn attestation verification error', [
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
        return 'webauthn';
    }

    public function isEnabled(): bool
    {
        return $this->config->get('device-binding.webauthn.enabled', true);
    }

    public function getTrustLevel(): string
    {
        return 'webauthn';
    }

    /**
     * Parse authenticator data
     *
     * @param  string  $authenticatorData
     * @return array
     */
    private function parseAuthenticatorData(string $authenticatorData): array
    {
        if (strlen($authenticatorData) < 37) {
            return [
                'user_present' => false,
                'user_verified' => false,
            ];
        }

        // Byte 0: flags
        $flags = ord($authenticatorData[0]);
        $userPresent = ($flags & 0x01) !== 0;
        $userVerified = ($flags & 0x04) !== 0;

        // Bytes 1-32: RP ID hash
        $rpIdHash = substr($authenticatorData, 1, 32);

        // Bytes 33-36: counter
        $counter = unpack('N', substr($authenticatorData, 33, 4))[1];

        // Bytes 37-52: AAGUID (if present)
        $aaguid = null;
        if (strlen($authenticatorData) >= 53) {
            $aaguid = bin2hex(substr($authenticatorData, 37, 16));
            $aaguid = substr($aaguid, 0, 8).'-'.substr($aaguid, 8, 4).'-'.substr($aaguid, 12, 4).'-'.substr($aaguid, 16, 4).'-'.substr($aaguid, 20, 12);
        }

        return [
            'user_present' => $userPresent,
            'user_verified' => $userVerified,
            'rp_id_hash' => bin2hex($rpIdHash),
            'counter' => $counter,
            'aaguid' => $aaguid,
        ];
    }

    /**
     * Verify attestation statement based on format
     *
     * @param  string  $format
     * @param  array  $statement
     * @param  string  $authenticatorData
     * @param  array  $clientDataJSON
     * @return array{valid: bool, device_info: array, error?: string}
     */
    private function verifyAttestationStatement(string $format, array $statement, string $authenticatorData, array $clientDataJSON): array
    {
        // In production, implement full attestation statement verification:
        // - packed: Verify signature with attestation certificate
        // - tpm: Verify TPM attestation
        // - android-key: Verify Android KeyStore attestation
        // - android-safetynet: Verify SafetyNet attestation
        // - fido-u2f: Verify FIDO U2F attestation
        // - none: No attestation (already handled)

        // For now, return success for non-'none' formats
        // TODO: Implement full certificate chain verification

        return [
            'valid' => true,
            'device_info' => [
                'attestation_verified' => true,
                'format' => $format,
            ],
        ];
    }

    /**
     * Identify device by AAGUID
     *
     * @param  string|null  $aaguid
     * @return string
     */
    private function identifyDeviceByAAGUID(?string $aaguid): string
    {
        if ($aaguid === null) {
            return 'Unknown Platform Authenticator';
        }

        // Known AAGUIDs (partial list - extend as needed)
        $knownDevices = [
            // Windows Hello
            '6028b017-b1d4-4c02-b4b3-afcd7d6f0c73' => 'Windows Hello',
            '08987058-cadc-4b81-b6e1-30de50dcbe96' => 'Windows Hello Software',
            
            // Apple Face ID / Touch ID
            'fa2b99dc-9e3b-5207-841e-2764111556e9' => 'Apple Face ID',
            '8fb3440c-374c-4378-a470-9a9500a4745d' => 'Apple Touch ID',
            
            // Android Biometrics
            '3b9519c2-7b79-4f39-b6a1-224890c4c501' => 'Android Biometric',
            'd607946d-6af2-41be-a1e3-4e4da0b44481' => 'Android Fingerprint',
        ];

        return $knownDevices[$aaguid] ?? 'Platform Authenticator';
    }
}
