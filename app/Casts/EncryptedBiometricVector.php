<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Encrypted Biometric Vector Cast (Post-Quantum Resistant)
 * 
 * Encrypts biometric vectors at rest using AES-256-GCM for protection against
 * Grover's algorithm attacks. Effective security: 128-bit post-quantum.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 11 - Шифрование
 * УБИ.КВАНТ-002: Grover's algorithm mitigation
 * 
 * @see docs/security/QUANTUM_THREATS_GROVER_ALGORITHM.md
 */
final readonly class EncryptedBiometricVector implements CastsAttributes
{
    private const ENCRYPTION_VERSION = 'v2'; // v2 = AES-256-GCM
    private const ALGORITHM = 'aes-256-gcm';
    private const IV_LENGTH = 16; // 128-bit IV for GCM
    private const TAG_LENGTH = 16; // 128-bit authentication tag

    /**
     * Decrypt the value from database using AES-256-GCM
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            // Check if value is encrypted with v2 (AES-256-GCM)
            if (str_starts_with($value, self::ENCRYPTION_VERSION . ':')) {
                [, $encrypted] = explode(':', $value, 2);
                $decrypted = $this->decryptAES256GCM($encrypted);
            } else {
                // Legacy v1 support (Laravel's default encryption)
                $decrypted = decrypt($value);
            }
            
            if (is_string($decrypted)) {
                return json_decode($decrypted, true);
            }
            
            return $decrypted;
        } catch (\Throwable $e) {
            Log::error('Failed to decrypt biometric vector', [
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
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        try {
            $encrypted = $this->encryptAES256GCM($value);
            
            // Add version prefix for future crypto-agility
            return self::ENCRYPTION_VERSION . ':' . $encrypted;
        } catch (\Throwable $e) {
            Log::error('Failed to encrypt biometric vector', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Failed to encrypt biometric data with AES-256-GCM', 0, $e);
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
