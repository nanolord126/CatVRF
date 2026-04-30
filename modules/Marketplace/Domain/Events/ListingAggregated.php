<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Events;

use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Событие агрегации позиции из вертикали в маркетплейс
 */
final readonly class ListingAggregated
{
    public function __construct(
        public UuidInterface $listingUuid,
        public VerticalSource $source,
        public int $sourceId,
        public string $sourceType,
        public int $tenantId,
        public \DateTimeImmutable $occurredAt,
    ) {}

    public static function fromListing(ProductListing $listing): self
    {
        return new self(
            listingUuid: $listing->uuid,
            source: $listing->source,
            sourceId: $listing->sourceId,
            sourceType: $listing->sourceType,
            tenantId: $listing->tenantId,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'listing_uuid' => $this->listingUuid->toString(),
            'source' => $this->source->value,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'tenant_id' => $this->tenantId,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
