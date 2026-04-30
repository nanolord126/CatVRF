<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Modules\Payment\Domain\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final readonly class Payment
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $payableId,
        public ?string $payableType,
        public string $uuid,
        public float $amount,
        public string $currency,
        public PaymentStatus $status,
        public ?string $gateway,
        public ?string $gatewayTransactionId,
        public ?string $gatewayResponse,
        public ?string $errorMessage,
        public ?CarbonImmutable::class $paidAt,
        public ?CarbonImmutable::class $failedAt,
        public ?CarbonImmutable::class $refundedAt,
        public ?string $refundReason,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        ?int $payableId,
        ?string $payableType,
        float $amount,
        string $currency = 'RUB',
        ?string $gateway = null,
        ?array $metadata = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            payableId: $payableId,
            payableType: $payableType,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            amount: $amount,
            currency: $currency,
            status: PaymentStatus::PENDING,
            gateway: $gateway,
            gatewayTransactionId: null,
            gatewayResponse: null,
            errorMessage: null,
            paidAt: null,
            failedAt: null,
            refundedAt: null,
            refundReason: null,
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markSucceeded(?string $gatewayTransactionId = null, ?string $gatewayResponse = null): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            payableId: $this->payableId,
            payableType: $this->payableType,
            uuid: $this->uuid,
            amount: $this->amount,
            currency: $this->currency,
            status: PaymentStatus::SUCCEEDED,
            gateway: $this->gateway,
            gatewayTransactionId: $gatewayTransactionId,
            gatewayResponse: $gatewayResponse,
            errorMessage: null,
            paidAt: CarbonImmutable::now(),
            failedAt: $this->failedAt,
            refundedAt: $this->refundedAt,
            refundReason: $this->refundReason,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markFailed(?string $errorMessage = null): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            payableId: $this->payableId,
            payableType: $this->payableType,
            uuid: $this->uuid,
            amount: $this->amount,
            currency: $this->currency,
            status: PaymentStatus::FAILED,
            gateway: $this->gateway,
            gatewayTransactionId: $this->gatewayTransactionId,
            gatewayResponse: $this->gatewayResponse,
            errorMessage: $errorMessage,
            paidAt: $this->paidAt,
            failedAt: CarbonImmutable::now(),
            refundedAt: $this->refundedAt,
            refundReason: $this->refundReason,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markPartiallyPaid(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            payableId: $this->payableId,
            payableType: $this->payableType,
            uuid: $this->uuid,
            amount: $this->amount,
            currency: $this->currency,
            status: PaymentStatus::PARTIALLY_PAID,
            gateway: $this->gateway,
            gatewayTransactionId: $this->gatewayTransactionId,
            gatewayResponse: $this->gatewayResponse,
            errorMessage: $this->errorMessage,
            paidAt: CarbonImmutable::now(),
            failedAt: $this->failedAt,
            refundedAt: $this->refundedAt,
            refundReason: $this->refundReason,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markRefunded(string $reason): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            payableId: $this->payableId,
            payableType: $this->payableType,
            uuid: $this->uuid,
            amount: $this->amount,
            currency: $this->currency,
            status: PaymentStatus::REFUNDED,
            gateway: $this->gateway,
            gatewayTransactionId: $this->gatewayTransactionId,
            gatewayResponse: $this->gatewayResponse,
            errorMessage: $this->errorMessage,
            paidAt: $this->paidAt,
            failedAt: $this->failedAt,
            refundedAt: CarbonImmutable::now(),
            refundReason: $reason,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }
}
