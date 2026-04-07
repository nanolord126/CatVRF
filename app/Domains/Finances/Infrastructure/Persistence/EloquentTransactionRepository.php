<?php

declare(strict_types=1);

namespace App\Domains\Finances\Infrastructure\Persistence;

use App\Domains\Finances\Domain\Entities\FinancialTransaction;
use App\Domains\Finances\Domain\Enums\TransactionType;
use App\Domains\Finances\Domain\Interfaces\TransactionRepositoryInterface;
use App\Models\BalanceTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация репозитория финансовых транзакций.
 *
 * Преобразует Eloquent-модели BalanceTransaction в доменные сущности
 * FinancialTransaction. Все запросы автоматически tenant-scoped.
 *
 * @package App\Domains\Finances\Infrastructure\Persistence
 */
final class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?FinancialTransaction
    {
        $model = BalanceTransaction::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByCorrelationId(string $correlationId): ?FinancialTransaction
    {
        $model = BalanceTransaction::where('correlation_id', $correlationId)->first();

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getForTenant(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return BalanceTransaction::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (BalanceTransaction $model): FinancialTransaction => $this->toEntity($model));
    }

    /**
     * {@inheritDoc}
     */
    public function getForTenantByType(
        int $tenantId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?TransactionType $type = null,
    ): Collection {
        $query = BalanceTransaction::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to]);

        if ($type !== null) {
            $query->where('type', $type->value);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (BalanceTransaction $model): FinancialTransaction => $this->toEntity($model));
    }

    /**
     * {@inheritDoc}
     */
    public function sumForTenantByType(
        int $tenantId,
        TransactionType $type,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): int {
        return (int) BalanceTransaction::where('tenant_id', $tenantId)
            ->where('type', $type->value)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function store(FinancialTransaction $transaction): void
    {
        BalanceTransaction::create([
            'wallet_id'      => $transaction->getWalletId(),
            'tenant_id'      => $transaction->getTenantId(),
            'type'           => $transaction->getType(),
            'amount'         => $transaction->getAmount(),
            'correlation_id' => $transaction->getCorrelationId(),
            'meta'           => $transaction->getMetadata(),
        ]);
    }

    /**
     * Преобразовать Eloquent-модель в доменную сущность.
     */
    private function toEntity(BalanceTransaction $model): FinancialTransaction
    {
        return new FinancialTransaction(
            id: $model->id,
            tenantId: $model->tenant_id ?? 0,
            businessGroupId: $model->business_group_id ?? null,
            walletId: $model->wallet_id,
            type: $model->type,
            amount: (int) $model->amount,
            metadata: $model->meta ?? [],
            createdAt: CarbonImmutable::parse($model->created_at),
            correlationId: $model->correlation_id ?? '',
        );
    }
}
