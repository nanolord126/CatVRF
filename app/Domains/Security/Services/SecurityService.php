<?php declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Domains\Security\DTOs\CreateApiKeyDto;
use App\Domains\Security\DTOs\ValidateApiKeyDto;
use App\Domains\Security\Models\ApiKey;
use App\Domains\Security\Models\SecurityEvent;
use App\Domains\Security\Models\RateLimitRecord;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class SecurityService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Generate new API key
     */
    public function generateApiKey(CreateApiKeyDto $dto, string $correlationId): array
    {
        $this->fraud->check([
            'operation' => 'api_key_generate',
            'tenant_id' => $dto->tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $rawKey = Str::random(64);
            $keyHash = Hash::make($rawKey);
            $keyId = Str::uuid()->toString();

            $apiKey = ApiKey::create([
                'tenant_id' => $dto->tenantId,
                'key_id' => $keyId,
                'name' => $dto->name,
                'key_hash' => $keyHash,
                'key_preview' => substr($keyId, 0, 10),
                'permissions' => $dto->permissions,
                'ip_whitelist' => $dto->ipWhitelist,
                'status' => 'active',
                'expires_at' => $dto->expiresAt,
            ]);

            $this->audit->record(
                action: 'api_key_created',
                subjectType: ApiKey::class,
                subjectId: $apiKey->id,
                newValues: $apiKey->toArray(),
                correlationId: $correlationId,
            );

            $this->logSecurityEvent(
                tenantId: $dto->tenantId,
                eventType: 'api_key_generated',
                severity: 'info',
                correlationId: $correlationId,
            );

            return [
                'key' => $rawKey,
                'key_id' => $keyId,
                'name' => $dto->name,
                'warning' => 'Save this key securely. You will not be able to see it again.',
            ];
        });
    }

    /**
     * Validate API key
     */
    public function validateApiKey(ValidateApiKeyDto $dto, string $correlationId): array|false
    {
        $keyHash = hash('sha256', $dto->rawKey);

        $apiKey = ApiKey::where('key_hash', $keyHash)
            ->where('status', 'active')
            ->first();

        if (!$apiKey || !$apiKey->isActive()) {
            $this->logSecurityEvent(
                tenantId: $apiKey->tenant_id ?? 0,
                eventType: 'api_key_validation_failed',
                severity: 'warning',
                metadata: ['reason' => $apiKey ? 'expired' : 'not_found'],
                correlationId: $correlationId,
            );
            return false;
        }

        // Check IP whitelist
        if ($apiKey->ip_whitelist && !$this->ipMatches($dto->clientIp, $apiKey->ip_whitelist)) {
            $this->logSecurityEvent(
                tenantId: $apiKey->tenant_id,
                eventType: 'api_key_ip_rejected',
                severity: 'warning',
                metadata: ['client_ip' => $dto->clientIp],
                correlationId: $correlationId,
            );
            return false;
        }

        $apiKey->update(['last_used_at' => now()]);

        return [
            'tenant_id' => $apiKey->tenant_id,
            'key_id' => $apiKey->key_id,
            'permissions' => $apiKey->permissions ?? [],
        ];
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(int $tenantId, string $keyId, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($tenantId, $keyId, $correlationId) {
            $apiKey = ApiKey::where('tenant_id', $tenantId)
                ->where('key_id', $keyId)
                ->first();

            if (!$apiKey) {
                return false;
            }

            $apiKey->update(['status' => 'revoked']);

            $this->audit->record(
                action: 'api_key_revoked',
                subjectType: ApiKey::class,
                subjectId: $apiKey->id,
                correlationId: $correlationId,
            );

            $this->logSecurityEvent(
                tenantId: $tenantId,
                eventType: 'api_key_revoked',
                severity: 'info',
                correlationId: $correlationId,
            );

            return true;
        });
    }

    /**
     * Check rate limit with sliding window
     */
    public function checkRateLimit(
        int $tenantId,
        ?int $userId,
        string $operation,
        int $limit,
        int $windowSeconds,
        string $correlationId
    ): bool {
        $key = "rate_limit:{$operation}:{$tenantId}" . ($userId ? ":{$userId}" : '');

        // Check burst ban
        if (Redis::exists("{$key}:burst_ban")) {
            $this->logSecurityEvent(
                tenantId: $tenantId,
                eventType: 'rate_limit_burst_ban',
                severity: 'warning',
                correlationId: $correlationId,
            );
            return false;
        }

        $now = now()->timestamp;
        $windowStart = $now - $windowSeconds;
        $member = (string) $now . ':' . (string) random_int(1000, 9999);

        Redis::zadd($key, [$member => $now]);
        Redis::expire($key, $windowSeconds + 60);
        Redis::zremrangebyscore($key, 0, $windowStart);

        $attempts = Redis::zcard($key);

        if ($attempts > $limit) {
            $this->handleRateLimitExceeded($key, $operation, $correlationId, $tenantId);
            return false;
        }

        return true;
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(
        int $tenantId,
        string $eventType,
        string $severity,
        ?array $metadata = null,
        ?string $correlationId = null
    ): void {
        SecurityEvent::create([
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'correlation_id' => $correlationId ?? Str::uuid()->toString(),
        ]);
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(
        string $key,
        string $operation,
        string $correlationId,
        int $tenantId
    ): void {
        $rejectionKey = "{$key}:rejections";
        $rejectionCount = Redis::incr($rejectionKey);
        Redis::expire($rejectionKey, 60);

        if ($rejectionCount > 3) {
            Redis::setex("{$key}:burst_ban", 300, now()->toDateTimeString());

            $this->logSecurityEvent(
                tenantId: $tenantId,
                eventType: 'rate_limit_burst_protection_activated',
                severity: 'critical',
                metadata: ['operation' => $operation, 'rejection_count' => $rejectionCount],
                correlationId: $correlationId,
            );
        }

        $this->logSecurityEvent(
            tenantId: $tenantId,
            eventType: 'rate_limit_exceeded',
            severity: 'warning',
            metadata: ['operation' => $operation, 'rejection_count' => $rejectionCount],
            correlationId: $correlationId,
        );
    }

    /**
     * Check if IP matches whitelist
     */
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

    /**
     * Check if IP is in CIDR range
     */
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
