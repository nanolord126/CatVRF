<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * AES-256-GCM Encrypted Cast for Personal Data (Post-Quantum Resistant)
 * 
 * Provides column-level encryption using AES-256-GCM for protection against
 * Grover's algorithm attacks. Effective security: 128-bit post-quantum.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 11 - Шифрование
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 * 
 * @see docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md
 */
final readonly class AES256EncryptedCast implements CastsAttributes
{
    private const ENCRYPTION_VERSION = 'v2'; // v2 = AES-256-GCM
    private const ALGORITHM = 'aes-256-gcm';
    private const IV_LENGTH = 16; // 128-bit IV for GCM
    private const TAG_LENGTH = 16; // 128-bit authentication tag

    /**
     * Decrypt the value from database using AES-256-GCM
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Check if value is encrypted with v2 (AES-256-GCM)
            if (str_starts_with($value, self::ENCRYPTION_VERSION . ':')) {
                [, $encrypted] = explode(':', $value, 2);
                return $this->decryptAES256GCM($encrypted);
            }

            // Legacy v1 support (Laravel's default encryption)
            if (str_starts_with($value, 'v1:')) {
                [, $encrypted] = explode(':', $value, 2);
                return $this->decryptLegacy($encrypted);
            }

            // Try legacy decryption without version prefix
            return $this->decryptLegacy($value);
        } catch (\Throwable $e) {
            Log::warning('Failed to decrypt AES-256 personal data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Encrypt the value for database storage using AES-256-GCM
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $encrypted = $this->encryptAES256GCM($value);
            
            // Add version prefix for future crypto-agility
            return self::ENCRYPTION_VERSION . ':' . $encrypted;
        } catch (\Throwable $e) {
            Log::error('Failed to encrypt AES-256 personal data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to encrypt personal data with AES-256-GCM', 0, $e);
        }
    }

    /**
     * Encrypt using AES-256-GCM with authenticated encryption
     */
    private function encryptAES256GCM(string $data): string
    {
        $key = $this->getAES256Key();
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed');
        }
        
        // Format: IV (16 bytes) + Tag (16 bytes) + Ciphertext
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt using AES-256-GCM with authenticated decryption
     */
    private function decryptAES256GCM(string $encrypted): string
    {
        $key = $this->getAES256Key();
        $decoded = base64_decode($encrypted);
        
        if (strlen($decoded) < self::IV_LENGTH + self::TAG_LENGTH) {
            throw new \RuntimeException('Invalid encrypted data length');
        }
        
        $iv = substr($decoded, 0, self::IV_LENGTH);
        $tag = substr($decoded, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($decoded, self::IV_LENGTH + self::TAG_LENGTH);
        
        $decrypted = openssl_decrypt(
            $ciphertext,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new \RuntimeException('AES-256-GCM decryption failed - authentication tag mismatch');
        }
        
        return $decrypted;
    }

    /**
     * Legacy decryption for v1 (Laravel's default encryption)
     */
    private function decryptLegacy(string $encrypted): string
    {
        return decrypt($encrypted);
    }

    /**
     * Get AES-256 key from configuration
     * 
     * Key must be 32 bytes (256 bits) for AES-256
     */
    private function getAES256Key(): string
    {
        $key = config('app.aes256_key');
        
        if ($key === null) {
            throw new \RuntimeException('AES-256 key not configured. Set APP_AES256_KEY in .env');
        }
        
        // Ensure key is exactly 32 bytes
        $keyBytes = base64_decode($key);
        
        if (strlen($keyBytes) !== 32) {
            throw new \RuntimeException('AES-256 key must be exactly 32 bytes (256 bits)');
        }
        
        return $keyBytes;
    }
}
