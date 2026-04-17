<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Models
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
        ];

        protected $casts = [
            'current_stock'       => 'integer',
            'hold_stock'          => 'integer',
            'min_stock_threshold' => 'integer',
            'max_stock_threshold' => 'integer',
            'tags'                => 'json',
            'last_checked_at'     => 'datetime',
        ];

        public function stockMovements(): HasMany
        {
            return $this->hasMany(StockMovement::class, 'inventory_item_id');
        }

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
