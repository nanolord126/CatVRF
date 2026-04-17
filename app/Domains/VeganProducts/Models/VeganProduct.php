<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;



use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\TenantScoped;

final class VeganProduct extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'vegan_products';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'price',
        'b2b_price',
        'is_b2b_available',
        'current_stock',
        'hold_stock',
        'availability_status',
        'allergen_info',
        'ingredients',
        'tags',
        'is_active',
    ];

        /**
         * Check availability taking hold stock into account.
         */
        public function getIsAvailableAttribute(): bool
        {
            return ($this->current_stock - $this->hold_stock) > 0
                   && $this->availability_status === 'in_stock';
        }

        /**
         * Return B2B or B2C price based on client context.
         */
        public function getActivePrice(bool $isB2B = false): int
        {
            return ($isB2B && $this->is_b2b_available && $this->b2b_price)
                   ? $this->b2b_price
                   : $this->price;
        }

        public function scopeInStock(Builder $query): Builder
        {
            return $query->where('current_stock', '>', 0)->where('availability_status', 'in_stock');
        }

        public function scopeByAllergen(Builder $query, string $allergen): Builder
        {
            return $query->whereJsonContains('allergen_info', $allergen);
        }
    }
