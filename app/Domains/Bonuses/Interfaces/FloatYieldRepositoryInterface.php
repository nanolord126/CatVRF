<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Interfaces;

use App\Domains\Bonuses\DTOs\FloatYieldDto;
use App\Domains\Bonuses\Models\FloatYieldTransaction;
use Illuminate\Support\Collection;

/**
 * FloatYieldRepositoryInterface - Repository interface for float yield transactions
 * 
 * Defines contract for yield operations following DDD principles.
 * Implementation in Infrastructure layer.
 */
interface FloatYieldRepositoryInterface
{
    /**
     * Create a new yield transaction
     */
    public function create(FloatYieldDto $dto): FloatYieldTransaction;

    /**
     * Find transaction by ID
     */
    public function findById(int $id): ?FloatYieldTransaction;

    /**
     * Find transaction by correlation ID
     */
    public function findByCorrelationId(string $correlationId): ?FloatYieldTransaction;

    /**
     * Check if yield exists for user on date
     */
    public function hasYieldForDate(int $userId, int $tenantId, string $date): bool;

    /**
     * Get transactions for user
     */
    public function getTransactionsForUser(int $userId, int $tenantId, ?int $limit = null): Collection;

    /**
     * Get transactions for date range
     */
    public function getTransactionsForDateRange(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): Collection;

    /**
     * Get total user yield for date range
     */
    public function getTotalUserYield(int $userId, int $tenantId, string $startDate, string $endDate): float;

    /**
     * Get total platform yield for date range
     */
    public function getTotalPlatformYield(?int $tenantId, string $startDate, string $endDate): float;

    /**
     * Get platform float for yield calculation
     */
    public function getPlatformFloatForYield(?int $tenantId = null): float;

    /**
     * Get yield rates
     */
    public function getYieldRates(): array;

    /**
     * Get yield statistics for tenant
     */
    public function getTenantStatistics(int $tenantId, string $startDate, string $endDate): array;

    /**
     * Get daily yield summary
     */
    public function getDailyYieldSummary(?int $tenantId, string $date): array;

    /**
     * Update transaction
     */
    public function update(FloatYieldTransaction $transaction): bool;
}
