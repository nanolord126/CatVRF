<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

/**
 * Encryption Service Interface
 *
 * Contract for field-level encryption/decryption of sensitive data
 * before writing to ClickHouse or sending to external services.
 */
interface EncryptionInterface
{
    /**
     * Encrypt a value using the algorithm appropriate for the data classification
     */
    public function encrypt(string $value, string $keyId = 'default'): string;

    /**
     * Decrypt a previously encrypted value
     */
    public function decrypt(string $encryptedValue, string $keyId = 'default'): string;

    /**
     * Encrypt multiple fields in a payload
     * Returns payload with specified fields encrypted
     */
    public function encryptFields(array $payload, array $fields, string $keyId = 'default'): array;

    /**
     * Decrypt multiple fields in a payload
     */
    public function decryptFields(array $payload, array $fields, string $keyId = 'default'): array;

    /**
     * Generate a data encryption key (DEK) for envelope encryption
     */
    public function generateDEK(): string;

    /**
     * Rotate encryption key: re-encrypt with new key
     */
    public function rotateKey(string $oldKeyId, string $newKeyId): int;

    /**
     * Get current key ID for a data classification
     */
    public function getCurrentKeyId(string $classification): string;
}
