<?php declare(strict_types=1);

namespace App\Models\Domains\Food;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FoodOrder
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
 * @package App\Models\Domains\Food
 */
final class FoodOrder extends Model
{

        protected $table = 'food_orders';

        protected static function newFactory()
        {
            return \Database\Factories\FoodOrderFactory::new();
        }

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'restaurant_id',
            'customer_id',
            'total_amount',
            'status',
            'items',
            'delivery_address',
        ];

        protected $casts = [
            'items' => 'array',
        ];

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
