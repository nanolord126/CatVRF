<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Advertising\Domain\Entities\Bid as BidEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent Bid Model
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
class EloquentBid extends Model
{
    use SoftDeletes;

    protected $table = 'bids';

    protected $fillable = [
        'uuid',
        'auction_id',
        'tenant_id',
        'bidder_id',
        'amount',
        'status',
        'placed_at',
        'correlation_id',
    ];

    protected $casts = [
        'auction_id' => 'integer',
        'tenant_id' => 'integer',
        'bidder_id' => 'integer',
        'amount' => 'integer',
        'placed_at' => 'datetime',
    ];

    public function toDomain(): BidEntity
    {
        return new BidEntity(
            id: $this->id,
            uuid: $this->uuid,
            auction_id: $this->auction_id,
            tenant_id: $this->tenant_id,
            bidder_id: $this->bidder_id,
            amount: $this->amount,
            status: $this->status,
            placed_at: $this->placed_at,
            correlation_id: $this->correlation_id,
        );
    }

    public static function fromDomain(BidEntity $entity): self
    {
        return new self([
            'uuid' => $entity->uuid,
            'auction_id' => $entity->auction_id,
            'tenant_id' => $entity->tenant_id,
            'bidder_id' => $entity->bidder_id,
            'amount' => $entity->amount,
            'status' => $entity->status,
            'placed_at' => $entity->placed_at,
            'correlation_id' => $entity->correlation_id,
        ]);
    }
}
