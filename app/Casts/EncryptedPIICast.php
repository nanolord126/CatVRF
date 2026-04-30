<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted PII Cast
 *
 * Encrypts/decrypts PII fields at rest (152-ФЗ compliance)
 * Uses Laravel's built-in encryption with AES-256
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class EncryptedPIICast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString($value);
    }
}
