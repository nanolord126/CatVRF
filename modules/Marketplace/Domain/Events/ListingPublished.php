<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Events;

use Ramsey\Uuid\UuidInterface;

/**
 * Событие публикации позиции на маркетплейсе
 */
final readonly class ListingPublished
{
    public function __construct(
        public UuidInterface $listingUuid,
        public int $tenantId,
        public \DateTimeImmutable $publishedAt,
    ) {}

    public static function create(UuidInterface $listingUuid, int $tenantId): self
    {
        return new self(
            listingUuid: $listingUuid,
            tenantId: $tenantId,
            publishedAt: new \DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'listing_uuid' => $this->listingUuid->toString(),
            'tenant_id' => $this->tenantId,
            'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
        ];
    }
}
