<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Auction Domain Entity
 *
 * Represents an auction for ad inventory slots.
 * Supports forward, dutch, and sealed-bid auction types.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class Auction
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenant_id,
        public string $name,
        public string $type, // forward, dutch, sealed_bid
        public string $status, // upcoming, active, closed, cancelled
        public Carbon $start_at,
        public Carbon $end_at,
        public int $starting_price,
        public int $current_price,
        public int $reserve_price,
        public int $inventory_id,
        public array $bid_history,
        public string $correlation_id,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $type,
        Carbon $startAt,
        Carbon $endAt,
        int $startingPrice,
        int $reservePrice,
        int $inventoryId,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            tenant_id: $tenantId,
            name: $name,
            type: $type,
            status: 'upcoming',
            start_at: $startAt,
            end_at: $endAt,
            starting_price: $startingPrice,
            current_price: $startingPrice,
            reserve_price: $reservePrice,
            inventory_id: $inventoryId,
            bid_history: [],
            correlation_id: $correlationId ?? (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_at->isPast()
            && $this->end_at->isFuture();
    }

    public function canStart(): bool
    {
        return $this->status === 'upcoming' && $this->start_at->isPast();
    }

    public function canClose(): bool
    {
        return $this->status === 'active' && ($this->end_at->isPast() || $this->hasMetReservePrice());
    }

    public function hasMetReservePrice(): bool
    {
        return $this->current_price >= $this->reserve_price;
    }

    public function isReservePriceMet(): bool
    {
        return $this->current_price >= $this->reserve_price;
    }

    public function getHighestBid(): ?array
    {
        return empty($this->bid_history) ? null : max($this->bid_history, fn($a, $b) => $a['amount'] <=> $b['amount']);
    }

    public function getWinningBid(): ?array
    {
        if ($this->status !== 'closed') {
            return null;
        }

        return $this->getHighestBid();
    }

    public function canBid(int $bidAmount): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->type === 'dutch') {
            return $bidAmount >= $this->current_price;
        }

        return $bidAmount > $this->current_price;
    }
}
