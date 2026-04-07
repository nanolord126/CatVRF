<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\FraudDetection\Domain\Entities\FraudAttempt;
use Modules\FraudDetection\Domain\Repositories\FraudAttemptRepositoryInterface;

final class EloquentFraudAttemptRepository implements FraudAttemptRepositoryInterface
{
    private const CACHE_TAG = 'fraud_attempts';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly FraudAttempt $model,
        private readonly CacheRepository $cache
    ) {
    }

    public function create(array $data): FraudAttempt
    {
        $attempt = $this->model->newQuery()->create($data);
        $this->cache->tags(self::CACHE_TAG)->flush();
        return $attempt;
    }

    public function findById(int $id): ?FraudAttempt
    {
        $cacheKey = "fraud_attempt_{$id}";
        return $this->cache->tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->model->newQuery()->find($id);
        });
    }

    public function findByTransactionId(string $transactionId): ?FraudAttempt
    {
        $cacheKey = "fraud_attempt_txn_{$transactionId}";
        return $this->cache->tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($transactionId) {
            return $this->model->newQuery()->where('transaction_id', $transactionId)->first();
        });
    }

    public function getRecentForUser(int $userId, int $limit = 10): Collection
    {
        $cacheKey = "fraud_attempts_user_{$userId}_{$limit}";
        return $this->cache->tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            return $this->model->newQuery()
                ->where('user_id', $userId)
                ->latest()
                ->limit($limit)
                ->get();
        });
    }
}
