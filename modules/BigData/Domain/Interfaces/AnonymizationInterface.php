<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Modules\BigData\Domain\Enums\AnonymizationMethod;

/**
 * Anonymization Service Interface
 *
 * Contract for PII anonymization, pseudonymization, and differential privacy
 * applied to ML features and data exports.
 */
interface AnonymizationInterface
{
    /**
     * Mask PII fields in a data row
     * Returns row with PII fields replaced by masked values
     */
    public function maskPII(array $data, array $piiFields): array;

    /**
     * Anonymize data for ML feature extraction
     * Uses k-anonymity + differential privacy
     */
    public function anonymizeForML(array $features, AnonymizationMethod $method = AnonymizationMethod::DifferentialPrivacy): array;

    /**
     * Pseudonymize a value (reversible with key)
     */
    public function pseudonymize(string $value, string $context = 'default'): string;

    /**
     * Reverse pseudonymization (requires authorization)
     */
    public function depseudonymize(string $pseudonym, string $context = 'default'): string;

    /**
     * Apply k-anonymity to a dataset
     * Generalizes quasi-identifiers until k >= threshold
     */
    public function applyKAnonymity(array $dataset, int $k = 5, array $quasiIdentifiers = []): array;

    /**
     * Add differential privacy noise to a numeric value
     * Uses Laplace mechanism with epsilon privacy budget
     */
    public function addDPNoise(float $value, float $epsilon = 1.0, float $sensitivity = 1.0): float;

    /**
     * Hash a value (irreversible)
     */
    public function hash(string $value, string $algorithm = 'sha256'): string;

    /**
     * Tokenize a value (maps to a random token, stored in vault)
     */
    public function tokenize(string $value, string $domain = 'default'): string;
}
