<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamProduct extends Model
{


    use HasFactory, SoftDeletes;

        protected $table = 'stream_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'stream_id',
            'product_id',
            'business_group_id',
            'product_name',
            'product_description',
            'product_image_url',
            'price_during_stream',
            'original_price',
            'quantity_available',
            'quantity_sold',
            'is_pinned',
            'pin_position',
            'sale_type',
            'tags',
            'correlation_id',
            'pinned_at',
            'unpinned_at',
        ];

        protected $casts = [
            'tags' => 'json',
            'price_during_stream' => 'decimal:2',
            'original_price' => 'decimal:2',
            'pinned_at' => 'datetime',
            'unpinned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('stream_products.tenant_id', tenant()->id);
            });
        }

        public function stream(): BelongsTo
        {
            return $this->belongsTo(Stream::class, 'stream_id');
        }

        public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(StreamOrder::class, 'stream_product_id');
        }

        public function getDiscountPercentAttribute(): float
        {
            if (! $this->original_price || $this->original_price === 0) {
                return 0;
            }

            return (($this->original_price - $this->price_during_stream) / $this->original_price) * 100;
        }

        public function hasDiscount(): bool
        {
            return $this->original_price > $this->price_during_stream;
        }

        public function isSoldOut(): bool
        {
            return $this->quantity_available <= $this->quantity_sold;
        }

        public function getAvailableQuantity(): int
        {
            return max(0, $this->quantity_available - $this->quantity_sold);
        }
}
