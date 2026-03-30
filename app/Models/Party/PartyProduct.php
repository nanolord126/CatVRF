<?php declare(strict_types=1);

namespace App\Models\Party;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PartyProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'party_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'party_store_id',
            'party_category_id',
            'party_theme_id',
            'sku',
            'name',
            'description',
            'price_cents',
            'price_currency',
            'current_stock',
            'min_stock_threshold',
            'attributes',
            'is_b2b',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'attributes' => 'json',
            'tags' => 'json',
            'price_cents' => 'integer',
            'current_stock' => 'integer',
            'is_b2b' => 'boolean',
            'is_active' => 'boolean',
        ];

        /**
         * Boot logic for UUID and Tenant Scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Link to store.
         */
        public function store(): BelongsTo
        {
            return $this->belongsTo(PartyStore::class, 'party_store_id');
        }

        /**
         * Link to category.
         */
        public function category(): BelongsTo
        {
            return $this->belongsTo(PartyCategory::class, 'party_category_id');
        }

        /**
         * Link to theme/collection.
         */
        public function theme(): BelongsTo
        {
            return $this->belongsTo(PartyTheme::class, 'party_theme_id');
        }

        /**
         * Check if enough stock is available.
         */
        public function hasStock(int $quantity): bool
        {
            return $this->current_stock >= $quantity;
        }

        /**
         * Calculate price with potential discount (simplified here).
         */
        public function getPriceForQuantity(int $quantity): int
        {
            return $this->price_cents * $quantity;
        }
}
