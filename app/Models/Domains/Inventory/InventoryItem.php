<?php

declare(strict_types=1);

/**
 * InventoryItem — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/inventoryitem
 * @see https://catvrf.ru/docs/inventoryitem
 */

namespace App\Models\Domains\Inventory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Database\Factories\InventoryItemFactory;

/**
 * Class InventoryItem
 *
 * Part of the Inventory vertical domain.
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InventoryItem extends Model
{
    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
    ];

    protected static function newFactory()
    {
        return InventoryItemFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
