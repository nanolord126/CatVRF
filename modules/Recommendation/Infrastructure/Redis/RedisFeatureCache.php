<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\Redis;

use Illuminate\Support\Facades\Redis;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;

final readonly class RedisFeatureCache
{
    private const TTL_SECONDS = 3600;
    private const KEY_PREFIX = 'rec:feature:';

    public function __construct() {}

    public function getUserFeatures(int $tenantId, int $userId): ?FeatureVector
    {
        $key = $this->getUserKey($tenantId, $userId);
        $cached = Redis::get($key);

        if ($cached === null) {
            return null;
        }

        $data = json_decode($cached, true);

        if (!$data) {
            return null;
        }

        return FeatureVector::fromMixed(
            $data['dense'] ?? [],
            $data['sparse'] ?? [],
            $data['version'] ?? 1,
        );
    }

    public function setUserFeatures(int $tenantId, int $userId, FeatureVector $features): void
    {
        $key = $this->getUserKey($tenantId, $userId);
        $value = json_encode($features->toArray());

        Redis::setex($key, self::TTL_SECONDS, $value);
    }

    public function getItemFeatures(int $tenantId, int $itemId): ?FeatureVector
    {
        $key = $this->getItemKey($tenantId, $itemId);
        $cached = Redis::get($key);

        if ($cached === null) {
            return null;
        }

        $data = json_decode($cached, true);

        if (!$data) {
            return null;
        }

        return FeatureVector::fromMixed(
            $data['dense'] ?? [],
            $data['sparse'] ?? [],
            $data['version'] ?? 1,
        );
    }

    public function setItemFeatures(int $tenantId, int $itemId, FeatureVector $features): void
    {
        $key = $this->getItemKey($tenantId, $itemId);
        $value = json_encode($features->toArray());

        Redis::setex($key, self::TTL_SECONDS, $value);
    }

    public function getSellerFeatures(int $tenantId, int $sellerId): ?FeatureVector
    {
        $key = $this->getSellerKey($tenantId, $sellerId);
        $cached = Redis::get($key);

        if ($cached === null) {
            return null;
        }

        $data = json_decode($cached, true);

        if (!$data) {
            return null;
        }

        return FeatureVector::fromMixed(
            $data['dense'] ?? [],
            $data['sparse'] ?? [],
            $data['version'] ?? 1,
        );
    }

    public function setSellerFeatures(int $tenantId, int $sellerId, FeatureVector $features): void
    {
        $key = $this->getSellerKey($tenantId, $sellerId);
        $value = json_encode($features->toArray());

        Redis::setex($key, self::TTL_SECONDS, $value);
    }

    public function invalidateUser(int $tenantId, int $userId): void
    {
        $key = $this->getUserKey($tenantId, $userId);
        Redis::del($key);
    }

    public function invalidateItem(int $tenantId, int $itemId): void
    {
        $key = $this->getItemKey($tenantId, $itemId);
        Redis::del($key);
    }

    public function invalidateSeller(int $tenantId, int $sellerId): void
    {
        $key = $this->getSellerKey($tenantId, $sellerId);
        Redis::del($key);
    }

    public function invalidateTenant(int $tenantId): void
    {
        $pattern = $this->getTenantPattern($tenantId);
        $keys = Redis::keys($pattern);

        if (!empty($keys)) {
            Redis::del($keys);
        }
    }

    public function batchGetUserFeatures(int $tenantId, array $userIds): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $features = $this->getUserFeatures($tenantId, $userId);
            $results[$userId] = $features;
        }

        return $results;
    }

    public function batchSetUserFeatures(int $tenantId, array $userFeatures): void
    {
        foreach ($userFeatures as $userId => $features) {
            if ($features instanceof FeatureVector) {
                $this->setUserFeatures($tenantId, $userId, $features);
            }
        }
    }

    public function getCacheStats(int $tenantId): array
    {
        $pattern = $this->getTenantPattern($tenantId);
        $keys = Redis::keys($pattern);

        $userKeys = array_filter($keys, fn($key) => str_contains($key, ':user:'));
        $itemKeys = array_filter($keys, fn($key) => str_contains($key, ':item:'));
        $sellerKeys = array_filter($keys, fn($key) => str_contains($key, ':seller:'));

        return [
            'total_keys' => count($keys),
            'user_keys' => count($userKeys),
            'item_keys' => count($itemKeys),
            'seller_keys' => count($sellerKeys),
            'tenant_id' => $tenantId,
        ];
    }

    public function warmupCache(int $tenantId, array $userIds, array $itemIds, array $sellerIds): void
    {
        $batchSize = 100;

        foreach (array_chunk($userIds, $batchSize) as $chunk) {
            foreach ($chunk as $userId) {
                if (!$this->getUserFeatures($tenantId, $userId)) {
                    Redis::setex(
                        $this->getUserKey($tenantId, $userId),
                        self::TTL_SECONDS,
                        json_encode(['dense' => [], 'sparse' => [], 'version' => 1])
                    );
                }
            }
        }

        foreach (array_chunk($itemIds, $batchSize) as $chunk) {
            foreach ($chunk as $itemId) {
                if (!$this->getItemFeatures($tenantId, $itemId)) {
                    Redis::setex(
                        $this->getItemKey($tenantId, $itemId),
                        self::TTL_SECONDS,
                        json_encode(['dense' => [], 'sparse' => [], 'version' => 1])
                    );
                }
            }
        }

        foreach (array_chunk($sellerIds, $batchSize) as $chunk) {
            foreach ($chunk as $sellerId) {
                if (!$this->getSellerFeatures($tenantId, $sellerId)) {
                    Redis::setex(
                        $this->getSellerKey($tenantId, $sellerId),
                        self::TTL_SECONDS,
                        json_encode(['dense' => [0.5, 0.5, 0.5, 0.5, 0.5], 'sparse' => [], 'version' => 1])
                    );
                }
            }
        }
    }

    private function getUserKey(int $tenantId, int $userId): string
    {
        return self::KEY_PREFIX . 'user:' . $tenantId . ':' . $userId;
    }

    private function getItemKey(int $tenantId, int $itemId): string
    {
        return self::KEY_PREFIX . 'item:' . $tenantId . ':' . $itemId;
    }

    private function getSellerKey(int $tenantId, int $sellerId): string
    {
        return self::KEY_PREFIX . 'seller:' . $tenantId . ':' . $sellerId;
    }

    private function getTenantPattern(int $tenantId): string
    {
        return self::KEY_PREFIX . '*' . $tenantId . ':*';
    }
}
