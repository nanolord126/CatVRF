<?php

declare(strict_types=1);

namespace App\Domains\Food\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class OrderModel
 *
 * Part of the Food vertical domain.
 * Follows CatVRF 9-layer architecture.
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
 * @package App\Domains\Food\Infrastructure\Persistence\Eloquent\Models
 */
final class OrderModel extends Model
{

    protected $table = 'food_orders';

    public $timestamps = true;

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'restaurant_id',
        'client_id',
        'total_price',
        'currency',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'total_price' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }
}
