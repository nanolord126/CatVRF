<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ApiKeyManagementService
{
    /**
     * Generate new API key for tenant
     */
    public function generateKey(
        int $tenantId,
        string $name,
        ?array $permissions = null,
        ?array $ipWhitelist = null,
        ?\DateTime $expiresAt = null
    ): array {
        $rawKey = Str::random(64);
        $keyHash = Hash::make($rawKey);
        $keyId = (string) Str::uuid();

        DB::transaction(function () use ($tenantId, $keyId, $name, $keyHash, $permissions, $ipWhitelist, $expiresAt) {
            $this->createApiKey(
                tenantId: $tenantId,
                keyId: $keyId,
                name: $name,
                keyHash: $keyHash,
                permissions: $permissions,
                ipWhitelist: $ipWhitelist,
                expiresAt: $expiresAt
            );
            
            Log::channel('audit')->info('API Key generated', [
                'tenant_id' => $tenantId,
                'key_id' => $keyId,
                'correlation_id' => request()->header('X-Correlation-ID', Str::uuid()->toString()),
            ]);
        });

        return [
            'key' => $rawKey,
            'key_id' => $keyId,
            'name' => $name,
            'warning' => 'Save this key securely. You will not be able to see it again.',
        ];
    }

    /**
     * Validate API key
     */
    public function validateKey(string $rawKey, string $clientIp): bool | array
    {
        $keyHash = $this->hashKey($rawKey);

        $apiKey = DB::table('api_keys')
            ->where('key_hash', $keyHash)
            ->where('status', 'active')
            ->first();

        if (!$apiKey || ($apiKey->expires_at && now() > $apiKey->expires_at)) {
            return false;
        }

        // Check IP whitelist
        if ($apiKey->ip_whitelist && !$this->ipMatches($clientIp, json_decode($apiKey->ip_whitelist, true))) {
            return false;
        }

        DB::transaction(function () use ($apiKey, $clientIp) {
            DB::table('api_keys')
                ->where('id', $apiKey->id)
                ->update(['last_used_at' => now()]);

            $this->logAudit($apiKey->id, 'used', $clientIp);
        });

        return [
            'tenant_id' => $apiKey->tenant_id,
            'key_id' => $apiKey->key_id,
            'permissions' => json_decode($apiKey->permissions, true) ?? [],
        ];
    }

    /**
     * Revoke API key
     */
    public function revokeKey(int $tenantId, string $keyId): bool
    {
        return DB::transaction(function () use ($tenantId, $keyId) {
            $updated = DB::table('api_keys')
                ->where('tenant_id', $tenantId)
                ->where('key_id', $keyId)
                ->update(['status' => 'revoked']);

            if ($updated) {
                $apiKey = DB::table('api_keys')
                    ->where('key_id', $keyId)
                    ->first();
                $this->logAudit($apiKey->id, 'revoked', null);
                
                Log::channel('audit')->info('API Key revoked', [
                    'tenant_id' => $tenantId,
                    'key_id' => $keyId,
                    'correlation_id' => request()->header('X-Correlation-ID', Str::uuid()->toString()),
                ]);
            }

            return $updated > 0;
        });
    }

    /**
     * Rotate API key (revoke old, create new)
     */
    public function rotateKey(int $tenantId, string $keyId): array
    {
        return DB::transaction(function () use ($tenantId, $keyId) {
            $oldKey = DB::table('api_keys')
                ->where('tenant_id', $tenantId)
                ->where('key_id', $keyId)
                ->first();

            if (!$oldKey) {
                throw new \InvalidArgumentException('API key not found');
            }

            $this->revokeKey($tenantId, $keyId);

            return $this->generateKey(
                tenantId: $tenantId,
                name: $oldKey->name . ' (rotated)',
                permissions: json_decode($oldKey->permissions, true),
                ipWhitelist: json_decode($oldKey->ip_whitelist, true),
                expiresAt: $oldKey->expires_at ? new \DateTime($oldKey->expires_at) : null
            );
        });
    }

    private function createApiKey(
        int $tenantId,
        string $keyId,
        string $name,
        string $keyHash,
        ?array $permissions,
        ?array $ipWhitelist,
        ?\DateTime $expiresAt
    ): void {
        DB::table('api_keys')->insert([
            'tenant_id' => $tenantId,
            'key_id' => $keyId,
            'name' => $name,
            'key_hash' => $keyHash,
            'key_preview' => substr($keyId, 0, 10),
            'permissions' => $permissions ? json_encode($permissions) : null,
            'ip_whitelist' => $ipWhitelist ? json_encode($ipWhitelist) : null,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hashKey(string $rawKey): string
    {
        return hash('sha256', $rawKey);
    }

    private function logAudit(int $apiKeyId, string $action, ?string $ipAddress): void
    {
        DB::table('api_key_audit_logs')->insert([
            'api_key_id' => $apiKeyId,
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => request()->header('User-Agent'),
            'metadata' => json_encode([
                'correlation_id' => request()->header('X-Correlation-ID', Str::uuid()->toString()),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ipMatches(string $clientIp, array $whitelist): bool
    {
        foreach ($whitelist as $ip) {
            if ($ip === $clientIp) {
                return true;
            }

            if (str_contains($ip, '/')) {
                if ($this->ipInCidr($clientIp, $ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        $subnet &= $mask;
        return ($ip & $mask) === $subnet;
    }
}
