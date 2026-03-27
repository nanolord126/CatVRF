<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * CollectibleCategory — Classification of collectible goods.
 */
final class CollectibleCategory extends Model
{
    protected $table = 'collectible_categories';

    protected $fillable = [
        'name',
        'slug',
        'tenant_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectibleCategory $model) {
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Get items under this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectibleItem::class, 'category_id');
    }
}
