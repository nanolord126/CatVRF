<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Repositories;

use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Domain\ValueObjects\Money;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Models\PaymentModel;

/**
 * Реализация PaymentRepository через Eloquent.
 * Отвечает за маппинг Domain ↔ Eloquent.
 */
final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function findById(string $id): ?Payment
    {
        $model = PaymentModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByIdempotencyKey(int $tenantId, IdempotencyKey $key): ?Payment
    {
        $model = PaymentModel::where('tenant_id', $tenantId)
            ->where('idempotency_key', $key->value)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByTenant(
        int $tenantId,
        ?PaymentStatus $status = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $query = PaymentModel::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset);

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->get()->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function save(Payment $payment): void
    {
        PaymentModel::updateOrCreate(
            ['id' => $payment->getId()],
            [
                'id'                  => $payment->getId(),
                'tenant_id'           => $payment->getTenantId(),
                'user_id'             => $payment->getUserId(),
                'amount'              => $payment->getAmount()->amount,
                'currency'            => $payment->getAmount()->currency,
                'idempotency_key'     => $payment->getIdempotencyKey()->value,
                'status'              => $payment->getStatus()->value,
                'provider_payment_id' => $payment->getProviderPaymentId(),
                'payment_url'         => $payment->getPaymentUrl(),
                'correlation_id'      => $payment->getCorrelationId(),
                'metadata'            => $payment->getMetadata(),
                'recurring'           => $payment->isRecurring(),
                'tags'                => [$payment->getStatus()->value],
            ]
        );
    }

    public function countByTenant(int $tenantId, ?PaymentStatus $status = null): int
    {
        $query = PaymentModel::where('tenant_id', $tenantId);
        if ($status !== null) {
            $query->where('status', $status->value);
        }
        return $query->count();
    }

    // --- Mapper ---

    private function toDomain(PaymentModel $model): Payment
    {
        return Payment::reconstitute(
            id:                 (string) $model->id,
            tenantId:           (int) $model->tenant_id,
            userId:             (int) $model->user_id,
            amount:             Money::ofKopeks((int) $model->amount, (string) $model->currency),
            idempotencyKey:     IdempotencyKey::fromString((string) $model->idempotency_key),
            status:             PaymentStatus::from((string) $model->status),
            providerPaymentId:  $model->provider_payment_id,
            paymentUrl:         $model->payment_url,
            correlationId:      $model->correlation_id,
            metadata:           (array) ($model->metadata ?? []),
            recurring:          (bool) $model->recurring,
        );
    }
}
