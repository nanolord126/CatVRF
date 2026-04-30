<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Payment\Domain\ValueObjects\Money;

final readonly class PaymentIntent
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public ?int $businessGroupId,
        public ?int $userId,
        public ?int $payableId,
        public ?string $payableType,
        public Money $amount,
        public string $currency,
        public string $status,
        public ?string $gateway,
        public ?string $gatewayTransactionId,
        public ?string $errorMessage,
        public ?CarbonImmutable $paidAt,
        public ?CarbonImmutable $failedAt,
        public ?CarbonImmutable $refundedAt,
        public ?string $refundReason,
        public array $metadata,
        public string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
