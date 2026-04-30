<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Advertising\Domain\Entities\Auction as AuctionEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent Auction Model
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
class EloquentAuction extends Model
{
    use SoftDeletes;

    protected $table = 'auctions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'type',
        'status',
        'start_at',
        'end_at',
        'starting_price',
        'current_price',
        'reserve_price',
        'inventory_id',
        'bid_history',
        'correlation_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'starting_price' => 'integer',
        'current_price' => 'integer',
        'reserve_price' => 'integer',
        'inventory_id' => 'integer',
        'bid_history' => 'array',
    ];

    public function toDomain(): AuctionEntity
    {
        return new AuctionEntity(
            id: $this->id,
            uuid: $this->uuid,
            tenant_id: $this->tenant_id,
            name: $this->name,
            type: $this->type,
            status: $this->status,
            start_at: $this->start_at,
            end_at: $this->end_at,
            starting_price: $this->starting_price,
            current_price: $this->current_price,
            reserve_price: $this->reserve_price,
            inventory_id: $this->inventory_id,
            bid_history: $this->bid_history ?? [],
            correlation_id: $this->correlation_id,
        );
    }

    public static function fromDomain(AuctionEntity $entity): self
    {
        return new self([
            'uuid' => $entity->uuid,
            'tenant_id' => $entity->tenant_id,
            'name' => $entity->name,
            'type' => $entity->type,
            'status' => $entity->status,
            'start_at' => $entity->start_at,
            'end_at' => $entity->end_at,
            'starting_price' => $entity->starting_price,
            'current_price' => $entity->current_price,
            'reserve_price' => $entity->reserve_price,
            'inventory_id' => $entity->inventory_id,
            'bid_history' => $entity->bid_history,
            'correlation_id' => $entity->correlation_id,
        ]);
    }
}
