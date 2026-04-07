<?php

declare(strict_types=1);

namespace App\Domains\Finances\Infrastructure\Persistence;

use App\Domains\Finances\Domain\Entities\Payout;
use App\Domains\Finances\Domain\Enums\PayoutStatus;
use App\Domains\Finances\Domain\Interfaces\PayoutRepositoryInterface;
use App\Models\PayoutTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация репозитория выплат.
 *
 * Преобразует Eloquent-модели PayoutTransaction в доменные
 * сущности Payout. Все запросы автоматически tenant-scoped
 * через глобальный scope в PayoutTransaction.
 *
 * @package App\Domains\Finances\Infrastructure\Persistence
 */
final class EloquentPayoutRepository implements PayoutRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Payout
    {
        $model = PayoutTransaction::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByCorrelationId(string $correlationId): ?Payout
    {
        $model = PayoutTransaction::where('correlation_id', $correlationId)->first();

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getForTenant(int $tenantId): Collection
    {
        return PayoutTransaction::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (PayoutTransaction $model): Payout => $this->toEntity($model));
    }

    /**
     * {@inheritDoc}
     */
    public function getForTenantInPeriod(
        int $tenantId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?PayoutStatus $status = null,
    ): Collection {
        $query = PayoutTransaction::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to]);

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (PayoutTransaction $model): Payout => $this->toEntity($model));
    }

    /**
     * {@inheritDoc}
     */
    public function store(Payout $payout): void
    {
        PayoutTransaction::create([
            'tenant_id'       => $payout->getTenantId(),
            'wallet_id'       => $payout->getWalletId(),
            'amount'          => $payout->getAmount(),
            'status'          => $payout->getStatus()->value,
            'provider_code'   => $payout->getProviderCode(),
            'correlation_id'  => $payout->getCorrelationId(),
            'metadata'        => $payout->getMetadata(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(int $id, PayoutStatus $status): void
    {
        PayoutTransaction::where('id', $id)->update([
            'status' => $status->value,
        ]);
    }

    /**
     * Преобразовать Eloquent-модель в доменную сущность.
     */
    private function toEntity(PayoutTransaction $model): Payout
    {
        return new Payout(
            id: $model->id,
            tenantId: $model->tenant_id,
            walletId: $model->wallet_id,
            amount: $model->amount,
            status: PayoutStatus::from($model->status),
            providerCode: $model->provider_code,
            correlationId: $model->correlation_id,
            metadata: $model->metadata ?? [],
            createdAt: CarbonImmutable::parse($model->created_at),
        );
    }
}
