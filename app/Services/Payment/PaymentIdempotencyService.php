<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use RuntimeException;

/**
 * Redis-based Idempotency Service for Payment Operations
 * 
 * Provides distributed locking and idempotency guarantees for payment operations
 * to prevent double-charging and race conditions across multiple servers.
 * 
 * TTL: 24 hours for idempotency records
 * Lock TTL: 5 minutes for operation locks
 */
final readonly class PaymentIdempotencyService
{
    private const string LOCK_PREFIX = 'payment:lock:';
    private const string IDEMPOTENCY_PREFIX = 'payment:idempotency:';
    private const int DEFAULT_TTL_SECONDS = 86400; // 24 hours
    private const int LOCK_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LogManager $logger,
    ) {}

    /**
     * Check if operation with idempotency key was already processed
     * 
     * @param string $operation Operation type (payment_init, capture, refund)
     * @param string $idempotencyKey Unique key from client
     * @param array $payload Request payload for hash comparison
     * @param int $tenantId Tenant for scoping
     * @return array|null Cached response if exists, null otherwise
     * @throws RuntimeException If payload hash mismatch (possible replay attack)
     */
    public function check(
        string $operation,
        string $idempotencyKey,
        array $payload,
        int $tenantId
    ): ?array {
        $key = $this->buildKey($operation, $idempotencyKey, $tenantId);
        $payloadHash = $this->generateHash($payload);

        $cached = $this->cache->get($key);

        if ($cached === null) {
            return null;
        }

        // Verify payload hash to prevent replay attacks
        if (!isset($cached['payload_hash']) || $cached['payload_hash'] !== $payloadHash) {
            $this->logger->channel('fraud_alert')->critical('Idempotency payload mismatch detected', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
                'expected_hash' => $cached['payload_hash'] ?? 'not_set',
                'actual_hash' => $payloadHash,
                'correlation_id' => $payload['correlation_id'] ?? null,
            ]);

            throw new RuntimeException(
                'Payload for this idempotency key does not match previous request. Possible replay attack.'
            );
        }

        $this->logger->channel('audit')->info('Idempotency cache hit', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
            'tenant_id' => $tenantId,
            'correlation_id' => $payload['correlation_id'] ?? null,
            'cached_at' => $cached['cached_at'] ?? null,
        ]);

        return $cached['response'];
    }

    /**
     * Store operation result for idempotency
     * 
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key from client
     * @param array $payload Request payload
     * @param array $response Operation result to cache
     * @param int $tenantId Tenant for scoping
     * @param int $ttlSeconds TTL in seconds (default 24h)
     * @return bool
     */
    public function store(
        string $operation,
        string $idempotencyKey,
        array $payload,
        array $response,
        int $tenantId,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS
    ): bool {
        $key = $this->buildKey($operation, $idempotencyKey, $tenantId);
        $payloadHash = $this->generateHash($payload);

        $data = [
            'payload_hash' => $payloadHash,
            'response' => $response,
            'cached_at' => now()->toIso8601String(),
            'operation' => $operation,
            'tenant_id' => $tenantId,
        ];

        $result = $this->cache->put($key, $data, $ttlSeconds);

        if ($result) {
            $this->logger->channel('audit')->info('Idempotency record stored', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
                'ttl_seconds' => $ttlSeconds,
                'correlation_id' => $payload['correlation_id'] ?? null,
            ]);
        } else {
            $this->logger->channel('audit')->error('Failed to store idempotency record', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
                'correlation_id' => $payload['correlation_id'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Acquire distributed lock for operation
     * 
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key
     * @param int $tenantId Tenant for scoping
     * @param int $ttlSeconds Lock TTL (default 5 minutes)
     * @return string Lock token if acquired, null if already locked
     */
    public function acquireLock(
        string $operation,
        string $idempotencyKey,
        int $tenantId,
        int $ttlSeconds = self::LOCK_TTL_SECONDS
    ): ?string {
        $lockKey = $this->buildLockKey($operation, $idempotencyKey, $tenantId);
        $token = Str::random(32);

        // Redis SETNX with expiration
        $acquired = $this->cache->getRedis()->set(
            $lockKey,
            $token,
            'EX',
            $ttlSeconds,
            'NX'
        );

        if ($acquired) {
            $this->logger->channel('audit')->info('Payment lock acquired', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
                'lock_token' => $token,
                'ttl_seconds' => $ttlSeconds,
            ]);

            return $token;
        }

        $this->logger->channel('audit')->warning('Payment lock already held', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
            'tenant_id' => $tenantId,
        ]);

        return null;
    }

    /**
     * Release distributed lock
     * 
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key
     * @param int $tenantId Tenant for scoping
     * @param string $token Lock token from acquireLock
     * @return bool True if released, false if token mismatch
     */
    public function releaseLock(
        string $operation,
        string $idempotencyKey,
        int $tenantId,
        string $token
    ): bool {
        $lockKey = $this->buildLockKey($operation, $idempotencyKey, $tenantId);

        // Lua script for atomic check-and-delete
        $script = <<<'LUA'
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        LUA;

        $result = $this->cache->getRedis()->eval(
            $script,
            1,
            $lockKey,
            $token
        );

        if ($result) {
            $this->logger->channel('audit')->info('Payment lock released', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
            ]);

            return true;
        }

        $this->logger->channel('audit')->warning('Payment lock release failed (token mismatch)', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
            'tenant_id' => $tenantId,
        ]);

        return false;
    }

    /**
     * Invalidate idempotency record (for testing or admin)
     * 
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key
     * @param int $tenantId Tenant for scoping
     * @return bool
     */
    public function invalidate(
        string $operation,
        string $idempotencyKey,
        int $tenantId
    ): bool {
        $key = $this->buildKey($operation, $idempotencyKey, $tenantId);
        $result = $this->cache->forget($key);

        $this->logger->channel('audit')->info('Idempotency record invalidated', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
            'tenant_id' => $tenantId,
        ]);

        return $result;
    }

    /**
     * Legacy method for backward compatibility
     */
    public function checkAndRecord(string $idempotencyKey, array $payload, int $tenantId): ?array
    {
        return $this->check('payment', $idempotencyKey, $payload, $tenantId);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function record(string $idempotencyKey, array $payload, array $response, int $tenantId): void
    {
        $this->store('payment', $idempotencyKey, $payload, $response, $tenantId);
    }

    /**
     * Generate SHA-256 hash of payload
     * 
     * @param array $payload
     * @return string
     */
    private function generateHash(array $payload): string
    {
        // Exclude service fields that may differ between requests
        $filtered = collect($payload)
            ->except(['correlation_id', 'timestamp', 'X-Correlation-ID', 'X-Request-ID', '_token'])
            ->sortKeys()
            ->all();

        $jsonPayload = json_encode($filtered, JSON_THROW_ON_ERROR | JSON_SORT_KEYS);
        return hash('sha256', $jsonPayload);
    }

    /**
     * Build Redis key for idempotency record
     */
    private function buildKey(string $operation, string $idempotencyKey, int $tenantId): string
    {
        return sprintf(
            '%s%s:%s:%d',
            self::IDEMPOTENCY_PREFIX,
            $operation,
            $idempotencyKey,
            $tenantId
        );
    }

    /**
     * Build Redis key for distributed lock
     */
    private function buildLockKey(string $operation, string $idempotencyKey, int $tenantId): string
    {
        return sprintf(
            '%s%s:%s:%d',
            self::LOCK_PREFIX,
            $operation,
            $idempotencyKey,
            $tenantId
        );
    }
}
