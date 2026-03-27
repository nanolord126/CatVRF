<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * UserCollection — Grouping of collectible items belonging to a user.
 */
final class UserCollection extends Model
{
    protected $table = 'user_collections';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'name',
        'theme',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserCollection $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Get all items in this collection.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectibleItem::class, 'collection_id');
    }
}
