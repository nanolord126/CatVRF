<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Class CollectibleCategory
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CollectibleCategory extends Model
{
    protected $table = 'collectible_categories';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'name',
        'slug',
        'tenant_id',
    ];

    /**
     * Get items under this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectibleItem::class, 'category_id');
    }

    protected static function booted(): void
    {
        self::creating(function (CollectibleCategory $model) {
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
