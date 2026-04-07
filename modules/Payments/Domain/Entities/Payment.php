<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Entities;

use Modules\Payments\Domain\Events\PaymentCaptured;
use Modules\Payments\Domain\Events\PaymentFailed;
use Modules\Payments\Domain\Events\PaymentInitiated;
use Modules\Payments\Domain\Events\RefundCreated;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Domain\ValueObjects\Money;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;

final class Payment
{
    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly Money $amount,
        private readonly IdempotencyKey $idempotencyKey,
        private PaymentStatus $status,
        private ?string $providerPaymentId = null,
        private ?string $paymentUrl = null,
        private readonly ?string $correlationId = null,
        private readonly array $metadata = [],
        private readonly bool $recurrent = false,
    ) {}

    public static function initiate(
        string $id,
        int $tenantId,
        int $userId,
        Money $amount,
        IdempotencyKey $idempotencyKey,
        string $correlationId,
        array $metadata = [],
        bool $recurrent = false,
    ): self {
        $payment = new self(
            id: $id,
            tenantId: $tenantId,
            userId: $userId,
            amount: $amount,
            idempotencyKey: $idempotencyKey,
            status: PaymentStatus::PENDING,
            correlationId: $correlationId,
            metadata: $metadata,
            recurrent: $recurrent,
        );

        $payment->domainEvents[] = new PaymentInitiated(
            paymentId: $id,
            tenantId: $tenantId,
            userId: $userId,
            amountKopeks: $amount->toKopeks(),
            correlationId: $correlationId,
        );

        return $payment;
    }

    public static function reconstitute(
        string $id,
        int $tenantId,
        int $userId,
        Money $amount,
        IdempotencyKey $idempotencyKey,
        PaymentStatus $status,
        ?string $providerPaymentId,
        ?string $paymentUrl,
        ?string $correlationId,
        array $metadata,
        bool $recurrent,
    ): self {
        return new self($id, $tenantId, $userId, $amount, $idempotencyKey, $status, $providerPaymentId, $paymentUrl, $correlationId, $metadata, $recurrent);
    }

    public function capture(string $providerPaymentId, string $paymentUrl): void
    {
        if (!$this->status->canBeCaptured()) {
            throw new PaymentDomainException("Cannot capture payment {$this->id} in status {$this->status->value}");
        }

        $this->status = PaymentStatus::CAPTURED;
        $this->providerPaymentId = $providerPaymentId;
        $this->paymentUrl = $paymentUrl;

        $this->domainEvents[] = new PaymentCaptured(
            paymentId: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            amountKopeks: $this->amount->toKopeks(),
            providerPaymentId: $providerPaymentId,
            correlationId: $this->correlationId ?? '',
        );
    }

    public function refund(string $refundId, Money $refundAmount, string $reason): void
    {
        if (!$this->status->canBeRefunded()) {
            throw new PaymentDomainException("Cannot refund payment {$this->id} in status {$this->status->value}");
        }

        $this->status = PaymentStatus::REFUNDED;

        $this->domainEvents[] = new RefundCreated(
            refundId: $refundId,
            paymentId: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            amount: $refundAmount,
            correlationId: $this->correlationId ?? '',
        );
    }

    public function markAsFailed(string $reason = ''): void
    {
        $this->status = PaymentStatus::FAILED;
        
        $this->domainEvents[] = new PaymentFailed(
            paymentId: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            amount: $this->amount,
            reason: $reason,
            correlationId: $this->correlationId ?? '',
        );
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): int { return $this->userId; }
    public function getAmount(): Money { return $this->amount; }
    public function getIdempotencyKey(): IdempotencyKey { return $this->idempotencyKey; }
    public function getStatus(): PaymentStatus { return $this->status; }
    public function getProviderPaymentId(): ?string { return $this->providerPaymentId; }
    public function getPaymentUrl(): ?string { return $this->paymentUrl; }
    public function getCorrelationId(): ?string { return $this->correlationId; }
    public function getMetadata(): array { return $this->metadata; }
    public function isRecurrent(): bool { return $this->recurrent; }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
