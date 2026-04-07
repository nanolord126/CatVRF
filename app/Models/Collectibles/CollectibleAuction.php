<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class CollectibleAuction extends Model
{
    use HasFactory;

    protected $table = 'collectible_auctions';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'item_id',
            'start_price_cents',
            'reserve_price_cents',
            'current_bid_cents',
            'last_bidder_id',
            'starts_at',
            'ends_at',
            'status',
            'correlation_id',
        ];

        protected $casts = [
            'start_price_cents' => 'integer',
            'reserve_price_cents' => 'integer',
            'current_bid_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (CollectibleAuction $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Relationship to the object being auctioned.
         */
        public function item(): BelongsTo
        {
            return $this->belongsTo(CollectibleItem::class, 'item_id');
        }

        /**
         * Determine if current bidding has met reserve.
         */
        public function reserveMet(): bool
        {
            return $this->current_bid_cents >= ($this->reserve_price_cents ?? 0);
        }
}
