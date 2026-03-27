<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * CollectibleStore Model — Store/Vendor specialized in collectibles.
 * CAÑON 2026 Ready.
 */
final class CollectibleStore extends Model
{
    protected $table = 'collectible_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'address',
        'description',
        'rating',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'rating' => 'float',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectibleStore $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Get all items belonging to this store.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectibleItem::class, 'store_id');
    }
}
