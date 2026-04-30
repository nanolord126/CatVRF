<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\OptimisticLockException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Class InventoryItem
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
 * @property int $version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'product_id',
        'sku',
        'name',
        'current_stock',
        'hold_stock',
        'min_stock_threshold',
        'max_stock_threshold',
        'correlation_id',
        'tags',
        'last_checked_at',
        'version',
    ];

    protected $casts = [
        'current_stock'       => 'integer',
        'hold_stock'          => 'integer',
        'min_stock_threshold' => 'integer',
        'max_stock_threshold' => 'integer',
        'tags'                => 'json',
        'last_checked_at'     => 'datetime',
        'version'             => 'integer',
        'supplier_name'       => \App\Casts\EncryptedPIICast::class,
        'performed_by'        => \App\Casts\EncryptedPIICast::class,
        'approved_by'         => \App\Casts\EncryptedPIICast::class,
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id');
    }

    /**
     * Optimistic locking: increment version on update
     */
    protected static function booted(): void
    {
        parent::booted();

        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });

        static::creating(function ($model) {
            if (!isset($model->version)) {
                $model->version = 1;
            }
        });

        static::updating(function ($model) {
            $model->version++;
        });
    }

    /**
     * Update with optimistic locking
     *
     * @param  array<string, mixed>  $attributes
     * @param  int  $expectedVersion
     * @return bool
     * @throws OptimisticLockException
     */
    public function updateWithLock(array $attributes, int $expectedVersion): bool
    {
        $currentVersion = $this->getOriginal('version') ?? $this->version;

        if ($currentVersion !== $expectedVersion) {
            throw new OptimisticLockException(
                entityType: 'inventory_item',
                entityId: $this->id,
                expectedVersion: $expectedVersion,
                actualVersion: $currentVersion
            );
        }

        return $this->update($attributes);
    }

    /**
     * Scope for optimistic locking check
     */
    public function scopeWithVersion(Builder $query, int $version): Builder
    {
        return $query->where('version', $version);
    }
}

    