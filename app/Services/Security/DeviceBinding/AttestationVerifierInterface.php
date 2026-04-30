<?php

declare(strict_types=1);

namespace App\Services\Security\DeviceBinding;

/**
 * Attestation Verifier Interface
 *
 * Interface for device attestation verification across different platforms.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
interface AttestationVerifierInterface
{
    /**
     * Verify device attestation statement
     *
     * @param  array  $attestation  Attestation data from client
     * @param  string  $expectedChallenge  Challenge that was sent to client
     * @param  string|null  $expectedOrigin  Expected origin (e.g., https://catvrf.ru)
     * @return array{valid: bool, trust_level: string, device_info: array, error?: string}
     */
    public function verify(array $attestation, string $expectedChallenge, ?string $expectedOrigin = null): array;

    /**
     * Get the attestation type this verifier handles
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Check if this verifier is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get trust level for this attestation type
     *
     * @return string 'hardware', 'tpm', 'secure_enclave', 'strongbox', 'webauthn', 'software'
     */
    public function getTrustLevel(): string;
}
