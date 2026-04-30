<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Payment\Domain\Entities\EscrowHold;
use Modules\Payment\Domain\Repositories\EscrowHoldRepositoryInterface;
use Modules\Payment\Infrastructure\Models\EscrowHoldModel;

final class EloquentEscrowHoldRepository implements EscrowHoldRepositoryInterface
{
    public function create(array $data): EscrowHold
    {
        $model = EscrowHoldModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): EscrowHold
    {
        $model = EscrowHoldModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findByUuid(string $uuid): ?EscrowHold
    {
        $model = EscrowHoldModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findActiveByWallet(int $walletId): array
    {
        return EscrowHoldModel::where('wallet_id', $walletId)
            ->whereIn('status', ['held', 'partially_released'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findExpired(): array
    {
        return EscrowHoldModel::whereIn('status', ['held', 'partially_released'])
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<', now())
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }
}
