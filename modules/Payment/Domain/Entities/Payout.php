<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Payout
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $sellerId,
        public int $paymentRecordId,
        public int $amountKopecks,
        public string $providerCode,
        public string $status, // pending, processing, succeeded, failed
        public ?string $providerPayoutId,
        public ?array $providerResponse,
        public string $correlationId,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $processedAt,
        public ?CarbonImmutable $failedAt,
    ) {}
}
