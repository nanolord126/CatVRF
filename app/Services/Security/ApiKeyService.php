<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\ApiKey;
use App\Traits\WithAuditLogging;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class ApiKeyService
{
    use WithAuditLogging;

    public function __construct()
    {
        // AuditService is injected via trait
    }

    /**
     * Generate a new API key
     *
     * @param int $tenantId Tenant ID
     * @param int|null $userId User ID (optional)
     * @param string $name Key name for identification
     * @param array|null $abilities Abilities/permissions ['read', 'write', 'payments', ...]
     * @param int|null $ttlDays TTL in days (null = never expires)
     * @return array ['key' => string, 'preview' => string, 'apiKey' => ApiKey]
     */
    public function generate(
        int $tenantId,
        ?int $userId,
        string $name,
        ?array $abilities = null,
        ?int $ttlDays = null
    ): array {
        $key = $this->generateRandomKey();
        $keyHash = hash('sha256', $key);
        $keyPreview = substr($key, -8);

        $apiKey = ApiKey::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $keyHash,
            'key_preview' => $keyPreview,
            'abilities' => $abilities,
            'expires_at' => $ttlDays ? CarbonImmutable::now()->addDays($ttlDays) : null,
        ]);

        $this->logCreated(
            'api_key',
            $apiKey->id,
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'name' => $name,
                'abilities' => $abilities,
                'expires_at' => $apiKey->expires_at?->toIso8601String(),
            ],
            $userId,
            $tenantId
        );

        return [
            'key' => $key,
            'preview' => $keyPreview,
            'apiKey' => $apiKey,
        ];
    }

    /**
     * Validate an API key
     *
     * @param string $key Raw API key from request
     * @return ApiKey|null Returns ApiKey if valid, null if invalid
     */
    public function validate(string $key): ?ApiKey
    {
        $keyHash = hash('sha256', $key);

        $apiKey = ApiKey::where('key_hash', $keyHash)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            })
            ->first();

        if (!$apiKey) {
            return null;
        }

        // Update last_used_at
        $apiKey->update(['last_used_at' => CarbonImmutable::now()]);

        return $apiKey;
    }

    /**
     * Revoke an API key
     *
     * @param int $apiKeyId API Key ID
     * @param int $tenantId Tenant ID for authorization
     * @return bool
     */
    public function revoke(int $apiKeyId, int $tenantId): bool
    {
        $apiKey = ApiKey::where('id', $apiKeyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$apiKey) {
            throw new RuntimeException('API key not found');
        }

        $apiKey->update(['revoked_at' => CarbonImmutable::now()]);

        $this->logAction('revoked', 'api_key', $apiKeyId, [
            'tenant_id' => $tenantId,
            'key_preview' => $apiKey->key_preview,
        ]);

        return true;
    }

    /**
     * Rotate an API key (generate new key, revoke old one)
     *
     * @param int $apiKeyId API Key ID
     * @param int $tenantId Tenant ID
     * @return array New key data
     */
    public function rotate(int $apiKeyId, int $tenantId): array
    {
        $apiKey = ApiKey::where('id', $apiKeyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$apiKey) {
            throw new RuntimeException('API key not found');
        }

        // Generate new key with same settings
        return $this->generate(
            $apiKey->tenant_id,
            $apiKey->user_id,
            $apiKey->name,
            $apiKey->abilities,
            $apiKey->expires_at ? CarbonImmutable::now()->diffInDays($apiKey->expires_at) : null
        );
    }

    /**
     * Check if API key has specific ability
     *
     * @param ApiKey $apiKey
     * @param string $ability
     * @return bool
     */
    public function hasAbility(ApiKey $apiKey, string $ability): bool
    {
        return $apiKey->hasAbility($ability);
    }

    /**
     * Generate a cryptographically secure random key
     */
    private function generateRandomKey(): string
    {
        return 'catvrf_' . Str::random(64);
    }
}
