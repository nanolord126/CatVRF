<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StockMovement
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
final class StockMovement extends Model
{
    use HasFactory;

        protected $table = 'stock_movements';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'inventory_item_id',
            'type',
            'quantity',
            'reason',
            'source_type',
            'source_id',
            'correlation_id',
            'created_by',
        ];

        protected $casts = [
            'quantity' => 'integer',
        ];

        public function inventoryItem(): BelongsTo
        {
            return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }
}
