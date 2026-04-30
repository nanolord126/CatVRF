<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Cart
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $userId,
        public int $sellerId,
        public string $status,
        public ?CarbonImmutable $reservedUntil,
        public ?array $metadata,
        public string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isExpired(): bool
    {
        return $this->reservedUntil !== null && $this->reservedUntil->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
