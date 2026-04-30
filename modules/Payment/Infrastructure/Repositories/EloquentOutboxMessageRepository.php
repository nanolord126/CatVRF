<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\OutboxMessage;
use Modules\Payment\Domain\Repositories\OutboxMessageRepositoryInterface;
use Modules\Payment\Infrastructure\Models\OutboxMessageModel;

final class EloquentOutboxMessageRepository implements OutboxMessageRepositoryInterface
{
    public function create(array $data): OutboxMessage
    {
        $model = OutboxMessageModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): OutboxMessage
    {
        $model = OutboxMessageModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?OutboxMessage
    {
        $model = OutboxMessageModel::find($id);
        return $model?->toDomain();
    }

    public function findByIdempotencyKey(string $key): ?OutboxMessage
    {
        $model = OutboxMessageModel::where('idempotency_key', $key)->first();
        return $model?->toDomain();
    }

    public function findPendingForDelivery(int $limit = 100): array
    {
        return OutboxMessageModel::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('deliver_after')
                    ->orWhere('deliver_after', '<=', now());
            })
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findFailed(): array
    {
        return OutboxMessageModel::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function deleteDeliveredBefore(\DateTimeImmutable $cutoff): int
    {
        return OutboxMessageModel::where('status', 'delivered')
            ->where('delivered_at', '<', $cutoff)
            ->delete();
    }
}
