<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class OutboxMessage
{
    public function __construct(
        public int $id,
        public string $uuid,
        public ?int $tenantId,
        public string $eventType,
        public string $targetUrl,
        public array $payload,
        public string $idempotencyKey,
        public string $status, // pending, delivered, failed
        public int $attemptCount,
        public ?CarbonImmutable $deliverAfter,
        public ?CarbonImmutable $lastAttemptAt,
        public ?CarbonImmutable $deliveredAt,
        public ?CarbonImmutable $failedAt,
        public ?int $responseCode,
        public ?string $responseBody,
        public string $correlationId,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
