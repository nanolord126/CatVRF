<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * NFT Gift Collection
 */
class NftGiftCollection extends BaseModel
{
    use HasFactory;

    protected $table = 'nft_gift_collections';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'collection_name',
        'collection_description',
        'collection_image_url',
        'ton_collection_address',
        'status',
        'started_at',
        'ended_at',
        'max_supply',
        'minted_count',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'tags' => 'json',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['ton_collection_address', 'correlation_id'];

    public function gifts(): HasMany
    {
        return $this->hasMany(NftGift::class, 'nft_contract_address', 'ton_collection_address');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (! $this->started_at || $this->started_at <= now()) &&
            (! $this->ended_at || $this->ended_at >= now());
    }

    public function isFull(): bool
    {
        return $this->max_supply && $this->minted_count >= $this->max_supply;
    }
}
