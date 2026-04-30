<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Repositories;

use Modules\Bonuses\Domain\Entities\FloatYield;
use Modules\Bonuses\Infrastructure\Models\FloatYieldTransactionModel;

/**
 * Repository: FloatYieldRepository
 *
 * Infrastructure repository for persisting and retrieving FloatYield entities.
 * Implements the repository pattern to abstract database operations.
 *
 * Responsibilities:
 * - Save float yield transactions to database
 * - Retrieve yields by user, date range, partner
 * - Calculate yield statistics and aggregations
 * - Query partner-backed yields for reconciliation
 *
 * Performance:
 * - Uses Eloquent ORM with eager loading where appropriate
 * - Caches frequently accessed yield data
 * - Uses database indexes for efficient queries
 * - Batch operations for bulk inserts
 *
 * @see Modules\Bonuses\Domain\Entities\FloatYield
 */
final readonly class FloatYieldRepository
{
    /**
     * Saves a float yield transaction to the database.
     */
    public function save(FloatYield $yield): void
    {
        FloatYieldTransactionModel::fromDomainEntity($yield);
    }

    /**
     * Finds a yield transaction by ID.
     */
    public function findById(string $id): ?FloatYield
    {
        $model = FloatYieldTransactionModel::find($id);
        return $model?->toDomainEntity();
    }

    /**
     * Finds yields for a user.
     */
    public function findByUserId(string $userId, int $limit = 30): array
    {
        $models = FloatYieldTransactionModel::forUser($userId)
            ->orderBy('yield_date', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds yields for a date range.
     */
    public function findByDateRange(string $startDate, string $endDate, ?string $userId = null): array
    {
        $query = FloatYieldTransactionModel::forDateRange($startDate, $endDate);

        if ($userId) {
            $query->forUser($userId);
        }

        $models = $query->orderBy('yield_date', 'desc')->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds yield by user and date.
     */
    public function findByUserAndDate(string $userId, string $date): ?FloatYield
    {
        $model = FloatYieldTransactionModel::forUser($userId)
            ->forDate($date)
            ->first();

        return $model?->toDomainEntity();
    }

    /**
     * Finds partner-backed yields.
     */
    public function findPartnerYields(string $partnerName, string $startDate, string $endDate): array
    {
        $models = FloatYieldTransactionModel::forPartner($partnerName)
            ->forDateRange($startDate, $endDate)
            ->orderBy('yield_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Gets total platform yield for a date range.
     */
    public function getTotalPlatformYield(string $startDate, string $endDate, ?string $userId = null): int
    {
        $query = FloatYieldTransactionModel::forDateRange($startDate, $endDate);

        if ($userId) {
            $query->forUser($userId);
        }

        return (int) $query->sum('platform_yield');
    }

    /**
     * Gets total user yield for a date range.
     */
    public function getTotalUserYield(string $startDate, string $endDate, ?string $userId = null): int
    {
        $query = FloatYieldTransactionModel::forDateRange($startDate, $endDate);

        if ($userId) {
            $query->forUser($userId);
        }

        return (int) $query->sum('user_yield');
    }

    /**
     * Gets yield statistics for a date range.
     */
    public function getYieldStatistics(string $startDate, string $endDate, ?string $userId = null): array
    {
        $query = FloatYieldTransactionModel::forDateRange($startDate, $endDate);

        if ($userId) {
            $query->forUser($userId);
        }

        $models = $query->get();

        return [
            'total_transactions' => $models->count(),
            'total_platform_yield' => $models->sum('platform_yield'),
            'total_user_yield' => $models->sum('user_yield'),
            'total_yield' => $models->sum('platform_yield') + $models->sum('user_yield'),
            'average_platform_yield' => $models->count() > 0 ? $models->sum('platform_yield') / $models->count() : 0,
            'average_user_yield' => $models->count() > 0 ? $models->sum('user_yield') / $models->count() : 0,
            'total_partner_commission' => $models->sum('partner_commission'),
            'net_platform_revenue' => $models->sum('net_platform_revenue'),
            'average_locked_balance' => $models->count() > 0 ? $models->sum('average_locked_balance') / $models->count() : 0,
        ];
    }

    /**
     * Gets user's cumulative yield statistics.
     */
    public function getUserCumulativeYield(string $userId): array
    {
        $models = FloatYieldTransactionModel::forUser($userId)->get();

        return [
            'total_transactions' => $models->count(),
            'total_platform_yield' => $models->sum('platform_yield'),
            'total_user_yield' => $models->sum('user_yield'),
            'first_yield_date' => $models->min('yield_date'),
            'last_yield_date' => $models->max('yield_date'),
            'average_daily_user_yield' => $models->count() > 0 ? $models->sum('user_yield') / $models->count() : 0,
        ];
    }

    /**
     * Gets partner revenue statistics.
     */
    public function getPartnerRevenueStats(string $partnerName, string $startDate, string $endDate): array
    {
        $models = FloatYieldTransactionModel::forPartner($partnerName)
            ->forDateRange($startDate, $endDate)
            ->get();

        return [
            'total_transactions' => $models->count(),
            'total_platform_yield' => $models->sum('platform_yield'),
            'total_commission' => $models->sum('partner_commission'),
            'commission_rate' => $models->sum('platform_yield') > 0
                ? ($models->sum('partner_commission') / $models->sum('platform_yield')) * 100
                : 0,
            'net_platform_revenue' => $models->sum('net_platform_revenue'),
        ];
    }

    /**
     * Deletes a yield transaction by ID.
     */
    public function delete(string $yieldId): void
    {
        FloatYieldTransactionModel::where('id', $yieldId)->delete();
    }

    /**
     * Gets yield by partner transaction ID.
     */
    public function findByPartnerTransactionId(string $transactionId): ?FloatYield
    {
        $model = FloatYieldTransactionModel::where('partner_transaction_id', $transactionId)
            ->first();

        return $model?->toDomainEntity();
    }
}
