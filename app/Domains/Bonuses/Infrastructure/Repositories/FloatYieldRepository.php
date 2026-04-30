<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Repositories;

use App\Domains\Bonuses\DTOs\FloatYieldDto;
use App\Domains\Bonuses\Interfaces\FloatYieldRepositoryInterface;
use App\Domains\Bonuses\Models\FloatYieldTransaction;
use Illuminate\Support\Collection;

/**
 * FloatYieldRepository - Infrastructure implementation of float yield repository
 * 
 * Implements the domain interface using Eloquent ORM.
 */
final readonly class FloatYieldRepository implements FloatYieldRepositoryInterface
{
    public function create(FloatYieldDto $dto): FloatYieldTransaction
    {
        return FloatYieldTransaction::create([
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'total_float' => $dto->totalFloat,
            'platform_yield' => $dto->platformYield,
            'user_yield' => $dto->userYield,
            'yield_rate' => $dto->yieldRate,
            'yield_date' => $dto->yieldDate,
            'correlation_id' => $dto->correlationId,
        ]);
    }

    public function findById(int $id): ?FloatYieldTransaction
    {
        return FloatYieldTransaction::find($id);
    }

    public function findByCorrelationId(string $correlationId): ?FloatYieldTransaction
    {
        return FloatYieldTransaction::where('correlation_id', $correlationId)->first();
    }

    public function hasYieldForDate(int $userId, int $tenantId, string $date): bool
    {
        return FloatYieldTransaction::hasYieldForDate($userId, $tenantId, $date);
    }

    public function getTransactionsForUser(int $userId, int $tenantId, ?int $limit = null): Collection
    {
        $query = FloatYieldTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('yield_date');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getTransactionsForDateRange(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): Collection {
        return FloatYieldTransaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->get();
    }

    public function getTotalUserYield(int $userId, int $tenantId, string $startDate, string $endDate): float
    {
        return FloatYieldTransaction::getTotalUserYield($userId, $tenantId, $startDate, $endDate);
    }

    public function getTotalPlatformYield(?int $tenantId, string $startDate, string $endDate): float
    {
        return FloatYieldTransaction::getTotalPlatformYield($tenantId, $startDate, $endDate);
    }

    public function getPlatformFloatForYield(?int $tenantId = null): float
    {
        return FloatYieldTransaction::getPlatformFloatForYield($tenantId);
    }

    public function getYieldRates(): array
    {
        return FloatYieldTransaction::calculateYieldRates();
    }

    public function getTenantStatistics(int $tenantId, string $startDate, string $endDate): array
    {
        $totalPlatformYield = $this->getTotalPlatformYield($tenantId, $startDate, $endDate);
        $totalTransactions = FloatYieldTransaction::where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->count();

        return [
            'total_platform_yield' => $totalPlatformYield,
            'total_transactions' => $totalTransactions,
        ];
    }

    public function getDailyYieldSummary(?int $tenantId, string $date): array
    {
        $query = FloatYieldTransaction::where('yield_date', $date);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $transactions = $query->get();

        return [
            'date' => $date,
            'tenant_id' => $tenantId,
            'total_transactions' => $transactions->count(),
            'total_float' => $transactions->sum('total_float'),
            'total_platform_yield' => $transactions->sum('platform_yield'),
            'total_user_yield' => $transactions->sum('user_yield'),
            'total_yield' => $transactions->sum(fn ($tx) => $tx->getTotalYield()),
        ];
    }

    public function update(FloatYieldTransaction $transaction): bool
    {
        return $transaction->save();
    }
}
