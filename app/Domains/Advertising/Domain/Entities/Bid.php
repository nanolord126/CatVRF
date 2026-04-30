<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Bid Domain Entity
 *
 * Represents a bid placed in an auction.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class Bid
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $auction_id,
        public int $tenant_id,
        public int $bidder_id,
        public int $amount,
        public string $status, // pending, winning, losing, withdrawn
        public Carbon $placed_at,
        public string $correlation_id,
    ) {}

    public static function place(
        int $auctionId,
        int $tenantId,
        int $bidderId,
        int $amount,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            auction_id: $auctionId,
            tenant_id: $tenantId,
            bidder_id: $bidderId,
            amount: $amount,
            status: 'pending',
            placed_at: Carbon::now(),
            correlation_id: $correlationId ?? (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function markAsWinning(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            auction_id: $this->auction_id,
            tenant_id: $this->tenant_id,
            bidder_id: $this->bidder_id,
            amount: $this->amount,
            status: 'winning',
            placed_at: $this->placed_at,
            correlation_id: $this->correlation_id,
        );
    }

    public function markAsLosing(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            auction_id: $this->auction_id,
            tenant_id: $this->tenant_id,
            bidder_id: $this->bidder_id,
            amount: $this->amount,
            status: 'losing',
            placed_at: $this->placed_at,
            correlation_id: $this->correlation_id,
        );
    }

    public function withdraw(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            auction_id: $this->auction_id,
            tenant_id: $this->tenant_id,
            bidder_id: $this->bidder_id,
            amount: $this->amount,
            status: 'withdrawn',
            placed_at: $this->placed_at,
            correlation_id: $this->correlation_id,
        );
    }
}
