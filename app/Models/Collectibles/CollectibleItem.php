<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CollectibleItem extends Model
{

    protected $table = 'collectible_items';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'store_id',
            'category_id',
            'collection_id',
            'name',
            'description',
            'rarity',
            'condition_grade',
            'price_cents',
            'estimated_value_cents',
            'is_limited_edition',
            'serial_number',
            'correlation_id',
            'attributes',
            'tags',
        ];

        protected $casts = [
            'price_cents' => 'integer',
            'estimated_value_cents' => 'integer',
            'is_limited_edition' => 'boolean',
            'attributes' => 'json',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (CollectibleItem $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Relationship to the owning store.
         */
        public function store(): BelongsTo
        {
            return $this->belongsTo(CollectibleStore::class, 'store_id');
        }

        /**
         * Relationship to the item category.
         */
        public function category(): BelongsTo
        {
            return $this->belongsTo(CollectibleCategory::class, 'category_id');
        }

        /**
         * Relationship to the user collection.
         */
        public function collection(): BelongsTo
        {
            return $this->belongsTo(UserCollection::class, 'collection_id');
        }

        /**
         * Authenticity certificate association.
         */
        public function certificate(): HasOne
        {
            return $this->hasOne(CollectibleCertificate::class, 'item_id');
        }

        /**
         * If the item is on active auction.
         */
        public function auctions(): HasMany
        {
            return $this->hasMany(CollectibleAuction::class, 'item_id');
        }
}
