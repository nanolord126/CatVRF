<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\ValueObjects\PaymentId;
use Modules\Payment\Infrastructure\Models\PaymentModel;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function save(Payment $payment): void
    {
        $model = $payment->id === 0
            ? PaymentModel::fromDomain($payment)
            : PaymentModel::findOrFail($payment->id);

        $model->fill(PaymentModel::fromDomain($payment)->toArray());
        $model->save();

        if ($payment->id === 0) {
            $model->id = $model->fresh()->id;
        }
    }

    public function findById(PaymentId $id): ?Payment
    {
        $model = PaymentModel::find($id->value);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?Payment
    {
        $model = PaymentModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPayable(string $payableType, int $payableId): array
    {
        return PaymentModel::where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function delete(PaymentId $id): void
    {
        PaymentModel::findOrFail($id->value)->delete();
    }
}
