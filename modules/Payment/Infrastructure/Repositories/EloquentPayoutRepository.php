<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\Payout;
use Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use Modules\Payment\Infrastructure\Models\PayoutModel;

final class EloquentPayoutRepository implements PayoutRepositoryInterface
{
    public function create(array $data): Payout
    {
        $model = PayoutModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): Payout
    {
        $model = PayoutModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?Payout
    {
        $model = PayoutModel::find($id);
        return $model?->toDomain();
    }

    public function findBySellerId(int $sellerId, ?string $status = null): array
    {
        $query = PayoutModel::where('seller_id', $sellerId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByPaymentRecordId(int $paymentRecordId): array
    {
        return PayoutModel::where('payment_record_id', $paymentRecordId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }
}
