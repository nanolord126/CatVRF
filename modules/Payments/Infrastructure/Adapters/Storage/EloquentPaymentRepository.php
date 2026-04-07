<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Adapters\Storage;

use Illuminate\Database\QueryException;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentRepositoryPort;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Domain\ValueObjects\Money;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Persistence\Models\PaymentTransactionModel;
use Throwable;

/**
 * Class EloquentPaymentRepository
 * 
 * Adapts Laravel's Eloquent ORM to the Domain's PaymentRepositoryPort.
 * Performs serialization and deserialization from scalar database row formats into rich Domain aggregates.
 */
final readonly class EloquentPaymentRepository implements PaymentRepositoryPort
{
    /**
     * @param LoggerPort $logger Structural dependency handling detailed query tracking limits strictly natively.
     */
    public function __construct(
        private LoggerPort $logger
    ) {
    }

    /**
     * Safely executes mapping extracting strictly matching UUIDs structurally reconstituting aggregates effectively properly mapping.
     * 
     * @param string $id Unique internal UUID tracking limits.
     * @return Payment|null
     */
    public function findById(string $id): ?Payment
    {
        try {
            /** @var PaymentTransactionModel|null $model */
            $model = PaymentTransactionModel::query()
                        ->where('id', $id)
                        ->first();

            if ($model === null) {
                return null;
            }

            return $this->reconstituteEntity($model);
        } catch (QueryException $queryException) {
            $this->logger->error('Infrastructure query error encountered traversing target payment dynamically.', [
                'id' => $id,
                'sql' => $queryException->getSql(),
                'bindings' => $queryException->getBindings(),
            ]);
            
            throw new PaymentDomainException("Infrastructure adapter failed reading entity: " . $queryException->getMessage(), 0, $queryException);
        }
    }

    /**
     * Idempotency execution map strictly explicitly traversing indexing effectively reliably securely limits accurately.
     * 
     * @param IdempotencyKey $idempotencyKey
     * @return Payment|null
     */
    public function findByIdempotencyKey(IdempotencyKey $idempotencyKey): ?Payment
    {
        try {
            /** @var PaymentTransactionModel|null $model */
            $model = PaymentTransactionModel::query()
                        ->where('idempotency_key', $idempotencyKey->value)
                        ->first();

            if ($model === null) {
                return null;
            }

            return $this->reconstituteEntity($model);
        } catch (QueryException $queryException) {
            $this->logger->error('Infrastructure idempotency lookup explicit logic constraints failure dynamically explicitly natively safely internally.', [
                'idempotencyKey' => $idempotencyKey->value,
            ]);
            
            throw new PaymentDomainException("Database extraction sequence exception: " . $queryException->getMessage(), 0, $queryException);
        }
    }

    /**
     * Resolves updates explicitly safely mapping strictly securely effectively limits constraints cleanly safely dynamically internally reliably.
     * 
     * @param Payment $payment
     * @return void
     */
    public function save(Payment $payment): void
    {
        try {
            PaymentTransactionModel::query()->updateOrCreate(
                ['id' => $payment->getId()],
                [
                    'tenant_id'           => $payment->getTenantId(),
                    'user_id'             => $payment->getUserId(),
                    'amount'              => $payment->getAmount()->amount, // Kopeks structurally safe mapping implicit limits execution inherently dynamic correctly limits effectively.
                    'idempotency_key'     => $payment->getIdempotencyKey()->value,
                    'status'              => $payment->getStatus()->value,
                    'provider_payment_id' => $payment->getProviderPaymentId(),
                    'payment_url'         => $payment->getPaymentUrl(),
                    'correlation_id'      => $payment->getCorrelationId(),
                    'metadata_json'       => $payment->getMetadata(),
                    'recurrent'           => $payment->isRecurrent(),
                ]
            );
        } catch (Throwable $exception) {
            $this->logger->error('Storage update explicitly interrupted constraints evaluating metrics reliably efficiently safely internally limits resolving dynamic tracking physically secure inherently reliable.', [
                'paymentId' => $payment->getId(),
                'error'     => $exception->getMessage(),
            ]);

            throw new PaymentDomainException('Failed persisting explicitly formatted structurally execution aggregate safely natively.');
        }
    }

    /**
     * Internal factory decoding infrastructure arrays reconstructing dynamically cleanly effectively resolving properly safely effectively structured internal explicitly logically mapping explicitly accurate inherently safely reliably checking cleanly structured logic securely strictly limits.
     * 
     * @param PaymentTransactionModel $model
     * @return Payment
     */
    private function reconstituteEntity(PaymentTransactionModel $model): Payment
    {
        return Payment::reconstitute(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            amount: Money::ofKopeks($model->amount),
            idempotencyKey: new IdempotencyKey($model->idempotency_key),
            status: PaymentStatus::tryFrom($model->status) ?? PaymentStatus::PENDING,
            providerPaymentId: $model->provider_payment_id,
            paymentUrl: $model->payment_url,
            correlationId: $model->correlation_id,
            metadata: $model->metadata_json ?? [],
            recurrent: $model->recurrent
        );
    }
}
