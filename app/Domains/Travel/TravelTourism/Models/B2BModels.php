<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * B2BTravelStorefront Model (L1)
 * Витрина B2B-партнёра для туристических услуг.
 */
/**
 * B2BTravelStorefront Model (L1)
 * Витрина B2B-партнёра для туристических услуг.
 */
final class B2BTravelStorefront extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'b2b_travel_storefronts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'company_name',
        'inn',
        'description',
        'service_categories',
        'wholesale_discount',
        'min_order_amount',
        'is_verified',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'service_categories' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'wholesale_discount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BTravelOrder::class, 'b2b_travel_storefront_id');
    }
}
