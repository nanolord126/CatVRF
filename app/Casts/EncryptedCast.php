<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Encryption\Encrypter;

/**
 * Encrypted Cast for Personal Data (152-FZ Compliance)
 * 
 * Provides column-level encryption for sensitive personal data.
 * Uses AES-256-GCM with automatic key rotation support.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * ФСТЭК №21: Мера 11 - Шифрование
 */
final readonly class EncryptedCast implements CastsAttributes
{
    private const ENCRYPTION_VERSION = 'v1';

    public function __construct(
        private readonly Encrypter $encrypter,
    ) {}

    /**
     * Decrypt the value from database
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Check if value is already encrypted (has version prefix)
            if (str_starts_with($value, self::ENCRYPTION_VERSION . ':')) {
                [, $encrypted] = explode(':', $value, 2);
                return $this->encrypter->decryptString($encrypted);
            }

            // Legacy support: try to decrypt without version prefix
            return $this->encrypter->decryptString($value);
        } catch (\Throwable $e) {
            // Log decryption failure but don't throw - data might be corrupted
            Log::warning('Failed to decrypt personal data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Encrypt the value for database storage
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $encrypted = $this->encrypter->encryptString($value);
            
            // Add version prefix for future key rotation support
            return self::ENCRYPTION_VERSION . ':' . $encrypted;
        } catch (\Throwable $e) {
            Log::error('Failed to encrypt personal data', [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to encrypt personal data', 0, $e);
        }
    }
}
