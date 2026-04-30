<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Ad Inventory Domain Entity
 *
 * Represents available ad inventory from publishers.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AdInventory
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $publisher_id,
        public string $inventory_type, // short, banner, video, native
        public string $placement, // feed, story, search, etc.
        public int $available_impressions,
        public int $reserved_impressions,
        public Carbon $available_from,
        public Carbon $available_until,
        public string $status, // available, reserved, sold_out
        public array $targeting_restrictions,
        public string $correlation_id,
    ) {}

    public static function create(
        int $publisherId,
        string $inventoryType,
        string $placement,
        int $availableImpressions,
        Carbon $availableFrom,
        Carbon $availableUntil,
        array $targetingRestrictions,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            publisher_id: $publisherId,
            inventory_type: $inventoryType,
            placement: $placement,
            available_impressions: $availableImpressions,
            reserved_impressions: 0,
            available_from: $availableFrom,
            available_until: $availableUntil,
            status: 'available',
            targeting_restrictions: $targetingRestrictions,
            correlation_id: $correlationId ?? (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available'
            && $this->available_from->isPast()
            && $this->available_until->isFuture()
            && $this->getRemainingImpressions() > 0;
    }

    public function getRemainingImpressions(): int
    {
        return max(0, $this->available_impressions - $this->reserved_impressions);
    }

    public function reserve(int $impressions): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            publisher_id: $this->publisher_id,
            inventory_type: $this->inventory_type,
            placement: $this->placement,
            available_impressions: $this->available_impressions,
            reserved_impressions: $this->reserved_impressions + $impressions,
            available_from: $this->available_from,
            available_until: $this->available_until,
            status: $this->getRemainingImpressions() === 0 ? 'sold_out' : $this->status,
            targeting_restrictions: $this->targeting_restrictions,
            correlation_id: $this->correlation_id,
        );
    }

    public function release(int $impressions): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            publisher_id: $this->publisher_id,
            inventory_type: $this->inventory_type,
            placement: $this->placement,
            available_impressions: $this->available_impressions,
            reserved_impressions: max(0, $this->reserved_impressions - $impressions),
            available_from: $this->available_from,
            available_until: $this->available_until,
            status: $this->status === 'sold_out' ? 'available' : $this->status,
            targeting_restrictions: $this->targeting_restrictions,
            correlation_id: $this->correlation_id,
        );
    }
}
