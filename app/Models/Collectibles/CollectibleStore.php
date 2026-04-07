<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class CollectibleStore
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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Models\Collectibles
 */
final class CollectibleStore extends Model
{
    use HasFactory;

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
